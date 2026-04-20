<?php

namespace App\Services\Billing;

use App\Models\Bill;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PaymentService
{
    public function makePayment(array $data): Payment
    {
        return DB::transaction(function () use ($data) {
            /** @var Bill $bill */
            $bill = Bill::query()->lockForUpdate()->findOrFail((int) $data['bill_id']);

            $amount = (float) $data['amount'];
            if ($amount <= 0) {
                throw new InvalidArgumentException('Payment amount must be greater than 0.');
            }

            $alreadyPaid = (float) $bill->payments()->sum('amount');
            $remaining = (float) $bill->total_amount - $alreadyPaid;

            if ($amount > $remaining) {
                throw new InvalidArgumentException('Payment amount exceeds remaining balance.');
            }

            $payment = Payment::query()->create([
                'bill_id' => $bill->id,
                'payment_method' => $data['payment_method'],
                'amount' => $amount,
                'paid_at' => now(),
            ]);

            $newTotalPaid = $alreadyPaid + $amount;
            $isPaid = $newTotalPaid >= (float) $bill->total_amount;
            $bill->status = $isPaid ? 'paid' : 'unpaid';
            $bill->save();

            // If bill is fully paid, mark table as free
            if ($isPaid) {
                $bill->table->update(['status' => 'free']);
            }

            return $payment;
        });
    }
}

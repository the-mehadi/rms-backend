<?php

namespace App\Services\Billing;

use App\Models\Bill;
use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BillingService
{
    public function getAllBills(int $perPage = 15): LengthAwarePaginator
    {
        return Bill::query()
            ->with(['order', 'cashier'])
            ->latest('created_at')
            ->paginate($perPage);
    }

    public function getBillById(int $id): Bill
    {
        return Bill::query()
            ->with(['order.items.menuItem', 'payments'])
            ->findOrFail($id);
    }

    public function createBill(int $orderId, int $userId, float $discount = 0.0, float $vat = 0.0): Bill
    {
        return DB::transaction(function () use ($orderId, $userId, $discount, $vat) {
            $order = Order::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($orderId);

            if ($order->status !== 'served') {
                throw new InvalidArgumentException('Bill can only be created for served orders.');
            }

            $existingBill = Bill::query()->where('order_id', $order->id)->lockForUpdate()->first();
            if ($existingBill) {
                throw new InvalidArgumentException('A bill already exists for this order.');
            }

            $subtotal = (float) $order->items->sum(fn ($item) => (float) $item->price * (int) $item->quantity);

            if ($discount < 0 || $vat < 0) {
                throw new InvalidArgumentException('Discount and VAT must be non-negative.');
            }

            if ($discount > $subtotal) {
                throw new InvalidArgumentException('Discount cannot exceed subtotal.');
            }

            $totalAmount = $subtotal - $discount + $vat;

            return Bill::query()->create([
                'order_id' => $order->id,
                'user_id' => $userId,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'vat' => $vat,
                'total_amount' => $totalAmount,
                'status' => 'unpaid',
            ]);
        });
    }

    public function getReceipt(int $billId): Bill
    {
        return Bill::query()
            ->with(['order.items.menuItem', 'payments'])
            ->findOrFail($billId);
    }
}


<?php

namespace App\Services\Billing;

use App\Models\Bill;
use App\Models\Order;
use App\Models\Table;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BillingService
{
    public function getAllBills(int $perPage = 15): LengthAwarePaginator
    {
        return Bill::query()
            ->with(['table', 'orders.items.menuItem', 'cashier', 'payments'])
            ->latest('created_at')
            ->paginate($perPage);
    }

    public function getBillById(int $id): Bill
    {
        return Bill::query()
            ->with(['table', 'orders.items.menuItem', 'cashier', 'payments'])
            ->findOrFail($id);
    }

    /**
     * Create a merged bill for all unpaid orders of a table.
     *
     * @param int $tableId
     * @param int $userId
     * @param float $discount
     * @param float $vat (as percentage, defaults to 5%)
     * @return Bill
     * @throws InvalidArgumentException
     */
    public function createBill(int $tableId, int $userId, float $discount = 0.0, float $vat = 5.0): Bill
    {
        return DB::transaction(function () use ($tableId, $userId, $discount, $vat) {
            $table = Table::query()->lockForUpdate()->findOrFail($tableId);

            // Get all unpaid orders for this table
            $unpaidOrders = Order::query()
                ->with('items')
                ->where('table_id', $tableId)
                ->where('status', '!=', 'cancelled')
                ->whereNotIn('id', function ($query) {
                    $query->select('orders.id')
                        ->from('orders')
                        ->leftJoin('bill_orders', 'orders.id', '=', 'bill_orders.order_id')
                        ->leftJoin('bills', 'bill_orders.bill_id', '=', 'bills.id')
                        ->where('bills.status', 'paid');
                })
                ->lockForUpdate()
                ->get();

            if ($unpaidOrders->isEmpty()) {
                throw new InvalidArgumentException('No unpaid orders found for this table.');
            }

            // Calculate subtotal from all orders
            $subtotal = 0.0;
            foreach ($unpaidOrders as $order) {
                foreach ($order->items as $item) {
                    $subtotal += (float) $item->price * (int) $item->quantity;
                }
            }

            if ($discount < 0 || $vat < 0) {
                throw new InvalidArgumentException('Discount and VAT must be non-negative.');
            }

            if ($discount > $subtotal) {
                throw new InvalidArgumentException('Discount cannot exceed subtotal.');
            }

            // Calculate VAT based on percentage
            $vatAmount = ($subtotal - $discount) * ($vat / 100);
            $totalAmount = ($subtotal - $discount) + $vatAmount;

            // Create the bill
            $bill = Bill::query()->create([
                'table_id' => $tableId,
                'user_id' => $userId,
                'subtotal' => round($subtotal, 2),
                'discount' => round($discount, 2),
                'vat' => round($vatAmount, 2),
                'total_amount' => round($totalAmount, 2),
                'status' => 'unpaid',
            ]);

            // Link all unpaid orders to this bill
            $orderIds = $unpaidOrders->pluck('id')->toArray();
            $bill->orders()->attach($orderIds);

            return $bill->fresh(['table', 'orders.items.menuItem', 'cashier']);
        });
    }

    public function getReceipt(int $billId): Bill
    {
        return Bill::query()
            ->with(['table', 'orders.items.menuItem', 'cashier', 'payments'])
            ->findOrFail($billId);
    }
}

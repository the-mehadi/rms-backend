<?php

namespace App\Services\Order;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OrderService
{
    /**
     * Get all orders with pagination.
     */
    public function getAllOrders(int $perPage = 15): LengthAwarePaginator
    {
        return Order::with(['table', 'user', 'items.menuItem'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get a single order by ID.
     */
    public function getOrderById(int $id): Order
    {
        return Order::with(['table', 'user', 'items.menuItem'])->findOrFail($id);
    }

    /**
     * Get the active order for a table.
     */
    public function getOrderByTable(int $tableId): ?Order
    {
        return Order::with(['table', 'user', 'items.menuItem'])
            ->where('table_id', $tableId)
            ->whereNotIn('status', ['served', 'cancelled'])
            ->first();
    }

    /**
     * Get all unpaid orders for a table.
     * Includes orders where payment status is unpaid OR no payment record exists.
     * Excludes cancelled orders.
     */
    public function getUnpaidOrdersByTable(int $tableId)
    {
        return Order::with(['table', 'user', 'items.menuItem'])
            ->where('table_id', $tableId)
            ->where('status', '!=', 'cancelled')
            ->whereNotIn('id', function ($query) {
                $query->select('orders.id')
                    ->from('orders')
                    ->leftJoin('bill_orders', 'orders.id', '=', 'bill_orders.order_id')
                    ->leftJoin('bills', 'bill_orders.bill_id', '=', 'bills.id')
                    ->where('bills.status', 'paid');
            })
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Get bill summary for a table (all unpaid orders merged).
     */
    public function getTableBillSummary(int $tableId): array
    {
        $orders = Order::with('items')
            ->where('table_id', $tableId)
            ->where('status', '!=', 'cancelled')
            ->whereNotIn('id', function ($query) {
                $query->select('orders.id')
                    ->from('orders')
                    ->leftJoin('bill_orders', 'orders.id', '=', 'bill_orders.order_id')
                    ->leftJoin('bills', 'bill_orders.bill_id', '=', 'bills.id')
                    ->where('bills.status', 'paid');
            })
            ->get();

        $subtotal = 0;
        $orderIds = [];

        foreach ($orders as $order) {
            $orderIds[] = $order->id;
            foreach ($order->items as $item) {
                $subtotal += $item->price * $item->quantity;
            }
        }

        $vat = $subtotal * 0.05; // 5% VAT
        $grandTotal = $subtotal + $vat;

        return [
            'table_id' => $tableId,
            'order_ids' => $orderIds,
            'subtotal' => round($subtotal, 2),
            'vat' => round($vat, 2),
            'discount' => 0,
            'grand_total' => round($grandTotal, 2),
            'order_count' => count($orderIds),
        ];
    }

    /**
     * Create a new order.
     * Allows multiple orders per table - all unpaid orders are merged into a single bill.
     *
     * @throws InvalidArgumentException
     */
    public function createOrder(array $data, User $user): Order
    {
        $orderPayload = [
            'table_id' => $data['table_id'],
            'user_id' => $user->id,
            'status' => 'pending',
            'special_note' => $data['special_note'] ?? null,
        ];

        return DB::transaction(function () use ($orderPayload, $data) {
            $order = Order::create($orderPayload);

            if (!empty($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    $menuItem = MenuItem::findOrFail($item['menu_item_id']);

                    $order->items()->create([
                        'menu_item_id' => $item['menu_item_id'],
                        'quantity' => $item['quantity'],
                        'price' => $item['unit_price'] ?? $menuItem->price,
                    ]);
                }
            }

            return $order->fresh(['table', 'user', 'items.menuItem']);
        });
    }

    /**
     * Add an item to an order.
     *
     * @throws InvalidArgumentException
     */
    public function addItem(int $orderId, array $data): OrderItem
    {
        $order = Order::findOrFail($orderId);

        if (in_array($order->status, ['served', 'cancelled'])) {
            throw new InvalidArgumentException('Cannot add items to a served or cancelled order.');
        }

        $menuItem = MenuItem::findOrFail($data['menu_item_id']);

        // Check if item already exists in order
        $existingItem = $order->items()->where('menu_item_id', $data['menu_item_id'])->first();

        if ($existingItem) {
            // Increase quantity
            $existingItem->increment('quantity', $data['quantity'] ?? 1);
            return $existingItem->fresh();
        }

        // Create new item
        return $order->items()->create([
            'menu_item_id' => $data['menu_item_id'],
            'quantity' => $data['quantity'] ?? 1,
            'price' => $menuItem->price,
        ]);
    }

    /**
     * Update order status.
     *
     * @throws InvalidArgumentException
     */
    public function updateStatus(int $orderId, string $status): Order
    {
        $order = Order::findOrFail($orderId);

        $this->validateStatusTransition($order->status, $status);

        $order->update(['status' => $status]);

        return $order->fresh(['table', 'user', 'items.menuItem']);
    }

    /**
     * Cancel an order.
     */
    public function cancelOrder(int $orderId): Order
    {
        return $this->updateStatus($orderId, 'cancelled');
    }

    /**
     * Validate status transition.
     *
     * @throws InvalidArgumentException
     */
    private function validateStatusTransition(string $currentStatus, string $newStatus): void
    {
        $validTransitions = [
            'pending' => ['preparing', 'cancelled'],
            'preparing' => ['ready', 'cancelled'],
            'ready' => ['served', 'cancelled'],
            'served' => [], // Cannot change from served
            'cancelled' => [], // Cannot change from cancelled
        ];

        if (!in_array($newStatus, $validTransitions[$currentStatus] ?? [])) {
            throw new InvalidArgumentException("Invalid status transition from {$currentStatus} to {$newStatus}.");
        }
    }
}

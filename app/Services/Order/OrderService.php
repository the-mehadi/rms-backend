<?php

namespace App\Services\Order;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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
     * Create a new order.
     *
     * @throws InvalidArgumentException
     */
    public function createOrder(array $data, User $user): Order
    {
        // Check if table already has an active order
        $existingOrder = $this->getOrderByTable($data['table_id']);
        if ($existingOrder) {
            throw new InvalidArgumentException('Table already has an active order.');
        }

        $data['user_id'] = $user->id;
        $data['status'] = 'pending';

        return Order::create($data);
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

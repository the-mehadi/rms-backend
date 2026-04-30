<?php

namespace App\Services\Billing;

use App\Models\Order;
use App\Models\Table;
use Illuminate\Support\Collection;


class FloorViewService
{
    /**
     * Get floor view data for all tables with their unpaid orders.
     *
     * @return array
     */
    public function getFloorViewData(): array
    {
        // Fetch all tables ordered by table number
        $tables = Table::orderBy('table_number')->get();

        // Fetch all unpaid orders with eager loading
        $unpaidOrders = Order::with(['items', 'user'])
            ->where('status', '!=', 'cancelled')
            ->whereNotIn('id', function ($query) {
                $query->select('orders.id')
                    ->from('orders')
                    ->leftJoin('bill_orders', 'orders.id', '=', 'bill_orders.order_id')
                    ->leftJoin('bills', 'bill_orders.bill_id', '=', 'bills.id')
                    ->where('bills.status', 'paid');
            })
            ->get()
            ->groupBy('table_id');

        $tablesData = [];
        $summary = [
            'total_tables' => $tables->count(),
            'available' => 0,
            'occupied' => 0,
            'reserved' => 0,
            'ready_to_bill' => 0,
            'total_unpaid_amount' => 0.0,
        ];

        foreach ($tables as $table) {
            $tableData = [
                'id' => $table->id,
                'table_number' => $table->table_number,
                'capacity' => $table->capacity,
                'status' => 'available', // default
                'unpaid_order' => null,
            ];

            // Check if table has unpaid orders
            $tableUnpaidOrders = $unpaidOrders->get($table->id, collect());

            if ($tableUnpaidOrders->isNotEmpty()) {
                // Get the latest unpaid order
                $latestOrder = $tableUnpaidOrders->sortByDesc('created_at')->first();

                // Calculate subtotal from all unpaid orders for the table
                $subtotal = $this->calculateOrdersSubtotal($tableUnpaidOrders);

                $tableData['unpaid_order'] = [
                    'order_id' => $latestOrder->id,
                    'order_status' => $latestOrder->status,
                    'items_count' => $latestOrder->items->count(),
                    'subtotal' => round($subtotal, 2),
                    'created_at' => $latestOrder->created_at->format('Y-m-d H:i:s'),
                    'waiter_name' => $latestOrder->user->name ?? 'Unknown',
                ];

                // Determine table status
                $tableData['status'] = 'occupied';

                // Update summary
                $summary['occupied']++;
                $summary['total_unpaid_amount'] += $subtotal;

                if ($this->allOrdersServed($tableUnpaidOrders)) {
                    $summary['ready_to_bill']++;
                }
            } elseif ($table->status === 'reserved') {
                $tableData['status'] = 'reserved';
                $summary['reserved']++;
            } else {
                $summary['available']++;
            }

            $tablesData[] = $tableData;
        }

        return [
            'tables' => $tablesData,
            'summary' => $summary,
        ];
    }

    /**
     * Calculate subtotal for a collection of orders.
     *
     * @param Collection<int, Order> $orders
     * @return float
     */
    private function calculateOrdersSubtotal(Collection $orders): float
    {
        $subtotal = 0.0;

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $subtotal += (float) $item->price * (int) $item->quantity;
            }
        }

        return $subtotal;
    }

    /**
     * Determine whether all unpaid orders for a table have been served.
     *
     * @param Collection<int, Order> $orders
     * @return bool
     */
    private function allOrdersServed(Collection $orders): bool
    {
        return $orders->every(fn (Order $order) => $order->status === 'served');
    }
}

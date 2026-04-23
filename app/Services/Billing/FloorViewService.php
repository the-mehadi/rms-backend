<?php

namespace App\Services\Billing;

use App\Models\Order;
use App\Models\Table;


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
            ->whereIn('status', ['pending', 'preparing', 'ready', 'served'])
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

                // Calculate subtotal
                $subtotal = $this->calculateOrderSubtotal($latestOrder);

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

                if ($latestOrder->status === 'ready') {
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
     * Calculate subtotal for an order.
     *
     * @param Order $order
     * @return float
     */
    private function calculateOrderSubtotal(Order $order): float
    {
        $subtotal = 0.0;

        foreach ($order->items as $item) {
            $subtotal += (float) $item->price * (int) $item->quantity;
        }

        return $subtotal;
    }
}

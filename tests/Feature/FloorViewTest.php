<?php

use App\Models\Bill;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Table;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

function createOrderWithItems(Table $table, User $user, string $status, string $createdAt, array $items): Order
{
    $order = Order::factory()->create([
        'table_id' => $table->id,
        'user_id' => $user->id,
        'status' => $status,
        'created_at' => Carbon::parse($createdAt),
        'updated_at' => Carbon::parse($createdAt),
    ]);

    $category = Category::factory()->create();

    foreach ($items as $index => $item) {
        $menuItem = MenuItem::query()->create([
            'category_id' => $category->id,
            'name' => "Test Item {$order->id}-{$index}",
            'slug' => "test-item-{$order->id}-{$index}-" . str()->random(6),
            'price' => $item['price'],
            'description' => 'Test menu item',
            'is_available' => true,
            'order' => $index,
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
            'quantity' => $item['quantity'],
            'price' => $item['price'],
        ]);
    }

    return $order->fresh(['items', 'user']);
}

describe('Floor view API', function () {
    beforeEach(function () {
        $this->cashier = User::factory()->create(['role' => 'cashier', 'name' => 'Cashier One']);
    });

    it('aggregates all unpaid orders into subtotal while keeping latest order display fields', function () {
        $table = Table::factory()->create([
            'table_number' => 10,
            'status' => 'occupied',
        ]);

        $olderOrder = createOrderWithItems($table, $this->cashier, 'served', '2026-04-30 10:00:00', [
            ['quantity' => 2, 'price' => 100],
            ['quantity' => 1, 'price' => 50],
        ]);

        $latestOrder = createOrderWithItems($table, $this->cashier, 'pending', '2026-04-30 11:00:00', [
            ['quantity' => 3, 'price' => 25],
        ]);

        $response = $this->actingAs($this->cashier)->getJson('/api/floor-view');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'tables' => [[
                        'id',
                        'table_number',
                        'capacity',
                        'status',
                        'unpaid_order' => [
                            'order_id',
                            'order_status',
                            'items_count',
                            'subtotal',
                            'created_at',
                            'waiter_name',
                        ],
                    ]],
                    'summary' => [
                        'total_tables',
                        'available',
                        'occupied',
                        'reserved',
                        'ready_to_bill',
                        'total_unpaid_amount',
                    ],
                ],
            ]);

        $tableData = collect($response->json('data.tables'))->firstWhere('id', $table->id);

        expect($tableData['status'])->toBe('occupied');
        expect($tableData['unpaid_order']['order_id'])->toBe($latestOrder->id);
        expect($tableData['unpaid_order']['order_status'])->toBe('pending');
        expect($tableData['unpaid_order']['items_count'])->toBe($latestOrder->items->count());
        expect($tableData['unpaid_order']['created_at'])->toBe('2026-04-30 11:00:00');
        expect($tableData['unpaid_order']['waiter_name'])->toBe('Cashier One');
        expect($tableData['unpaid_order']['subtotal'])->toBe(325);

        $summary = $response->json('data.summary');

        expect($summary['occupied'])->toBe(1);
        expect($summary['ready_to_bill'])->toBe(0);
        expect($summary['total_unpaid_amount'])->toBe(325);
    });

    it('counts a table as ready to bill only when all unpaid orders are served and sums all tables', function () {
        $servedTable = Table::factory()->create([
            'table_number' => 1,
            'status' => 'occupied',
        ]);

        $mixedTable = Table::factory()->create([
            'table_number' => 2,
            'status' => 'occupied',
        ]);

        createOrderWithItems($servedTable, $this->cashier, 'served', '2026-04-30 09:00:00', [
            ['quantity' => 1, 'price' => 100],
        ]);
        createOrderWithItems($servedTable, $this->cashier, 'served', '2026-04-30 09:30:00', [
            ['quantity' => 2, 'price' => 50],
        ]);

        createOrderWithItems($mixedTable, $this->cashier, 'served', '2026-04-30 10:00:00', [
            ['quantity' => 1, 'price' => 200],
        ]);
        createOrderWithItems($mixedTable, $this->cashier, 'ready', '2026-04-30 10:30:00', [
            ['quantity' => 1, 'price' => 75],
        ]);

        $response = $this->actingAs($this->cashier)->getJson('/api/floor-view');

        $response->assertOk();

        $summary = $response->json('data.summary');
        $servedTableData = collect($response->json('data.tables'))->firstWhere('id', $servedTable->id);
        $mixedTableData = collect($response->json('data.tables'))->firstWhere('id', $mixedTable->id);

        expect($servedTableData['unpaid_order']['subtotal'])->toBe(200);
        expect($mixedTableData['unpaid_order']['subtotal'])->toBe(275);
        expect($summary['ready_to_bill'])->toBe(1);
        expect($summary['total_unpaid_amount'])->toBe(475);
    });

    it('excludes orders already linked to a paid bill from floor view aggregation', function () {
        $table = Table::factory()->create([
            'table_number' => 5,
            'status' => 'occupied',
        ]);

        $paidOrder = createOrderWithItems($table, $this->cashier, 'served', '2026-04-30 08:00:00', [
            ['quantity' => 1, 'price' => 500],
        ]);

        $currentOrder = createOrderWithItems($table, $this->cashier, 'pending', '2026-04-30 12:00:00', [
            ['quantity' => 2, 'price' => 60],
        ]);

        $bill = Bill::query()->create([
            'table_id' => $table->id,
            'user_id' => $this->cashier->id,
            'subtotal' => 500,
            'discount' => 0,
            'vat' => 0,
            'total_amount' => 500,
            'status' => 'paid',
        ]);
        $bill->orders()->attach($paidOrder->id);

        $response = $this->actingAs($this->cashier)->getJson('/api/floor-view');

        $response->assertOk();

        $tableData = collect($response->json('data.tables'))->firstWhere('id', $table->id);
        $summary = $response->json('data.summary');

        expect($tableData['unpaid_order']['order_id'])->toBe($currentOrder->id);
        expect($tableData['unpaid_order']['subtotal'])->toBe(120);
        expect($summary['total_unpaid_amount'])->toBe(120);
    });
});

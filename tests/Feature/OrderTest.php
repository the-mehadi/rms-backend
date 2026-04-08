<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Table;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Order API', function () {
    beforeEach(function () {
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->cashier = User::factory()->create(['role' => 'cashier']);
        $this->kitchen = User::factory()->create(['role' => 'kitchen']);
        $this->table = Table::factory()->create();
    });

    it('requires authentication for orders index', function () {
        $response = $this->getJson('/api/orders');

        $response->assertStatus(401);
    });

    it('allows admin and cashier to view orders', function () {
        $response = $this->actingAs($this->admin)->getJson('/api/orders');

        $response->assertStatus(200);

        $response = $this->actingAs($this->cashier)->getJson('/api/orders');

        $response->assertStatus(200);
    });

    it('denies kitchen from viewing orders index', function () {
        $response = $this->actingAs($this->kitchen)->getJson('/api/orders');

        $response->assertStatus(403);
    });

    it('returns orders successfully', function () {
        Order::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)->getJson('/api/orders');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'items',
                    'meta' => ['total', 'per_page', 'current_page', 'last_page']
                ]
            ]);
    });

    it('shows single order to authenticated users', function () {
        $order = Order::factory()->create();

        $response = $this->actingAs($this->cashier)->getJson("/api/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['id' => $order->id]
            ]);
    });

    it('returns 404 for non-existent order', function () {
        $response = $this->actingAs($this->cashier)->getJson('/api/orders/999');

        $response->assertStatus(404);
    });

    it('allows cashier to view active order by table', function () {
        $order = Order::factory()->create(['table_id' => $this->table->id, 'status' => 'pending']);

        $response = $this->actingAs($this->cashier)->getJson("/api/orders/table/{$this->table->id}");

        $response->assertStatus(200)
            ->assertJson(['data' => ['id' => $order->id]]);
    });

    it('returns 404 when no active order for table', function () {
        $response = $this->actingAs($this->cashier)->getJson("/api/orders/table/{$this->table->id}");

        $response->assertStatus(404);
    });

    it('allows cashier to create order', function () {
        $data = [
            'table_id' => $this->table->id,
            'special_note' => 'Test note'
        ];

        $response = $this->actingAs($this->cashier)->postJson('/api/orders', $data);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'table' => ['id' => $this->table->id],
                    'user' => ['id' => $this->cashier->id],
                    'status' => 'pending'
                ]
            ]);
    });

    it('prevents creating order if table has active order', function () {
        Order::factory()->create(['table_id' => $this->table->id, 'status' => 'pending']);

        $data = ['table_id' => $this->table->id];

        $response = $this->actingAs($this->cashier)->postJson('/api/orders', $data);

        $response->assertStatus(422);
    });

    it('allows adding item to order', function () {
        $order = Order::factory()->create(['table_id' => $this->table->id]);
        $menuItem = \App\Models\MenuItem::factory()->create();

        $data = [
            'menu_item_id' => $menuItem->id,
            'quantity' => 2
        ];

        $response = $this->actingAs($this->cashier)->postJson("/api/orders/{$order->id}/items", $data);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'items' => [
                        [
                            'menu_item' => ['id' => $menuItem->id],
                            'quantity' => 2,
                            'price' => $menuItem->price
                        ]
                    ]
                ]
            ]);
    });

    it('increases quantity for same menu item', function () {
        $order = Order::factory()->create();
        $menuItem = \App\Models\MenuItem::factory()->create();
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
            'quantity' => 1
        ]);

        $data = ['menu_item_id' => $menuItem->id, 'quantity' => 3];

        $response = $this->actingAs($this->cashier)->postJson("/api/orders/{$order->id}/items", $data);

        $response->assertStatus(200);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
            'quantity' => 4
        ]);
    });

    it('allows kitchen to update status', function () {
        $order = Order::factory()->create(['status' => 'pending']);

        $response = $this->actingAs($this->kitchen)->patchJson("/api/orders/{$order->id}/status", [
            'status' => 'preparing'
        ]);

        $response->assertStatus(200)
            ->assertJson(['data' => ['status' => 'preparing']]);
    });

    it('validates status transitions', function () {
        $order = Order::factory()->create(['status' => 'pending']);

        $response = $this->actingAs($this->kitchen)->patchJson("/api/orders/{$order->id}/status", [
            'status' => 'served'
        ]);

        $response->assertStatus(422);
    });

    it('allows cashier to cancel order', function () {
        $order = Order::factory()->create(['status' => 'pending']);

        $response = $this->actingAs($this->cashier)->deleteJson("/api/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJson(['data' => ['status' => 'cancelled']]);
    });
});

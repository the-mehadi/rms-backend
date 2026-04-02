<?php

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('public can list active categories with pagination', function () {
    Category::factory()->count(12)->create(['is_active' => true]);
    Category::factory()->count(3)->inactive()->create();

    $response = $this->getJson('/api/categories?per_page=5');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Categories retrieved successfully.',
        ])
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'items',
                'meta' => ['total', 'per_page', 'current_page', 'last_page'],
            ],
        ]);

    expect($response->json('data.meta.per_page'))->toBe(5);
    expect($response->json('data.meta.total'))->toBe(12);
});

test('unauthenticated user cannot create category', function () {
    $this->postJson('/api/categories', ['name' => 'Beverages'])
        ->assertStatus(401)
        ->assertJson([
            'success' => false,
            'message' => 'Unauthenticated. Please login first.',
        ]);
});

test('non-admin cannot manage categories', function () {
    $cashier = User::factory()->cashier()->create(['is_active' => true]);
    Sanctum::actingAs($cashier);

    $this->postJson('/api/categories', ['name' => 'Beverages'])
        ->assertStatus(403)
        ->assertJson([
            'success' => false,
            'message' => 'Access denied. You do not have permission for this action.',
        ]);
});

test('admin can create category and slug is auto-generated', function () {
    $admin = User::factory()->admin()->create(['is_active' => true]);
    Sanctum::actingAs($admin);

    $response = $this->postJson('/api/categories', [
        'name' => 'Hot Drinks',
        'description' => 'Tea, coffee, and more.',
        'order' => 10,
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'Category created successfully.',
            'data' => [
                'name' => 'Hot Drinks',
                'slug' => 'hot-drinks',
                'order' => 10,
            ],
        ]);
});

test('admin can update category and slug regenerates when name changes', function () {
    $admin = User::factory()->admin()->create(['is_active' => true]);
    Sanctum::actingAs($admin);

    $category = Category::factory()->create([
        'name' => 'Soft Drinks',
        'slug' => 'soft-drinks',
    ]);

    $this->patchJson("/api/categories/{$category->id}", [
        'name' => 'Cold Drinks',
    ])->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Category updated successfully.',
            'data' => [
                'name' => 'Cold Drinks',
                'slug' => 'cold-drinks',
            ],
        ]);
});

test('admin can delete category (soft delete)', function () {
    $admin = User::factory()->admin()->create(['is_active' => true]);
    Sanctum::actingAs($admin);

    $category = Category::factory()->create();

    $this->deleteJson("/api/categories/{$category->id}")
        ->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Category deleted successfully.',
        ]);

    $this->assertSoftDeleted('categories', ['id' => $category->id]);
});


<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('unauthenticated user cannot list users', function () {
    $response = $this->getJson('/api/users');

    $response->assertStatus(401)
        ->assertJson([
            'success' => false,
            'message' => 'Unauthenticated. Please login first.',
        ]);
});

test('non-admin cannot access users endpoints', function () {
    $admin = User::factory()->admin()->create();
    $cashier = User::factory()->create([
        'role' => 'cashier',
        'is_active' => true,
    ]);

    Sanctum::actingAs($cashier);

    $this->getJson('/api/users')
        ->assertStatus(403)
        ->assertJson([
            'success' => false,
            'message' => 'Access denied. You do not have permission for this action.',
        ]);

    // Silence unused variable warning in case of static analyzers.
    expect($admin->id)->toBeInt();
});

test('admin can list users with pagination', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->count(15)->create(['role' => 'cashier']);

    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/users?per_page=5');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Users retrieved successfully.',
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
});

test('admin can create user and password is hashed', function () {
    $admin = User::factory()->admin()->create();

    $payload = [
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'password' => 'password123',
    ];

    Sanctum::actingAs($admin);

    $response = $this->postJson('/api/users', $payload);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'User created successfully.',
            'data' => [
                'email' => $payload['email'],
            ],
        ]);

    $created = User::where('email', $payload['email'])->firstOrFail();
    expect(Hash::check($payload['password'], $created->password))->toBeTrue();
});

test('admin can update user (email and password) and password is re-hashed', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create([
        'role' => 'cashier',
        'password' => Hash::make('oldpassword123'),
        'is_active' => true,
    ]);

    Sanctum::actingAs($admin);

    $payload = [
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
        'password' => 'newpassword123',
    ];

    $response = $this->putJson("/api/users/{$user->id}", $payload);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'User updated successfully.',
        ]);

    $user->refresh();
    expect($user->email)->toBe($payload['email']);
    expect(Hash::check($payload['password'], $user->password))->toBeTrue();
});

test('admin can update user without changing password', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create([
        'role' => 'cashier',
        'password' => Hash::make('oldpassword123'),
    ]);
    $oldPasswordHash = $user->password;

    Sanctum::actingAs($admin);

    $payload = [
        'name' => 'Only Name Update',
    ];

    $this->putJson("/api/users/{$user->id}", $payload)
        ->assertStatus(200);

    $user->refresh();
    expect($user->password)->toBe($oldPasswordHash);
});

test('admin can delete user', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create([
        'role' => 'cashier',
        'is_active' => true,
    ]);

    Sanctum::actingAs($admin);

    $this->deleteJson("/api/users/{$user->id}")
        ->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'User deleted successfully.',
        ]);

    expect(User::find($user->id))->toBeNull();
});


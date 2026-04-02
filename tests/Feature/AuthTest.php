<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

// ─── POST /api/auth/login ────────────────────────────────────────────────────
test('user can login with correct credentials', function () {
    $user = User::factory()->create([
        'email'     => 'admin@restaurant.com',
        'password'  => bcrypt('password123'),
        'role'      => 'admin',
        'is_active' => true,
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email'    => 'admin@restaurant.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
             ->assertJsonStructure([
                 'success',
                 'message',
                 'data' => [
                     'access_token',
                     'token_type',
                     'user' => ['id', 'name', 'email', 'role'],
                 ],
             ])
             ->assertJson([
                 'success' => true,
                 'message' => 'Login successful.',
             ]);
});

test('user cannot login with wrong password', function () {
    User::factory()->create([
        'email'    => 'admin@restaurant.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email'    => 'admin@restaurant.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(401)
             ->assertJson([
                 'success' => false,
                 'message' => 'Invalid email or password.',
             ]);
});

test('user cannot login with wrong email', function () {
    $response = $this->postJson('/api/auth/login', [
        'email'    => 'notexist@restaurant.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(401)
             ->assertJson([
                 'success' => false,
                 'message' => 'Invalid email or password.',
             ]);
});

test('login fails with validation error when email is missing', function () {
    $response = $this->postJson('/api/auth/login', [
        'password' => 'password123',
    ]);

    $response->assertStatus(422)
             ->assertJson([
                 'success' => false,
                 'message' => 'Validation failed.',
             ])
             ->assertJsonStructure([
                 'errors' => ['email'],
             ]);
});

test('login fails with validation error when password is missing', function () {
    $response = $this->postJson('/api/auth/login', [
        'email' => 'admin@restaurant.com',
    ]);

    $response->assertStatus(422)
             ->assertJson([
                 'success' => false,
                 'message' => 'Validation failed.',
             ])
             ->assertJsonStructure([
                 'errors' => ['password'],
             ]);
});

test('deactivated user cannot login', function () {
    User::factory()->create([
        'email'     => 'inactive@restaurant.com',
        'password'  => bcrypt('password123'),
        'is_active' => false,
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email'    => 'inactive@restaurant.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(403)
             ->assertJson([
                 'success' => false,
                 'message' => 'Your account has been deactivated. Contact admin.',
             ]);
});

// ─── GET /api/auth/me ────────────────────────────────────────────────────────
test('authenticated user can view their profile', function () {
    $user = User::factory()->create([
        'role' => 'cashier',
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/auth/me');

    $response->assertStatus(200)
             ->assertJson([
                 'success' => true,
                 'data'    => [
                     'id'    => $user->id,
                     'name'  => $user->name,
                     'email' => $user->email,
                     'role'  => 'cashier',
                 ],
             ]);
});

test('unauthenticated user cannot access me endpoint', function () {
    $response = $this->getJson('/api/auth/me');

    $response->assertStatus(401)
             ->assertJson([
                 'success' => false,
                 'message' => 'Unauthenticated. Please login first.',
             ]);
});

// ─── POST /api/auth/logout ───────────────────────────────────────────────────
test('authenticated user can logout', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/auth/logout');

    $response->assertStatus(200)
             ->assertJson([
                 'success' => true,
                 'message' => 'Logged out successfully.',
             ]);
});

test('user cannot access me endpoint after logout', function () {
    $user = User::factory()->create([
        'email'    => 'admin@restaurant.com',
        'password' => bcrypt('password123'),
    ]);

    // Login to get token
    $loginResponse = $this->withHeaders([
        'Accept' => 'application/json',
    ])->post('/api/auth/login', [
        'email' => 'admin@restaurant.com',
        'password' => 'password123',
    ]);

    $token = $loginResponse->json('data.access_token');

    // Logout
    $this->withHeader('Authorization', "Bearer $token")
         ->postJson('/api/auth/logout')
         ->assertStatus(200);

    // Clear Sanctum's token cache
    $this->flushHeaders();
    app()->forgetInstance('auth');
    auth()->forgetGuards();

    // Try to access /me with old token — should be 401
    $this->withHeader('Authorization', "Bearer $token")
         ->getJson('/api/auth/me')
         ->assertStatus(401);
});

test('unauthenticated user cannot logout', function () {
    $response = $this->postJson('/api/auth/logout');

    $response->assertStatus(401);
});

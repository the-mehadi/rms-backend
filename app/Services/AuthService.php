<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;


class AuthService
{
    // ─── Login Service ────────────────────────────────────────────────
    public function login(array $data): array
    {
        $user = User::where('email', $data['email'])->first();

        // Check credentials
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return [
                'success' => false,
                'message' => 'Invalid email or password.',
                'code'    => 401,
            ];
        }

        // Check if account is active
        if (! $user->is_active) {
            return [
                'success' => false,
                'message' => 'Your account has been deactivated. Contact admin.',
                'code'    => 403,
            ];
        }

        // Revoke old tokens (single session per user)
        $user->tokens()->delete();

        // Create new token with role-based abilities
        $token = $user->createToken(
            name: 'auth_token',
            abilities: [$user->role],
        )->plainTextToken;

        return [
            'success' => true,
            'message' => 'Login successful.',
            'code'    => 200,
            'data'    => [
                'access_token' => $token,
                'token_type'   => 'Bearer',
                'user'         => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'role'  => $user->role,
                ],
            ],
        ];
    }
    // ─── logout Service ────────────────────────────────────────────────
    public function logout(User $user): array
    {
        $user->currentAccessToken()->delete();

        return [
            'success' => true,
            'message' => 'Logged out successfully.',
            'code'    => 200,
        ];
    }

    // ─── Me Service ────────────────────────────────────────────────
    public function me(User $user): array
    {
        return [
            'success' => true,
            'message' => 'User details retrieved successfully.',
            'code'    => 200,
            'data'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
            ],
        ];
    }

}

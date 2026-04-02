<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function paginate(int $perPage): LengthAwarePaginator
    {
        return User::query()
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function createUser(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
    }

    public function updateUser(User $user, array $data): User
    {
        $updateData = [
            'name' => $data['name'],
        ];

        if (array_key_exists('email', $data)) {
            $updateData['email'] = $data['email'];
        }

        if (array_key_exists('password', $data)) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $user->update($updateData);

        return $user->fresh();
    }

    public function deleteUser(User $user): void
    {
        $user->delete();
    }
}


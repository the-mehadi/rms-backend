<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name'      => 'Admin User',
                'email'     => 'admin@restaurant.com',
                'password'  => Hash::make('password123'),
                'role'      => 'admin',
                'is_active' => true,
            ],
            [
                'name'      => 'Cashier User',
                'email'     => 'cashier@restaurant.com',
                'password'  => Hash::make('password123'),
                'role'      => 'cashier',
                'is_active' => true,
            ],
            [
                'name'      => 'Kitchen Staff',
                'email'     => 'kitchen@restaurant.com',
                'password'  => Hash::make('password123'),
                'role'      => 'kitchen',
                'is_active' => true,
            ],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }

        $this->command->info('✅ Users seeded: admin, cashier, kitchen');
    }
}

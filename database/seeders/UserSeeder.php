<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@vape.com',
                'password' => bcrypt('password'),
                'role' => UserRole::ADMIN,
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Kasir User',
                'email' => 'kasir@vape.com',
                'password' => bcrypt('password'),
                'role' => UserRole::CASHIER,
                'email_verified_at' => now(),
            ],
        ];

        foreach ($users as $userData) {
            User::create($userData);
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@sansworks.com'],
            [
                'name' => 'System Administrator',
                'username' => 'admin',
                'email' => 'admin@sansworks.com',
                'phone' => '+1234567890',
                'password' => bcrypt('admin123'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        User::firstOrCreate(
            ['email' => 'manager@sansworks.com'],
            [
                'name' => 'Production Manager',
                'username' => 'manager',
                'email' => 'manager@sansworks.com',
                'phone' => '+1234567891',
                'password' => bcrypt('manager123'),
                'role' => 'manager',
                'is_active' => true,
            ]
        );
    }
}

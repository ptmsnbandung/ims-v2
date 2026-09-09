<?php

namespace Database\Seeders;

use App\Enums\UserRole;
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
                'name' => 'Rian Pratama (Teknik Drafter)',
                'email' => 'teknik@ims.test',
                'password' => Hash::make('password'),
                'role' => UserRole::TEKNIK,
                'phone' => '081234567891',
                'is_active' => true,
            ],
            [
                'name' => 'Dimas Arya (NOC Engineer)',
                'email' => 'noc@ims.test',
                'password' => Hash::make('password'),
                'role' => UserRole::NOC,
                'phone' => '081234567892',
                'is_active' => true,
            ],
            [
                'name' => 'Siti Rahma (Finance Officer)',
                'email' => 'finance@ims.test',
                'password' => Hash::make('password'),
                'role' => UserRole::FINANCE,
                'phone' => '081234567893',
                'is_active' => true,
            ],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }
    }
}

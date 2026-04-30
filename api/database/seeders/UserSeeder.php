<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Administrator',
                'email' => 'admin@example.com',
                'password' => bcrypt('password'),
                // 'role' => 'admin',
            ],
            [
                'name' => 'Verifikator',
                'email' => 'verifikator@example.com',
                'password' => bcrypt('password'),
                // 'role' => 'verifikator',
            ],
            [
                'name' => 'Operator',
                'email' => 'operator@example.com',
                'password' => bcrypt('password'),
                // 'role' => 'operator',
            ],
        ];

        foreach ($users as $row) {
            User::firstOrCreate(
                ['email' => $row['email']],
                $row
            );
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('admin_accounts')->updateOrInsert(
            ['email' => 'admin@example.com'], // UNIQUE check
            [
                'username' => 'admin',
                'password' => Hash::make('password123'),
                'full_name' => 'Admin',
                'role' => 'super_admin',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}

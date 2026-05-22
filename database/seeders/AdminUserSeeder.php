<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@hrportal.com'],
            [
                'name'     => 'System Admin',
                'email'    => 'admin@hrportal.com',
                'password' => Hash::make('Admin@123'),
                'status'   => 'active',
            ]
        );

        $admin->assignRole('admin');

        // HR User
        $hr = User::firstOrCreate(
            ['email' => 'hr@hrportal.com'],
            [
                'name'     => 'HR Manager',
                'email'    => 'hr@hrportal.com',
                'password' => Hash::make('Hr@123456'),
                'status'   => 'active',
            ]
        );
        $hr->assignRole('hr');

        $this->command->info('Admin and HR users seeded.');
        $this->command->info('Admin: admin@hrportal.com / Admin@123');
        $this->command->info('HR:    hr@hrportal.com    / Hr@123456');
    }
}
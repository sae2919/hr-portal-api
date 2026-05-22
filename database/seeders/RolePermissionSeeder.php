<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ── Define all permissions ─────────────────────────────────
        $permissions = [
            // Employees
            'view employees',
            'create employees',
            'edit employees',
            'delete employees',
            'view own profile',

            // Departments
            'view departments',
            'create departments',
            'edit departments',
            'delete departments',

            // Designations
            'view designations',
            'create designations',
            'edit designations',
            'delete designations',

            // Attendance
            'view attendance',
            'manage attendance',
            'view own attendance',
            'checkin checkout',

            // Leaves
            'view leaves',
            'apply leave',
            'approve leave',
            'reject leave',
            'manage leave types',
            'view own leaves',

            // Payroll
            'view payroll',
            'process payroll',
            'view own payslip',
            'manage salary structures',

            // Recruitment
            'view recruitment',
            'manage recruitment',
            'schedule interviews',

            // Users
            'view users',
            'create users',
            'edit users',
            'delete users',
            'assign roles',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // ── Create Roles ───────────────────────────────────────────

        // Admin — everything
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions(Permission::all());

        // HR — most things except system-level user management
        $hr = Role::firstOrCreate(['name' => 'hr']);
        $hr->syncPermissions([
            'view employees', 'create employees', 'edit employees',
            'view departments', 'create departments', 'edit departments',
            'view designations', 'create designations', 'edit designations',
            'view attendance', 'manage attendance', 'checkin checkout',
            'view leaves', 'apply leave', 'approve leave', 'reject leave', 'manage leave types',
            'view payroll', 'process payroll', 'manage salary structures',
            'view recruitment', 'manage recruitment', 'schedule interviews',
            'view own profile', 'view own attendance', 'view own leaves', 'view own payslip',
        ]);

        // Manager — team oversight
        $manager = Role::firstOrCreate(['name' => 'manager']);
        $manager->syncPermissions([
            'view employees',
            'view departments',
            'view designations',
            'view attendance', 'checkin checkout',
            'view leaves', 'apply leave', 'approve leave', 'reject leave',
            'view own profile', 'view own attendance', 'view own leaves', 'view own payslip',
            'schedule interviews',
        ]);

        // Employee — self-service only
        $employee = Role::firstOrCreate(['name' => 'employee']);
        $employee->syncPermissions([
            'view own profile',
            'checkin checkout',
            'view own attendance',
            'apply leave',
            'view own leaves',
            'view own payslip',
        ]);

        $this->command->info('Roles and permissions seeded successfully.');
    }
}
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

        // ── Define all permissions ─────────────────────────────────────
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
            'view all attendance',
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

        // ── Admin — everything ─────────────────────────────────────────
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions(Permission::all());

        // ── HR — most things except system-level user management ───────
        $hr = Role::firstOrCreate(['name' => 'hr']);
        $hr->syncPermissions([
            'view employees', 'create employees', 'edit employees',
            'view departments', 'create departments', 'edit departments',
            'view designations', 'create designations', 'edit designations',
            'view attendance', 'manage attendance', 'view all attendance', 'checkin checkout',
            'view leaves', 'apply leave', 'approve leave', 'reject leave', 'manage leave types',
            'view payroll', 'process payroll', 'manage salary structures',
            'view recruitment', 'manage recruitment', 'schedule interviews',
            'view own profile', 'view own attendance', 'view own leaves', 'view own payslip',
        ]);

        // ── Manager — team oversight ───────────────────────────────────
        $manager = Role::firstOrCreate(['name' => 'manager']);
        $manager->syncPermissions([
            'view employees',
            'view departments',
            'view designations',
            'view attendance', 'manage attendance', 'checkin checkout',
            'view leaves', 'apply leave', 'approve leave', 'reject leave',
            'view own profile', 'view own attendance', 'view own leaves', 'view own payslip',
            'schedule interviews',
        ]);

        // ── TeamLead — team-level oversight, no admin functions ────────
        // Can monitor their team's attendance & leaves, cannot touch
        // departments, designations, payroll, or recruitment pipelines.
        $teamLead = Role::firstOrCreate(['name' => 'team_lead']);
        $teamLead->syncPermissions([
            // Employees (read-only, own team)
            'view employees',

            // Attendance (can view & manage their team's records)
            'view attendance',
            'manage attendance',
            'checkin checkout',

            // Leaves (can view, apply, approve/reject for their team)
            'view leaves',
            'apply leave',
            'approve leave',
            'reject leave',

            // Self-service
            'view own profile',
            'view own attendance',
            'view own leaves',
            'view own payslip',
        ]);

        // ── SalesManager — sales-focused role with broader visibility ──
        // Can recruit, view payroll summaries, create/edit employees in
        // their domain, and manage the full sales team lifecycle.
        $salesManager = Role::firstOrCreate(['name' => 'sales_manager']);
        $salesManager->syncPermissions([
            // Employees (can create & edit, not delete)
            'view employees',
            'create employees',
            'edit employees',

            // Departments & Designations (read-only)
            'view departments',
            'view designations',

            // Attendance (full team management)
            'view attendance',
            'manage attendance',
            'checkin checkout',

            // Leaves (full team management)
            'view leaves',
            'apply leave',
            'approve leave',
            'reject leave',

            // Payroll (read-only visibility, no processing)
            'view payroll',
            'view own payslip',

            // Recruitment (full pipeline access)
            'view recruitment',
            'manage recruitment',
            'schedule interviews',

            // Self-service
            'view own profile',
            'view own attendance',
            'view own leaves',
        ]);

        // ── Employee — self-service only ───────────────────────────────
        $employee = Role::firstOrCreate(['name' => 'employee']);
        $employee->syncPermissions([
            'view own profile',
            'checkin checkout',
            'view own attendance',
            'apply leave',
            'view own leaves',
            'view own payslip',
        ]);

        $this->command->info('✅ Roles and permissions seeded successfully.');
        $this->command->table(
            ['Role', 'Permissions Count'],
            [
                ['admin',         $admin->permissions()->count()],
                ['hr',            $hr->permissions()->count()],
                ['manager',       $manager->permissions()->count()],
                ['team_lead',     $teamLead->permissions()->count()],
                ['sales_manager', $salesManager->permissions()->count()],
                ['employee',      $employee->permissions()->count()],
            ]
        );
    }
}
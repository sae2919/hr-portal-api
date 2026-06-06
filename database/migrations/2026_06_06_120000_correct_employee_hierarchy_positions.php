<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Update Vishwanath Srirangam (CEO AND FOUNDER, EMP1000)
        DB::table('employees')->where('employee_code', 'EMP1000')->update([
            'position_level' => 'c_level',
            'hierarchy_level' => 1,
        ]);

        // 2. Update Guntuku Akanksha (EMP1011) to be HR Manager (designation 6) and Manager (position level)
        DB::table('employees')->where('employee_code', 'EMP1011')->update([
            'designation_id' => 6, // HR Manager
            'position_level' => 'manager',
            'hierarchy_level' => 3,
        ]);

        // 3. Update Shakti Sharma (EMP1001, Sales head) to team_lead
        DB::table('employees')->where('employee_code', 'EMP1001')->update([
            'position_level' => 'team_lead',
            'hierarchy_level' => 4,
        ]);

        // 4. Update Vasa Raviteja (EMP1024, SEO lead) to team_lead
        DB::table('employees')->where('employee_code', 'EMP1024')->update([
            'position_level' => 'team_lead',
            'hierarchy_level' => 4,
        ]);

        // 5. Update Santosh Asole (EMP1019, Tech Lead) to team_lead
        DB::table('employees')->where('employee_code', 'EMP1019')->update([
            'position_level' => 'team_lead',
            'hierarchy_level' => 4,
        ]);

        // 6. Update Ladhwe Navaneetha (EMP1026, content creater lead) to team_lead
        DB::table('employees')->where('employee_code', 'EMP1026')->update([
            'position_level' => 'team_lead',
            'hierarchy_level' => 4,
        ]);

        // 7. Update Durga devi (TEMP_DURGA_DE) reporting_to to Shakti Sharma (EMP1001)
        $shakti = DB::table('employees')->where('employee_code', 'EMP1001')->first();
        if ($shakti) {
            DB::table('employees')->where('employee_code', 'TEMP_DURGA_DE')->update([
                'reporting_to' => $shakti->id,
            ]);
        }

        // 8. Update Guna sahasara (EMP0024) reporting_to to Vishwanath Srirangam (EMP1000)
        $ceo = DB::table('employees')->where('employee_code', 'EMP1000')->first();
        if ($ceo) {
            DB::table('employees')->where('employee_code', 'EMP0024')->update([
                'reporting_to' => $ceo->id,
            ]);
        }

        // 9. Rebuild hierarchy paths for all employees
        foreach (Employee::all() as $emp) {
            $emp->updateHierarchyPath();
        }
    }

    public function down(): void
    {
        // Rollback is no-op
    }
};

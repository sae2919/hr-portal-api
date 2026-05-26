<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\SalaryStructure;
use Illuminate\Database\Seeder;

class PayrollSeeder extends Seeder
{
    public function run(): void
    {
        Employee::all()->each(function ($employee, $index) {

            // =========================================
            // Base Salary Logic
            // =========================================

            $basic = 35000 + ($index * 10000);

            $hra = $basic * 0.40;

            $allowances = 5000;

            $bonus = 2000;

            $gross = $basic + $hra + $allowances + $bonus;

            // =========================================
            // Attendance Logic
            // =========================================

            $workingDays = 22;

            $presentDays = Attendance::where(
                'employee_id',
                $employee->id
            )
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

            // Demo fallback

            if ($presentDays === 0) {
                $presentDays = rand(18, 22);
            }

            $leaveDays = $workingDays - $presentDays;

            // =========================================
            // Per Day Salary
            // =========================================

            $perDaySalary = $gross / $workingDays;

            $earnedSalary = $perDaySalary * $presentDays;

            // =========================================
            // Deductions
            // =========================================

            $pf = $basic * 0.12;

            $tax = $basic * 0.10;

            $totalDeductions = $pf + $tax;

            // =========================================
            // Final Net Salary
            // =========================================

            $net = $earnedSalary - $totalDeductions;

            // =========================================
            // Salary Structure
            // =========================================

            $salary = SalaryStructure::updateOrCreate(

                [
                    'employee_id' => $employee->id,
                ],

                [
                    'basic_salary' => $basic,

                    'hra' => $hra,

                    'allowances' => $allowances,

                    'bonus' => $bonus,

                    'pf_deduction' => $pf,

                    'tax_deduction' => $tax,

                    'other_deductions' => 0,

                    'gross_salary' => $gross,

                    'net_salary' => $net,

                    'effective_from' => now(),
                ]
            );

            // =========================================
            // Payroll
            // =========================================

            Payroll::updateOrCreate(

                [
                    'employee_id' => $employee->id,
                    'month' => now()->month,
                    'year' => now()->year,
                ],

                [
                    'salary_structure_id' => $salary->id,

                    'working_days' => $workingDays,

                    'present_days' => $presentDays,

                    'leave_days' => $leaveDays,

                    'gross_salary' => $gross,

                    'total_deductions' => $totalDeductions,

                    'net_salary' => round($net),

                    'status' => 'processed',

                    'processed_at' => now(),
                ]
            );
        });
    }
}
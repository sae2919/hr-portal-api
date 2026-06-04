<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\CompanySetting;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\SalaryStructure;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateMonthlyPayroll extends Command
{
    /**
     * Run automatically at month end, or manually:
     *   php artisan payroll:generate-monthly
     *   php artisan payroll:generate-monthly --month=5 --year=2026
     */
    protected $signature = 'payroll:generate-monthly
                            {--month= : Month number (1–12). Defaults to current month.}
                            {--year=  : Year. Defaults to current year.}';

    protected $description = 'Auto-generate payroll for all active employees at month end.';

    public function handle(): int
    {
        $month = (int) ($this->option('month') ?: now()->month);
        $year  = (int) ($this->option('year')  ?: now()->year);

        // Read company-level PF + PT settings once for the entire run
        $pfEnabled    = CompanySetting::isEnabled('pf_enabled');
        $ptEnabled    = CompanySetting::isEnabled('pt_enabled');
        $pfPercentage = (float) (CompanySetting::getValue('pf_percentage') ?? 12);

        $this->info("=== Auto Payroll Generation: {$month}/{$year} ===");
        $this->info("  PF: " . ($pfEnabled ? "Enabled ({$pfPercentage}%)" : 'Disabled') . " | PT: " . ($ptEnabled ? 'Enabled' : 'Disabled'));

        // Fetch all employees who have an active salary structure
        $employeeIds = SalaryStructure::where('status', 'active')
                        ->pluck('employee_id')
                        ->unique();

        $employees = Employee::whereIn('id', $employeeIds)->get();

        if ($employees->isEmpty()) {
            $this->warn('No employees with active salary structures found.');
            return self::SUCCESS;
        }

        $generated = 0;
        $skipped   = 0;
        $failed    = 0;

        foreach ($employees as $employee) {
            // Skip if payroll already exists for this month
            $exists = Payroll::where('employee_id', $employee->id)
                             ->where('month', $month)
                             ->where('year',  $year)
                             ->exists();

            if ($exists) {
                $this->line("  <fg=yellow>SKIP</> {$employee->first_name} {$employee->last_name} — already generated.");
                $skipped++;
                continue;
            }

            try {
                $this->generateForEmployee($employee, $month, $year, $pfEnabled, $ptEnabled, $pfPercentage);
                $this->line("  <fg=green>DONE</> {$employee->first_name} {$employee->last_name}");
                $generated++;
            } catch (\Throwable $e) {
                $this->line("  <fg=red>FAIL</> {$employee->first_name} {$employee->last_name} — {$e->getMessage()}");
                Log::error("Auto payroll failed for employee {$employee->id}: " . $e->getMessage());
                $failed++;
            }
        }

        $this->newLine();
        $this->info("Done. Generated: {$generated} | Skipped: {$skipped} | Failed: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    // ─────────────────────────────────────────────────────────────
    //  Core calculation
    //  $pfEnabled / $ptEnabled come from company_settings table.
    //  $pfPercentage: PF is calculated as basic_salary × pfPercentage / 100
    //  If PF is disabled company-wide, it is zeroed out regardless
    //  of what is stored in the employee's salary structure.
    // ─────────────────────────────────────────────────────────────
    private function generateForEmployee(
        Employee $employee,
        int $month,
        int $year,
        bool $pfEnabled,
        bool $ptEnabled,
        float $pfPercentage = 12.0
    ): void {
        $employeeId = $employee->id;

        // ── 1. Active salary structure ────────────────────────────
        $salary = SalaryStructure::where('employee_id', $employeeId)
                    ->where('status', 'active')
                    ->latest('effective_from')
                    ->firstOrFail();

        // ── 2. Month boundaries ───────────────────────────────────
        $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
        $endOfMonth   = Carbon::create($year, $month, 1)->endOfMonth();
        $periodEnd    = $endOfMonth->lt(Carbon::today()) ? $endOfMonth : Carbon::today();

        // ── 3. Working days (Mon–Sat + custom Sat weekoffs) ───────
        $workingDays = 0;
        for ($d = $startOfMonth->copy(); $d->lte($periodEnd); $d->addDay()) {
            if (!\App\Models\Leave::isWeekOff($d)) $workingDays++;
        }

        // ── 4. Attendance ─────────────────────────────────────────
        $attendance  = Attendance::where('employee_id', $employeeId)
                        ->whereMonth('date', $month)
                        ->whereYear('date',  $year)
                        ->get();

        $presentDays = $attendance->whereIn('status', ['present', 'late'])->count();
        $halfDays    = $attendance->where('status', 'half_day')->count();

        // ── 5. Approved leaves ────────────────────────────────────
        $approvedLeaves = Leave::with('leaveType')
            ->where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->where(fn($q) =>
                $q->where('start_date', '<=', $endOfMonth)
                  ->where('end_date',   '>=', $startOfMonth)
            )->get();

        $paidLeaveDays   = 0;
        $unpaidLeaveDays = 0;

        foreach ($approvedLeaves as $leave) {
            $leaveStart = Carbon::parse($leave->start_date)->max($startOfMonth);
            $leaveEnd   = Carbon::parse($leave->end_date)->min($endOfMonth);
            if ($leaveStart->gt($leaveEnd)) continue;

            $leaveDays = 0;
            for ($d = $leaveStart->copy(); $d->lte($leaveEnd); $d->addDay()) {
                if (!\App\Models\Leave::isWeekOff($d)) $leaveDays++;
            }
            if ($leaveDays === 0) continue;

            $isPaid = (bool) ($leave->leaveType->is_paid ?? false);
            $isPaid ? $paidLeaveDays += $leaveDays : $unpaidLeaveDays += $leaveDays;
        }

        // ── 6. LOP ────────────────────────────────────────────────
        $accountedDays  = $presentDays + ($halfDays * 0.5) + $paidLeaveDays;
        $absentDays     = max(0, $workingDays - $accountedDays - $unpaidLeaveDays);
        $lopDays        = $absentDays + $unpaidLeaveDays;
        $totalLeaveDays = $paidLeaveDays + $unpaidLeaveDays;

        // ── 7. Earnings ───────────────────────────────────────────
        $basic       = (float) $salary->basic_salary;
        $hra         = (float) $salary->hra;
        $allowances  = (float) $salary->allowances;
        $bonus       = (float) $salary->bonus;
        $grossSalary = $basic + $hra + $allowances + $bonus;

        // ── 8. Deductions ─────────────────────────────────────────
        // PF is calculated as a percentage of basic salary.
        // The percentage is read from company_settings (default 12%).
        // If PF is disabled globally, it is zeroed out.
        $pfDeduction     = $pfEnabled ? round($basic * $pfPercentage / 100, 2) : 0;
        $taxDeduction    = $ptEnabled ? (float) $salary->tax_deduction : 0;
        $otherDeductions = (float) $salary->other_deductions;
        $dailyRate       = $workingDays > 0 ? $basic / $workingDays : 0;
        $lopDeduction    = round($dailyRate * $lopDays, 2);
        $totalDeductions = $pfDeduction + $taxDeduction + $otherDeductions + $lopDeduction;

        // ── 9. Net ────────────────────────────────────────────────
        $netSalary = round($grossSalary - $totalDeductions, 2);

        // ── 10. Persist ───────────────────────────────────────────
        DB::transaction(function () use (
            $employeeId, $salary, $month, $year,
            $workingDays, $presentDays, $totalLeaveDays, $lopDays,
            $paidLeaveDays, $unpaidLeaveDays, $absentDays,
            $grossSalary, $totalDeductions, $netSalary,
            $basic, $hra, $allowances, $bonus,
            $pfDeduction, $pfPercentage, $taxDeduction, $otherDeductions, $lopDeduction
        ) {
            $payroll = Payroll::create([
                'employee_id'         => $employeeId,
                'salary_structure_id' => $salary->id,
                'month'               => $month,
                'year'                => $year,
                'working_days'        => $workingDays,
                'present_days'        => $presentDays,
                'leave_days'          => $totalLeaveDays,
                'gross_salary'        => $grossSalary,
                'total_deductions'    => $totalDeductions,
                'net_salary'          => $netSalary,
                'status'              => 'processed',
                'processed_at'        => now(),
            ]);

            $items = [
                ['name' => 'Basic Salary', 'type' => 'earning', 'amount' => $basic],
                ['name' => 'HRA',          'type' => 'earning', 'amount' => $hra],
                ['name' => 'Allowances',   'type' => 'earning', 'amount' => $allowances],
            ];
            if ($bonus > 0)           $items[] = ['name' => 'Bonus',                                   'type' => 'earning',   'amount' => $bonus];
            if ($pfDeduction > 0)     $items[] = ['name' => "Provident Fund ({$pfPercentage}%)",        'type' => 'deduction', 'amount' => $pfDeduction];
            if ($taxDeduction > 0)    $items[] = ['name' => 'Professional Tax',                         'type' => 'deduction', 'amount' => $taxDeduction];
            if ($otherDeductions > 0) $items[] = ['name' => 'Other Deductions',                         'type' => 'deduction', 'amount' => $otherDeductions];
            if ($unpaidLeaveDays > 0) $items[] = ['name' => "Unpaid Leave ({$unpaidLeaveDays} days)",   'type' => 'deduction', 'amount' => round(($lopDeduction / max($lopDays, 1)) * $unpaidLeaveDays, 2)];
            if ($absentDays > 0)      $items[] = ['name' => "Absent / LOP ({$absentDays} days)",        'type' => 'deduction', 'amount' => round(($lopDeduction / max($lopDays, 1)) * $absentDays, 2)];

            foreach ($items as $item) {
                PayrollItem::create([
                    'payroll_id' => $payroll->id,
                    'name'       => $item['name'],
                    'type'       => $item['type'],
                    'amount'     => $item['amount'],
                ]);
            }
        });
    }
}
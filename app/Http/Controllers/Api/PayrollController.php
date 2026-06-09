<?php

namespace App\Http\Controllers\Api;

use App\Mail\EmployeePayslipMail;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\CompanySetting;
use App\Models\Leave;
use App\Models\Payroll;
use App\Models\PayrollItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;

class PayrollController extends Controller
{
    /**
     * Get payrolls with enhanced filtering
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        $query = Payroll::with(['employee.department', 'employee.designation', 'salaryStructure']);

        // Role-based access
        if ($user->role !== 'admin') {
            if ($user->employee) {
                $query->where('employee_id', $user->employee->id);
            } else {
                return response()->json(['data' => [], 'message' => 'No employee record linked.'], 200);
            }
        }

        // Apply filters
        if ($request->month) {
            $query->where('month', $request->month);
        }
        
        if ($request->year) {
            $query->where('year', $request->year);
        }
        
        if ($request->status) {
            $query->where('status', $request->status);
        }
        
        if ($request->employee_id && $user->role === 'admin') {
            $query->where('employee_id', $request->employee_id);
        }
        
        if ($request->department_id && $user->role === 'admin') {
            $query->whereHas('employee', function($q) use ($request) {
                $q->where('department_id', $request->department_id);
            });
        }
        
        // Date range filter
        if ($request->start_month && $request->start_year) {
            $query->where(function($q) use ($request) {
                $q->where('year', '>', $request->start_year)
                  ->orWhere(function($q2) use ($request) {
                      $q2->where('year', $request->start_year)
                         ->where('month', '>=', $request->start_month);
                  });
            });
        }
        
        if ($request->end_month && $request->end_year) {
            $query->where(function($q) use ($request) {
                $q->where('year', '<', $request->end_year)
                  ->orWhere(function($q2) use ($request) {
                      $q2->where('year', $request->end_year)
                         ->where('month', '<=', $request->end_month);
                  });
            });
        }

        $sortBy = $request->sort_by ?? 'created_at';
        $sortOrder = $request->sort_order ?? 'desc';
        
        if (in_array($sortBy, ['month', 'year', 'created_at', 'net_salary', 'gross_salary', 'status'])) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $perPage = $request->per_page ?? 10;
        $payrolls = $query->paginate($perPage);

        return response()->json($payrolls);
    }

    /**
     * Get payroll summary statistics for dashboard
     */
    public function summary(Request $request): JsonResponse
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $year = $request->year ?? Carbon::now()->year;
        
        $summary = [
            'total_processed' => Payroll::where('year', $year)->count(),
            'total_paid' => Payroll::where('year', $year)->where('status', 'paid')->count(),
            'total_pending' => Payroll::where('year', $year)->where('status', 'processed')->count(),
            'total_net_amount' => (float) Payroll::where('year', $year)->sum('net_salary'),
            'total_gross_amount' => (float) Payroll::where('year', $year)->sum('gross_salary'),
            'total_deductions' => (float) Payroll::where('year', $year)->sum('total_deductions'),
            'average_net_salary' => (float) Payroll::where('year', $year)->avg('net_salary') ?? 0,
            'monthly_breakdown' => Payroll::where('year', $year)
                ->select('month', DB::raw('SUM(net_salary) as total'), DB::raw('COUNT(*) as count'))
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->map(function($item) {
                    return [
                        'month' => $item->month,
                        'month_name' => date('F', mktime(0, 0, 0, $item->month, 1)),
                        'total' => (float) $item->total,
                        'count' => $item->count
                    ];
                }),
            'department_breakdown' => Payroll::where('year', $year)
                ->join('employees', 'payrolls.employee_id', '=', 'employees.id')
                ->join('departments', 'employees.department_id', '=', 'departments.id')
                ->select('departments.name', DB::raw('SUM(payrolls.net_salary) as total'), DB::raw('COUNT(*) as count'))
                ->groupBy('departments.name')
                ->get()
                ->map(function($item) {
                    return [
                        'department' => $item->name,
                        'total' => (float) $item->total,
                        'count' => $item->count
                    ];
                }),
            'yearly_trend' => Payroll::select(
                    DB::raw('YEAR(created_at) as year'), 
                    DB::raw('MONTH(created_at) as month'),
                    DB::raw('SUM(net_salary) as total')
                )
                ->whereYear('created_at', '>=', $year - 1)
                ->groupBy('year', 'month')
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->limit(12)
                ->get()
                ->map(function($item) {
                    return [
                        'year' => $item->year,
                        'month' => $item->month,
                        'month_name' => date('F', mktime(0, 0, 0, $item->month, 1)),
                        'total' => (float) $item->total
                    ];
                })
        ];

        return response()->json($summary);
    }

    /**
     * Get payroll items for payslip modal
     */
    public function items(Payroll $payroll): JsonResponse
    {
        $user = auth()->user();

        if ($user->role !== 'admin') {
            $employeeId = $user->employee->id ?? null;
            if ($payroll->employee_id !== $employeeId) {
                return response()->json(['message' => 'Unauthorized.'], 403);
            }
        }

        $payroll->load(['employee.department', 'employee.designation', 'salaryStructure', 'items']);

        return response()->json([
            'data'  => $payroll,
            'items' => $payroll->items,
        ]);
    }

    /**
     * Bulk mark payrolls as paid
     */
    public function bulkMarkPaid(Request $request): JsonResponse
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:payrolls,id'],
        ]);

        $updated = Payroll::whereIn('id', $request->ids)
            ->where('status', '!=', 'paid')
            ->update(['status' => 'paid', 'paid_at' => now()]);

        return response()->json([
            'message' => "{$updated} payroll(s) marked as paid.",
            'updated' => $updated,
        ]);
    }

    /**
     * Bulk send emails for multiple payrolls
     */
    public function bulkSendEmail(Request $request): JsonResponse
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:payrolls,id'],
        ]);

        $sent   = 0;
        $failed = [];

        foreach ($request->ids as $id) {
            try {
                $payroll = Payroll::with(['employee.user'])->findOrFail($id);
                $employeeEmail = $payroll->employee->user->email ?? null;
                $employeeName  = $payroll->employee->first_name . ' ' . $payroll->employee->last_name;

                if (!$employeeEmail) {
                    $failed[] = ['id' => $id, 'reason' => "No email for {$employeeName}"];
                    continue;
                }

                $variables = \App\Services\DocumentService::getPayslipVariables($payroll, $payroll->employee);
                $pdf = \App\Services\DocumentService::render('monthly_payslip_template', $variables);
                $monthName = date("F", mktime(0, 0, 0, $payroll->month, 10));

                \App\Services\MailService::sendTemplateMail(
                    $employeeEmail,
                    'employee_payslip_delivery',
                    [
                        'name' => $payroll->employee->full_name,
                        'employee_name' => $payroll->employee->full_name,
                        'month' => $monthName,
                        'year' => $payroll->year,
                        'net_salary' => $payroll->net_salary,
                    ],
                    [
                        [
                            'data' => $pdf->output(),
                            'name' => "Payslip-{$monthName}-{$payroll->year}.pdf",
                            'mime' => 'application/pdf',
                        ]
                    ]
                );
                $sent++;
            } catch (\Exception $e) {
                Log::error("Bulk email failed for payroll {$id}: " . $e->getMessage());
                $failed[] = ['id' => $id, 'reason' => $e->getMessage()];
            }
        }

        return response()->json([
            'message' => "{$sent} email(s) sent." . (count($failed) ? ' ' . count($failed) . ' failed.' : ''),
            'sent'    => $sent,
            'failed'  => $failed,
        ]);
    }

    /**
     * Generate payroll for an employee
     */
    public function generate(Request $request): JsonResponse
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'employee_id'   => ['required', 'exists:employees,id'],
            'month'         => ['required', 'integer', 'min:1', 'max:12'],
            'year'          => ['required', 'integer', 'min:2000'],
            'include_pf'    => ['sometimes', 'boolean'],
            'include_pt'    => ['sometimes', 'boolean'],
            'pf_percentage' => ['sometimes', 'numeric', 'min:0', 'max:12'],
            'pt_amount'     => ['sometimes', 'numeric', 'min:0', 'max:500'],
        ]);

        $employeeId = $request->employee_id;
        $month      = (int) $request->month;
        $year       = (int) $request->year;

        $employee = \App\Models\Employee::findOrFail($employeeId);
        $isIntern = $employee->employment_type === 'intern';

        // Check if payroll already exists
        if (Payroll::where('employee_id', $employeeId)
            ->where('month', $month)
            ->where('year', $year)
            ->exists()) {
            return response()->json(['message' => "Payroll already generated for {$month}/{$year}."], 422);
        }

        // Get salary structure
        $salary = \App\Models\SalaryStructure::where('employee_id', $employeeId)
            ->where('status', 'active')
            ->latest('effective_from')
            ->first();

        if (!$salary) {
            return response()->json(['message' => 'No active salary structure found. Please create one first.'], 422);
        }

        // Calculate working days
        $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
        $endOfMonth   = Carbon::create($year, $month, 1)->endOfMonth();
        $periodEnd    = $endOfMonth->lt(Carbon::today()) ? $endOfMonth : Carbon::today();

        $workingDays = 0;
        for ($d = $startOfMonth->copy(); $d->lte($periodEnd); $d->addDay()) {
            if (!\App\Models\Leave::isWeekOff($d)) $workingDays++;
        }

        // Get attendance
        $attendance = Attendance::where('employee_id', $employeeId)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->get();
            
        $presentDays = $attendance->whereIn('status', ['present', 'late'])->count();
        $halfDays    = $attendance->where('status', 'half_day')->count();

        // Calculate leave days
        $approvedLeaves = Leave::with('leaveType')
            ->where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->where(function ($q) use ($startOfMonth, $endOfMonth) {
                $q->where('start_date', '<=', $endOfMonth)
                  ->where('end_date', '>=', $startOfMonth);
            })->get();

        $paidLeaveDays = $unpaidLeaveDays = 0;

        foreach ($approvedLeaves as $leave) {
            $leaveStart = Carbon::parse($leave->start_date)->max($startOfMonth);
            $leaveEnd   = Carbon::parse($leave->end_date)->min($endOfMonth);
            if ($leaveStart->gt($leaveEnd)) continue;
            
            $leaveDays = 0;
            for ($d = $leaveStart->copy(); $d->lte($leaveEnd); $d->addDay()) {
                if (!\App\Models\Leave::isWeekOff($d)) $leaveDays++;
            }
            if ($leaveDays === 0) continue;
            
            ($leave->leave_type->is_paid ?? false) 
                ? $paidLeaveDays += $leaveDays 
                : $unpaidLeaveDays += $leaveDays;
        }

        $accountedDays  = $presentDays + ($halfDays * 0.5) + $paidLeaveDays;
        $absentDays     = max(0, $workingDays - $accountedDays - $unpaidLeaveDays);
        $lopDays        = $absentDays + $unpaidLeaveDays;
        $totalLeaveDays = $paidLeaveDays + $unpaidLeaveDays;

        // Calculate salary components
        $basic       = (float) $salary->basic_salary;
        $hra         = (float) $salary->hra;
        $allowances  = (float) $salary->allowances;
        $bonus       = (float) $salary->bonus;
        $grossSalary = $basic + $hra + $allowances + $bonus;

        // Deductions
        $globalPfPercentage = (float) (CompanySetting::getValue('pf_percentage') ?? 0);
        $pfPercentage       = $request->has('pf_percentage') ? (float) $request->pf_percentage : $globalPfPercentage;
        $structurePtAmount  = (float) ($salary->tax_deduction ?? 0);
        $ptAmount           = $request->has('pt_amount') ? (float) $request->pt_amount : $structurePtAmount;

        $daysInMonth  = $startOfMonth->daysInMonth;
        $dailyRate    = $daysInMonth > 0 ? $grossSalary / $daysInMonth : 0;
        $lopDeduction = round($dailyRate * $lopDays, 2);

        // Calculate prorated earnings components based on calendar-day attendance factor
        $attendanceFactor = $daysInMonth > 0 ? ($daysInMonth - $lopDays) / $daysInMonth : 0;
        $proratedBasic = round($basic * $attendanceFactor, 2);
        $proratedHra = round($hra * $attendanceFactor, 2);
        $proratedAllowances = round($allowances * $attendanceFactor, 2);
        $proratedBonus = round($bonus * $attendanceFactor, 2);
        $revisedGross = round($grossSalary - $lopDeduction, 2);

        $pfDeduction     = ($isIntern || !$request->input('include_pf', true)) ? 0 : round($proratedBasic * $pfPercentage / 100, 2);
        $taxDeduction    = ($isIntern || !$request->input('include_pt', true)) ? 0 : $ptAmount;
        $otherDeductions = $isIntern ? 0 : (float) $salary->other_deductions;

        // Total deductions exclude LOP deduction since it's already deducted from revised gross
        $totalDeductions = $pfDeduction + $taxDeduction + $otherDeductions;
        $netSalary       = round($revisedGross - $totalDeductions, 2);

        // Create payroll record
        DB::transaction(function () use (
            $employeeId, $salary, $month, $year, $isIntern,
            $daysInMonth, $presentDays, $totalLeaveDays, $lopDays,
            $paidLeaveDays, $unpaidLeaveDays, $absentDays,
            $revisedGross, $totalDeductions, $netSalary,
            $proratedBasic, $proratedHra, $proratedAllowances, $proratedBonus,
            $pfDeduction, $pfPercentage, $taxDeduction, $ptAmount, $otherDeductions, $lopDeduction
        ) {
            $payroll = Payroll::create([
                'employee_id'         => $employeeId,
                'salary_structure_id' => $salary->id,
                'month'               => $month,
                'year'                => $year,
                'working_days'        => $daysInMonth,
                'present_days'        => $daysInMonth - $lopDays,
                'leave_days'          => $totalLeaveDays,
                'lop_days'            => $lopDays,
                'lop_deduction'       => $lopDeduction,
                'basic_salary'        => $proratedBasic,
                'gross_salary'        => $revisedGross,
                'total_deductions'    => $totalDeductions,
                'net_salary'          => $netSalary,
                'status'              => 'processed',
                'processed_at'        => now(),
            ]);

            // Create payroll items
            if ($isIntern) {
                $items = [
                    ['name' => 'Stipend', 'type' => 'earning', 'amount' => $proratedBasic],
                ];
            } else {
                $items = [
                    ['name' => 'Basic Salary', 'type' => 'earning', 'amount' => $proratedBasic],
                    ['name' => 'HRA',          'type' => 'earning', 'amount' => $proratedHra],
                    ['name' => 'Allowances',   'type' => 'earning', 'amount' => $proratedAllowances],
                ];
            }
            
            if ($proratedBonus > 0)          
                $items[] = ['name' => 'Bonus', 'type' => 'earning', 'amount' => $proratedBonus];
            if ($pfDeduction > 0)    
                $items[] = ['name' => "Provident Fund ({$pfPercentage}%)", 'type' => 'deduction', 'amount' => $pfDeduction];
            if ($taxDeduction > 0)   
                $items[] = ['name' => "Professional Tax (₹{$ptAmount})", 'type' => 'deduction', 'amount' => $taxDeduction];
            if ($otherDeductions > 0)
                $items[] = ['name' => 'Other Deductions', 'type' => 'deduction', 'amount' => $otherDeductions];

            foreach ($items as $item) {
                PayrollItem::create([
                    'payroll_id' => $payroll->id, 
                    'name' => $item['name'], 
                    'type' => $item['type'], 
                    'amount' => $item['amount']
                ]);
            }
        });

        return response()->json([
            'message'           => 'Payroll generated successfully.',
            'month'             => $month,
            'year'              => $year,
            'working_days'      => $daysInMonth,
            'present_days'      => $daysInMonth - $lopDays,
            'paid_leave_days'   => $paidLeaveDays,
            'unpaid_leave_days' => $unpaidLeaveDays,
            'absent_days'       => $absentDays,
            'lop_days'          => $lopDays,
            'gross_salary'      => $revisedGross,
            'pf_percentage'     => $pfPercentage,
            'pt_amount'         => $taxDeduction,
            'total_deductions'  => $totalDeductions,
            'net_salary'        => $netSalary,
        ], 201);
    }

    /**
     * Mark single payroll as paid
     */
    public function markPaid(Payroll $payroll): JsonResponse
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }
        
        $payroll->update(['status' => 'paid', 'paid_at' => now()]);
        return response()->json(['message' => 'Payroll marked as paid.']);
    }

    /**
     * Send single payslip email
     */
    public function sendEmail($payrollId): JsonResponse
    {
        $payroll = Payroll::with(['employee.user'])->findOrFail($payrollId);
        $employeeEmail = $payroll->employee->user->email ?? null;
        $employeeName  = $payroll->employee->first_name . ' ' . $payroll->employee->last_name;

        if (!$employeeEmail) {
            return response()->json(['message' => "No email found for {$employeeName}."], 422);
        }

        try {
            $variables = \App\Services\DocumentService::getPayslipVariables($payroll, $payroll->employee);
            $pdf = \App\Services\DocumentService::render('monthly_payslip_template', $variables);
            $monthName = date("F", mktime(0, 0, 0, $payroll->month, 10));

            \App\Jobs\SendReusableMail::dispatch(
                'employee_payslip_delivery',
                $employeeEmail,
                [
                    'name' => $payroll->employee->full_name,
                    'employee_name' => $payroll->employee->full_name,
                    'month' => $monthName,
                    'year' => $payroll->year,
                    'net_salary' => $payroll->net_salary,
                ],
                null,
                [
                    [
                        'data' => base64_encode($pdf->output()),
                        'name' => "Payslip-{$monthName}-{$payroll->year}.pdf",
                        'mime' => 'application/pdf',
                        'base64' => true,
                    ]
                ]
            );
            return response()->json(['message' => "Payslip email queued for {$employeeEmail}."]);
        } catch (\Exception $e) {
            Log::error('Payslip mail failed: ' . $e->getMessage());
            return response()->json(['message' => "Mail delivery failed for {$employeeEmail}."], 500);
        }
    }

    /**
     * Download payslip PDF
     */
    public function downloadPayslip(Request $request, $id)
    {
        if (!auth()->check()) {
            $tokenStr = $request->bearerToken() ?? $request->query('token');
            if ($tokenStr) {
                $token = PersonalAccessToken::findToken($tokenStr);
                if ($token && ($token->expires_at === null || $token->expires_at->isFuture())) {
                    auth()->login($token->tokenable);
                }
            }
        }

        $user = auth()->user();
        if (!$user) abort(401, 'Unauthenticated.');

        $payroll = Payroll::with(['employee.department', 'salaryStructure', 'items'])->findOrFail($id);
        $employeeId = $user->employee->id ?? null;

        if ($user->role !== 'admin' && $payroll->employee_id !== $employeeId) 
            abort(403, 'Unauthorized.');
        if ($user->role !== 'admin' && $payroll->status !== 'paid') 
            abort(403, 'Payslip not yet released.');

        $variables = \App\Services\DocumentService::getPayslipVariables($payroll, $payroll->employee);
        $pdf = \App\Services\DocumentService::render('monthly_payslip_template', $variables);
        
        $filename = "payslip-{$payroll->employee_id}-{$payroll->year}-{$payroll->month}.pdf";
        return $pdf->stream($filename);
    }



    /**
     * Export payroll data to CSV
     */
    public function exportCSV(Request $request): JsonResponse
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $query = Payroll::with(['employee.department']);
        
        if ($request->month) $query->where('month', $request->month);
        if ($request->year) $query->where('year', $request->year);
        
        $payrolls = $query->get();
        
        $csvData = [];
        $csvData[] = ['Employee ID', 'Employee Name', 'Department', 'Month', 'Year', 'Gross Salary', 'Total Deductions', 'Net Salary', 'Status', 'Paid At'];
        
        foreach ($payrolls as $payroll) {
            $csvData[] = [
                $payroll->employee->employee_id ?? $payroll->employee_id,
                $payroll->employee->first_name . ' ' . $payroll->employee->last_name,
                $payroll->employee->department->name ?? 'N/A',
                date('F', mktime(0, 0, 0, $payroll->month, 1)),
                $payroll->year,
                $payroll->gross_salary,
                $payroll->total_deductions,
                $payroll->net_salary,
                $payroll->status,
                $payroll->paid_at ?? 'N/A'
            ];
        }
        
        return response()->json([
            'data' => $csvData,
            'filename' => 'payroll_export_' . date('Y-m-d') . '.csv'
        ]);
    }

    /**
     * Get department breakdown for payroll
     */
    public function departmentBreakdown(Request $request): JsonResponse
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $year = $request->year ?? Carbon::now()->year;
        
        $breakdown = Payroll::where('year', $year)
            ->join('employees', 'payrolls.employee_id', '=', 'employees.id')
            ->join('departments', 'employees.department_id', '=', 'departments.id')
            ->select(
                'departments.id',
                'departments.name',
                DB::raw('COUNT(DISTINCT payrolls.employee_id) as employee_count'),
                DB::raw('SUM(payrolls.gross_salary) as total_gross'),
                DB::raw('SUM(payrolls.total_deductions) as total_deductions'),
                DB::raw('SUM(payrolls.net_salary) as total_net'),
                DB::raw('AVG(payrolls.net_salary) as average_net')
            )
            ->groupBy('departments.id', 'departments.name')
            ->get()
            ->map(function($item) {
                return [
                    'department_id' => $item->id,
                    'department_name' => $item->name,
                    'employee_count' => $item->employee_count,
                    'total_gross' => (float) $item->total_gross,
                    'total_deductions' => (float) $item->total_deductions,
                    'total_net' => (float) $item->total_net,
                    'average_net' => (float) $item->average_net
                ];
            });

        return response()->json($breakdown);
    }

    /**
     * Delete a payroll record (Admin only)
     */
    public function destroy(Payroll $payroll): JsonResponse
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($payroll->status === 'paid') {
            return response()->json(['message' => 'Cannot delete a paid payroll record.'], 422);
        }

        DB::transaction(function () use ($payroll) {
            $payroll->items()->delete();
            $payroll->delete();
        });

        return response()->json(['message' => 'Payroll record deleted successfully.']);
    }
}
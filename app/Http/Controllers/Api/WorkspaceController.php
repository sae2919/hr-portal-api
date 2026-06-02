<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class WorkspaceController extends Controller
{
    // ── Role tier resolution (mirrors frontend resolveRoleTier) ──────────────
    // Priority: Spatie roles → users.role column → designation title
    private function resolveRoleTier($user, $designation = null): string
    {
        $spatieRoles = $user->getRoleNames()
            ->map(fn($r) => strtolower(str_replace([' ', '-'], '_', $r)))
            ->toArray();

        $adminRoles    = ['admin', 'super_admin', 'superadmin', 'hr_admin'];
        $hrRoles       = ['hr', 'hr_manager'];
        $managerRoles  = ['manager'];
        $salesMgrRoles = ['sales_manager'];
        $teamLeadRoles = ['team_lead'];

        // 1. Check Spatie roles
        foreach ($adminRoles   as $r) { if (in_array($r, $spatieRoles)) return 'admin'; }
        foreach ($hrRoles       as $r) { if (in_array($r, $spatieRoles)) return 'hr'; }
        foreach ($managerRoles  as $r) { if (in_array($r, $spatieRoles)) return 'manager'; }
        foreach ($salesMgrRoles as $r) { if (in_array($r, $spatieRoles)) return 'sales_manager'; }
        foreach ($teamLeadRoles as $r) { if (in_array($r, $spatieRoles)) return 'team_lead'; }

        // 2. Fallback: users.role column
        $roleCol = strtolower(str_replace([' ', '-'], '_', $user->role ?? ''));
        if (in_array($roleCol, $adminRoles))    return 'admin';
        if (in_array($roleCol, $hrRoles))       return 'hr';
        if ($roleCol === 'manager')             return 'manager';
        if ($roleCol === 'sales_manager')       return 'sales_manager';
        if ($roleCol === 'team_lead')           return 'team_lead';

        // 3. Fallback: designation title
        if ($designation) {
            $d = strtolower($designation);
            $managerDesignations  = ['manager', 'senior manager', 'department manager'];
            $teamLeadDesignations = [
                'sales head', 'seo lead', 'tech lead', 'technical lead',
                'team lead', 'lead', 'head', 'seo head', 'dev lead',
            ];
            foreach ($managerDesignations  as $kw) { if (str_contains($d, $kw)) return 'manager'; }
            foreach ($teamLeadDesignations as $kw) { if (str_contains($d, $kw)) return 'team_lead'; }
        }

        return 'employee';
    }

    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // ── Resolve Employee Record ───────────────────────────────────────────
        $employee = null;
        if (Schema::hasTable('employees')) {
            $employee = DB::table('employees')
                ->where(function ($query) use ($user) {
                    if (Schema::hasColumn('employees', 'user_id')) {
                        $query->where('user_id', $user->id);
                    }
                    if (!empty($user->employee_id) && Schema::hasColumn('employees', 'id')) {
                        $query->orWhere('id', $user->employee_id);
                    }
                })
                ->first();
        }

        // ── Resolve Designation title (for role fallback) ─────────────────────
        $designationTitle = null;
        if ($employee && !empty($employee->designation_id) && Schema::hasTable('designations')) {
            $desig = DB::table('designations')->where('id', $employee->designation_id)->first();
            $designationTitle = $desig->title ?? $desig->name ?? null;
        }

        // ── Resolve Department ────────────────────────────────────────────────
        $deptCode = 'GENERAL';
        $deptId   = null;
        $deptName = null;

        if ($employee && isset($employee->department_id) && Schema::hasTable('departments')) {
            $department = DB::table('departments')->where('id', $employee->department_id)->first();
            if ($department) {
                $deptId   = $department->id;
                $deptName = $department->name ?? null;
                $deptCode = strtoupper($department->code ?? $department->name ?? 'GENERAL');
            }
        }

        // ── Resolve Role Tier ─────────────────────────────────────────────────
        $roleTier = $this->resolveRoleTier($user, $designationTitle);

        // ── Base Payload ──────────────────────────────────────────────────────
        $payload = [
            'department'  => $deptName ?? $deptCode,
            'dept_code'   => $deptCode,
            'role_tier'   => $roleTier,
            'user_name'   => $user->name,
            'designation' => $designationTitle,
        ];

        // ── Manager / Team Lead Stats (department-scoped) ─────────────────────
        if (in_array($roleTier, ['manager', 'team_lead']) && $deptId) {
            $today = Carbon::today();

            $deptEmployeeIds = Schema::hasTable('employees')
                ? DB::table('employees')
                    ->where('department_id', $deptId)
                    ->when($employee, fn($q) => $q->where('id', '!=', $employee->id))
                    ->pluck('id')
                : collect();

            $deptCount = $deptEmployeeIds->count();

            $presentToday = Schema::hasTable('attendances') && $deptCount > 0
                ? DB::table('attendances')
                    ->whereIn('employee_id', $deptEmployeeIds)
                    ->whereDate('date', $today)
                    ->whereIn('status', ['present', 'late'])
                    ->count()
                : 0;

            $onLeaveToday = Schema::hasTable('attendances') && $deptCount > 0
                ? DB::table('attendances')
                    ->whereIn('employee_id', $deptEmployeeIds)
                    ->whereDate('date', $today)
                    ->where('status', 'on_leave')
                    ->count()
                : 0;

            $absentToday = max(0, $deptCount - $presentToday - $onLeaveToday);

            $pendingApprovals = Schema::hasTable('leaves') && $deptCount > 0
                ? DB::table('leaves')
                    ->whereIn('employee_id', $deptEmployeeIds)
                    ->where('status', 'pending')
                    ->count()
                : 0;

            $attendanceRate = $deptCount > 0
                ? round(($presentToday / $deptCount) * 100)
                : 0;

            $payload['manager_stats'] = [
                'dept_employee_count'          => $deptCount,
                'dept_present_today'           => $presentToday,
                'dept_on_leave'                => $onLeaveToday,
                'dept_absent_today'            => $absentToday,
                'dept_pending_leave_approvals' => $pendingApprovals,
                'dept_attendance_rate'         => $attendanceRate,
            ];

            return response()->json($payload);
        }

        // ── Sales Manager Stats ───────────────────────────────────────────────
        if ($roleTier === 'sales_manager') {
            $today = Carbon::today();

            $salesEmployeeIds = collect();
            if ($deptId && Schema::hasTable('employees')) {
                $salesEmployeeIds = DB::table('employees')
                    ->where('department_id', $deptId)
                    ->when($employee, fn($q) => $q->where('id', '!=', $employee->id))
                    ->pluck('id');
            }

            $teamCount = $salesEmployeeIds->count();

            $presentToday = Schema::hasTable('attendances') && $teamCount > 0
                ? DB::table('attendances')
                    ->whereIn('employee_id', $salesEmployeeIds)
                    ->whereDate('date', $today)
                    ->whereIn('status', ['present', 'late'])
                    ->count()
                : 0;

            $pendingApprovals = Schema::hasTable('leaves') && $teamCount > 0
                ? DB::table('leaves')
                    ->whereIn('employee_id', $salesEmployeeIds)
                    ->where('status', 'pending')
                    ->count()
                : 0;

            $monthlyRevenue = Schema::hasTable('deals')
                ? (DB::table('deals')
                    ->where('status', 'won')
                    ->whereMonth('closed_at', now()->month)
                    ->sum('value') ?? 0)
                : 0;

            $pipelineDeals = Schema::hasTable('deals')
                ? DB::table('deals')->where('status', 'open')->count()
                : 0;

            $payload['manager_stats'] = [
                'dept_employee_count'          => $teamCount,
                'dept_present_today'           => $presentToday,
                'dept_on_leave'                => 0,
                'dept_absent_today'            => max(0, $teamCount - $presentToday),
                'dept_pending_leave_approvals' => $pendingApprovals,
                'dept_attendance_rate'         => $teamCount > 0
                    ? round(($presentToday / $teamCount) * 100)
                    : 0,
            ];

            $payload['sales_manager_stats'] = [
                'team_count'        => $teamCount,
                'present_today'     => $presentToday,
                'pending_approvals' => $pendingApprovals,
                'monthly_revenue'   => $monthlyRevenue,
                'pipeline_deals'    => $pipelineDeals,
                'attendance_rate'   => $teamCount > 0
                    ? round(($presentToday / $teamCount) * 100)
                    : 0,
            ];

            return response()->json($payload);
        }

        // ── Employee Stats ────────────────────────────────────────────────────
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd   = Carbon::now()->endOfMonth();
        $employeeId = $employee->id ?? null;

        $presentThisMonth = 0;
        $absentThisMonth  = 0;
        $approvedLeaves   = 0;
        $pendingLeaves    = 0;
        $latestPayslip    = null;

        if ($employeeId && Schema::hasTable('attendances')) {
            $presentThisMonth = DB::table('attendances')
                ->where('employee_id', $employeeId)
                ->whereBetween('date', [$monthStart, $monthEnd])
                ->whereIn('status', ['present', 'late'])
                ->count();

            $absentThisMonth = DB::table('attendances')
                ->where('employee_id', $employeeId)
                ->whereBetween('date', [$monthStart, $monthEnd])
                ->where('status', 'absent')
                ->count();
        }

        if ($employeeId && Schema::hasTable('leaves')) {
            $approvedLeaves = DB::table('leaves')
                ->where('employee_id', $employeeId)
                ->where('status', 'approved')
                ->whereYear('start_date', now()->year)
                ->count();

            $pendingLeaves = DB::table('leaves')
                ->where('employee_id', $employeeId)
                ->where('status', 'pending')
                ->count();
        }

        if ($employeeId && Schema::hasTable('payrolls')) {
            $latestPayslip = DB::table('payrolls')
                ->where('employee_id', $employeeId)
                ->orderByDesc('year')
                ->orderByDesc('month')
                ->select('month', 'year', 'net_salary')
                ->first();
        }

        $payload['employee_stats'] = [
            'present_this_month'   => $presentThisMonth,
            'absent_this_month'    => $absentThisMonth,
            'approved_leaves'      => $approvedLeaves,
            'pending_leaves'       => $pendingLeaves,
            'latest_payslip_month' => $latestPayslip
                ? Carbon::createFromDate($latestPayslip->year, $latestPayslip->month, 1)->format('F Y')
                : null,
            'latest_payslip_net'   => $latestPayslip?->net_salary ?? null,
        ];

        $payload['core_hr_stats'] = [
            'employees'     => Schema::hasTable('employees') ? DB::table('employees')->count() : 0,
            'present_today' => $presentThisMonth,
            'on_leave'      => $approvedLeaves,
        ];

        // ── Department-specific extras ────────────────────────────────────────
        switch ($deptCode) {
            case 'TECH':
                $payload['tech_stats'] = [
                    'active_tasks_count' => Schema::hasTable('tasks')
                        ? DB::table('tasks')->where('status', 'in_progress')->count() : 0,
                    'open_bugs_count'    => Schema::hasTable('tickets')
                        ? DB::table('tickets')->where('status', 'open')->count() : 0,
                ];
                break;

            case 'SALES':
                $payload['sales_stats'] = [
                    'monthly_revenue' => Schema::hasTable('deals')
                        ? (DB::table('deals')->where('status', 'won')->whereMonth('closed_at', now()->month)->sum('value') ?? 0) : 0,
                    'pipeline_deals'  => Schema::hasTable('deals')
                        ? DB::table('deals')->where('status', 'open')->count() : 0,
                ];
                break;

            case 'MARKETING':
            case 'SEO':
                $payload['marketing_stats'] = [
                    'active_campaigns' => Schema::hasTable('campaigns')
                        ? DB::table('campaigns')->where('status', 'active')->count() : 0,
                    'conversion_yield' => 4.2,
                ];
                break;
        }

        return response()->json($payload);
    }
}
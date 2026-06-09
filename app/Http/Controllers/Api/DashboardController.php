<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\Payroll;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    private function isAdminOrHR($user): bool
{
    // Add 'super_admin' here
    if ($user->hasRole('super_admin') || $user->hasRole('admin') || $user->hasRole('hr')) return true;

    $employee = $user->employee;
    if ($employee) {
        $designation = strtolower($employee->designation?->title ?? '');
        if (str_contains($designation, 'ceo') || str_contains($designation, 'founder') || 
            str_contains($designation, 'president') || str_contains($designation, 'co-founder') || 
            str_contains($designation, 'co_founder')) {
            return true;
        }
    }
    return false;
}

    private function isManager($user): bool
    {
        if ($this->isAdminOrHR($user)) return false;
        if ($user->hasRole('manager')) return true;

        $employee = $user->employee;
        if ($employee) {
            $level = strtolower($employee->position_level ?? '');
            if ($level === 'manager') return true;

            $designation = strtolower($employee->designation?->title ?? '');
            if (str_contains($designation, 'manager')) return true;
        }
        return false;
    }

    private function isTeamLead($user): bool
    {
        if ($this->isAdminOrHR($user)) return false;
        if ($user->hasRole('team_lead')) return true;

        $employee = $user->employee;
        if ($employee) {
            $level = strtolower($employee->position_level ?? '');
            if ($level === 'team_lead') return true;

            $designation = strtolower($employee->designation?->title ?? '');
            if (str_contains($designation, 'team lead') || str_contains($designation, 'lead')) return true;
        }
        return false;
    }

    private function isSalesMgr($user): bool
    {
        if ($this->isAdminOrHR($user)) return false;
        if ($user->hasRole('sales_manager')) return true;

        $employee = $user->employee;
        if ($employee) {
            $designation = strtolower($employee->designation?->title ?? '');
            if (str_contains($designation, 'sales manager')) return true;
        }
        return false;
    }

    public function stats(): JsonResponse
    {
        $user       = auth()->user();
        $isAdmin    = $this->isAdminOrHR($user);
        $isManager  = $this->isManager($user);
        $isTeamLead = $this->isTeamLead($user);
        $isSalesMgr = $this->isSalesMgr($user);
        $employeeId = $user->employee?->id;
        $deptId     = $user->employee?->department_id;

        // Cache for 60 seconds per user — re-runs if stale or on next visit
        $cacheKey = "dashboard_stats_{$user->id}";

        $data = Cache::remember($cacheKey, 60, function () use (
            $isAdmin, $isManager, $isTeamLead, $isSalesMgr, $employeeId, $deptId
        ) {
            if ($isAdmin) {
                // ── Admin / HR: full system stats ─────────────────────────
                $totalEmployees   = Employee::where('status', 'active')->count();
                $totalDepartments = Department::where('status', 'active')->count();
                $presentToday     = Attendance::whereDate('date', today())->where('status', 'present')->count();
                $onLeave          = Leave::where('status', 'approved')
                                        ->whereDate('start_date', '<=', today())
                                        ->whereDate('end_date', '>=', today())
                                        ->count();
                $pendingLeaves    = Leave::where('status', 'pending')->count();
                $totalPayroll     = Payroll::whereMonth('created_at', now()->month)
                                        ->whereYear('created_at', now()->year)
                                        ->sum('net_salary');

            } elseif ($isManager || $isTeamLead) {
                // ── Manager / TeamLead: own department stats only ─────────
                $totalEmployees   = Employee::where('status', 'active')
                                        ->where('department_id', $deptId)->count();
                $totalDepartments = 1;
                $presentToday     = Attendance::whereDate('date', today())
                                        ->where('status', 'present')
                                        ->whereHas('employee', fn($q) => $q->where('department_id', $deptId))
                                        ->count();
                $onLeave          = Leave::where('status', 'approved')
                                        ->whereDate('start_date', '<=', today())
                                        ->whereDate('end_date', '>=', today())
                                        ->whereHas('employee', fn($q) => $q->where('department_id', $deptId))
                                        ->count();
                $pendingLeaves    = Leave::where('status', 'pending')
                                        ->whereHas('employee', fn($q) => $q->where('department_id', $deptId))
                                        ->count();
                $totalPayroll     = null; // TeamLead does not see payroll

            } elseif ($isSalesMgr) {
                // ── SalesManager: department stats + payroll visibility ───
                $totalEmployees   = Employee::where('status', 'active')
                                        ->where('department_id', $deptId)->count();
                $totalDepartments = Department::where('status', 'active')->count();
                $presentToday     = Attendance::whereDate('date', today())
                                        ->where('status', 'present')
                                        ->whereHas('employee', fn($q) => $q->where('department_id', $deptId))
                                        ->count();
                $onLeave          = Leave::where('status', 'approved')
                                        ->whereDate('start_date', '<=', today())
                                        ->whereDate('end_date', '>=', today())
                                        ->whereHas('employee', fn($q) => $q->where('department_id', $deptId))
                                        ->count();
                $pendingLeaves    = Leave::where('status', 'pending')
                                        ->whereHas('employee', fn($q) => $q->where('department_id', $deptId))
                                        ->count();
                $totalPayroll     = Payroll::whereMonth('created_at', now()->month)
                                        ->whereYear('created_at', now()->year)
                                        ->whereHas('employee', fn($q) => $q->where('department_id', $deptId))
                                        ->sum('net_salary');

            } else {
                // ── Employee: own stats only ──────────────────────────────
                $totalEmployees   = null;
                $totalDepartments = null;
                $presentToday     = Attendance::whereDate('date', today())
                                        ->where('employee_id', $employeeId)
                                        ->where('status', 'present')
                                        ->count();
                $onLeave          = Leave::where('employee_id', $employeeId)
                                        ->where('status', 'approved')
                                        ->whereDate('start_date', '<=', today())
                                        ->whereDate('end_date', '>=', today())
                                        ->count();
                $pendingLeaves    = Leave::where('employee_id', $employeeId)
                                        ->where('status', 'pending')
                                        ->count();
                $totalPayroll     = null;
            }

            return compact(
                'totalEmployees', 'totalDepartments', 'presentToday',
                'onLeave', 'pendingLeaves', 'totalPayroll'
            );
        });

        return response()->json([
            'employees'       => $data['totalEmployees'],
            'departments'     => $data['totalDepartments'],
            'present_today'   => $data['presentToday'],
            'on_leave'        => $data['onLeave'],
            'pending_leaves'  => $data['pendingLeaves'],
            'monthly_payroll' => $data['totalPayroll'],
            'role'            => $user->getRoleNames()->first(),
        ]);
    }
}
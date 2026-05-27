<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\Payroll;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        $user       = auth()->user();
        $isAdmin    = $user->hasRole('admin') || $user->hasRole('hr');
        $isManager  = $user->hasRole('manager');
        $isTeamLead = $user->hasRole('team_lead');
        $isSalesMgr = $user->hasRole('sales_manager');
        $employeeId = $user->employee?->id;
        $deptId     = $user->employee?->department_id;

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

        return response()->json([
            'employees'       => $totalEmployees,
            'departments'     => $totalDepartments,
            'present_today'   => $presentToday,
            'on_leave'        => $onLeave,
            'pending_leaves'  => $pendingLeaves,
            'monthly_payroll' => $totalPayroll,
            'role'            => $user->getRoleNames()->first(),
        ]);
    }
}
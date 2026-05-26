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
    $user = auth()->user(); // ← add this

    $today = today()->toDateString();

    $totalEmployees = Employee::count();

    $onLeaveToday = Leave::where('status', 'approved')
        ->whereDate('start_date', '<=', $today)
        ->whereDate('end_date', '>=', $today)
        ->count();

    $manuallyAbsentToday = Attendance::whereDate('date', $today)
        ->where('status', 'absent')
        ->whereNotIn('employee_id', function($query) use ($today) {
            $query->select('employee_id')
                ->from('leaves')
                ->where('status', 'approved')
                ->whereDate('start_date', '<=', $today)
                ->whereDate('end_date', '>=', $today);
        })
        ->count();

    $presentToday = $totalEmployees - ($onLeaveToday + $manuallyAbsentToday);
    $pendingLeaves = Leave::where('status', 'pending')->count();
    $monthlyPayroll = Payroll::whereMonth('created_at', now()->month)
        ->whereYear('created_at', now()->year)
        ->sum('net_salary');

    return response()->json([
        'role_tier'       => $user->role,  // ← add this
        'employees'       => $totalEmployees,
        'present_today'   => max(0, $presentToday),
        'on_leave'        => $onLeaveToday,
        'pending_leaves'  => $pendingLeaves,
        'departments'     => Department::count(),
        'monthly_payroll' => $monthlyPayroll,
    ]);
}
}
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Leave;
use App\Models\Attendance;
use App\Models\Payroll;
use App\Models\Employee;
use Carbon\Carbon;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user     = $request->user();
        $employee = Employee::where('user_id', $user->id)->first();
        $role     = $user->role ?? 'employee';
        $now      = Carbon::now();
        $notifications = [];

        // ── 1. LEAVE NOTIFICATIONS ────────────────────────────────────────────

        if (in_array($role, ['admin', 'hr'])) {
            // Admin/HR: all pending leave requests
            $pendingLeaves = Leave::with('employee.user')
                ->where('status', 'pending')
                ->orderByDesc('created_at')
                ->take(10)
                ->get();

            foreach ($pendingLeaves as $leave) {
                $notifications[] = [
                    'id'      => 'leave_pending_' . $leave->id,
                    'type'    => 'leave_request',
                    'title'   => 'Leave Request Pending',
                    'message' => ($leave->employee->user->name ?? 'An employee') . ' requested ' . $leave->leave_type . ' leave',
                    'time'    => $leave->created_at->diffForHumans(),
                    'read'    => false,
                    'icon'    => 'calendar',
                    'color'   => 'orange',
                ];
            }

        } elseif (in_array($role, ['manager', 'team_lead', 'sales_manager']) && $employee) {
            // Manager/TL: pending leaves in their department
            $pendingLeaves = Leave::with('employee.user')
                ->whereHas('employee', fn($q) => $q->where('department_id', $employee->department_id))
                ->where('status', 'pending')
                ->orderByDesc('created_at')
                ->take(5)
                ->get();

            foreach ($pendingLeaves as $leave) {
                $notifications[] = [
                    'id'      => 'leave_pending_' . $leave->id,
                    'type'    => 'leave_request',
                    'title'   => 'Leave Request Pending',
                    'message' => ($leave->employee->user->name ?? 'An employee') . ' requested ' . $leave->leave_type . ' leave',
                    'time'    => $leave->created_at->diffForHumans(),
                    'read'    => false,
                    'icon'    => 'calendar',
                    'color'   => 'orange',
                ];
            }

        } elseif ($employee) {
            // Employee: their own leave status updates (last 7 days)
            $myLeaves = Leave::where('employee_id', $employee->id)
                ->whereIn('status', ['approved', 'rejected'])
                ->where('updated_at', '>=', $now->copy()->subDays(7))
                ->orderByDesc('updated_at')
                ->take(5)
                ->get();

            foreach ($myLeaves as $leave) {
                $isApproved = $leave->status === 'approved';
                $notifications[] = [
                    'id'      => 'leave_status_' . $leave->id,
                    'type'    => 'leave_status',
                    'title'   => 'Leave ' . ucfirst($leave->status),
                    'message' => 'Your ' . $leave->leave_type . ' leave was ' . $leave->status,
                    'time'    => $leave->updated_at->diffForHumans(),
                    'read'    => false,
                    'icon'    => $isApproved ? 'check' : 'x',
                    'color'   => $isApproved ? 'green' : 'red',
                ];
            }
        }

        // ── 2. BIRTHDAY & WORK ANNIVERSARY NOTIFICATIONS ─────────────────────

        $today    = $now->format('m-d');
        $tomorrow = $now->copy()->addDay()->format('m-d');

        $birthdayEmployees = Employee::with('user')
            ->whereNotNull('date_of_birth')
            ->get()
            ->filter(fn($e) =>
                Carbon::parse($e->date_of_birth)->format('m-d') === $today ||
                Carbon::parse($e->date_of_birth)->format('m-d') === $tomorrow
            );

        foreach ($birthdayEmployees as $emp) {
            $dob     = Carbon::parse($emp->date_of_birth);
            $isToday = $dob->format('m-d') === $today;
            $notifications[] = [
                'id'      => 'birthday_' . $emp->id,
                'type'    => 'birthday',
                'title'   => $isToday ? '🎂 Birthday Today!' : '🎂 Birthday Tomorrow',
                'message' => ($emp->user->name ?? 'An employee') . ($isToday ? ' is celebrating their birthday today!' : '\'s birthday is tomorrow!'),
                'time'    => $isToday ? 'Today' : 'Tomorrow',
                'read'    => false,
                'icon'    => 'cake',
                'color'   => 'pink',
            ];
        }

        // Work anniversaries
        $anniversaryEmployees = Employee::with('user')
            ->whereNotNull('joining_date')
            ->get()
            ->filter(function ($e) use ($today, $tomorrow) {
                $joined = Carbon::parse($e->joining_date);
                // Only show if at least 1 year has passed
                if ($joined->diffInYears(Carbon::now()) < 1) return false;
                return $joined->format('m-d') === $today || $joined->format('m-d') === $tomorrow;
            });

        foreach ($anniversaryEmployees as $emp) {
            $joined  = Carbon::parse($emp->joining_date);
            $years   = $joined->diffInYears(Carbon::now());
            $isToday = $joined->format('m-d') === $today;
            $notifications[] = [
                'id'      => 'anniversary_' . $emp->id,
                'type'    => 'anniversary',
                'title'   => $isToday ? '⭐ Work Anniversary!' : '⭐ Anniversary Tomorrow',
                'message' => ($emp->user->name ?? 'An employee') . ' is completing ' . $years . ' year' . ($years > 1 ? 's' : '') . ' at the company!',
                'time'    => $isToday ? 'Today' : 'Tomorrow',
                'read'    => false,
                'icon'    => 'star',
                'color'   => 'yellow',
            ];
        }

        // ── 3. ATTENDANCE ALERTS ──────────────────────────────────────────────

        if (in_array($role, ['admin', 'hr', 'manager', 'team_lead', 'sales_manager'])) {
            $absentToday = Employee::with('user')
                ->whereDoesntHave('attendances', fn($q) =>
                    $q->whereDate('date', $now->toDateString())
                      ->whereIn('status', ['present', 'half_day'])
                )
                ->whereDoesntHave('leaves', fn($q) =>
                    $q->where('status', 'approved')
                      ->whereDate('start_date', '<=', $now->toDateString())
                      ->whereDate('end_date',   '>=', $now->toDateString())
                )
                ->when(
                    in_array($role, ['manager', 'team_lead', 'sales_manager']) && $employee,
                    fn($q) => $q->where('department_id', $employee->department_id)
                )
                ->take(5)
                ->get();

            if ($absentToday->count() > 0) {
                $names = $absentToday->take(3)->map(fn($e) => $e->user->name ?? 'Unknown')->join(', ');
                $extra = $absentToday->count() > 3 ? ' and ' . ($absentToday->count() - 3) . ' more' : '';
                $notifications[] = [
                    'id'      => 'absent_today_' . $now->toDateString(),
                    'type'    => 'attendance',
                    'title'   => 'Absent Today',
                    'message' => $names . $extra . ' have not marked attendance',
                    'time'    => 'Today',
                    'read'    => false,
                    'icon'    => 'alert',
                    'color'   => 'red',
                ];
            }
        } elseif ($employee) {
            // Employee: check if they marked attendance today
            $markedToday = \App\Models\Attendance::where('employee_id', $employee->id)
                ->whereDate('date', $now->toDateString())
                ->exists();

            if (!$markedToday && $now->isWeekday() && $now->hour >= 9) {
                $notifications[] = [
                    'id'      => 'attendance_missing_' . $now->toDateString(),
                    'type'    => 'attendance',
                    'title'   => 'Attendance Not Marked',
                    'message' => 'You have not marked your attendance today',
                    'time'    => 'Today',
                    'read'    => false,
                    'icon'    => 'alert',
                    'color'   => 'orange',
                ];
            }
        }

        // ── 4. PAYROLL NOTIFICATIONS ──────────────────────────────────────────

        if (in_array($role, ['super admin','admin', 'hr'])) {
            // Admin/HR: unpaid payrolls this month
            $unpaidCount = Payroll::where('status', 'pending')
                ->where('month', $now->month)
                ->where('year',  $now->year)
                ->count();

            if ($unpaidCount > 0) {
                $notifications[] = [
                    'id'      => 'payroll_pending_' . $now->format('Y-m'),
                    'type'    => 'payroll',
                    'title'   => 'Payroll Pending',
                    'message' => $unpaidCount . ' payroll' . ($unpaidCount > 1 ? 's' : '') . ' pending for ' . $now->format('F Y'),
                    'time'    => $now->format('F Y'),
                    'read'    => false,
                    'icon'    => 'rupee',
                    'color'   => 'blue',
                ];
            }

        } elseif ($employee) {
            // Employee: latest payslip processed
            $latestPayroll = Payroll::where('employee_id', $employee->id)
                ->where('status', 'paid')
                ->where('updated_at', '>=', $now->copy()->subDays(7))
                ->orderByDesc('updated_at')
                ->first();

            if ($latestPayroll) {
                $notifications[] = [
                    'id'      => 'payroll_paid_' . $latestPayroll->id,
                    'type'    => 'payroll',
                    'title'   => 'Salary Processed',
                    'message' => 'Your salary for ' . Carbon::create($latestPayroll->year, $latestPayroll->month, 1)->format('F Y') . ' has been processed',
                    'time'    => $latestPayroll->updated_at->diffForHumans(),
                    'read'    => false,
                    'icon'    => 'rupee',
                    'color'   => 'green',
                ];
            }
        }

        // ── Sort: unread first, then by recency (already in order) ───────────
        return response()->json([
            'success' => true,
            'data'    => $notifications,
            'count'   => count($notifications),
        ]);
    }
}
<?php

namespace App\Http\Controllers\Api\Attendance;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    // ── Role helpers ──────────────────────────────────────────────
    private function isAdminOrHR(): bool
    {
        return auth()->user()->hasRole('admin') || auth()->user()->hasRole('hr');
    }

    private function isManager(): bool
    {
        return auth()->user()->hasRole('manager');
    }

    private function managerDeptId(): ?int
    {
        return auth()->user()->employee?->department_id;
    }

    // ── GET /api/v1/attendance ────────────────────────────────────
    public function index(Request $request): AnonymousResourceCollection
    {
        $user  = auth()->user();
        $query = Attendance::with(['employee.department']);

        if ($this->isAdminOrHR()) {
            // Admin/HR: see all, with optional filters
            if ($request->filled('employee_id')) {
                $query->where('employee_id', $request->employee_id);
            }
            if ($request->filled('department_id')) {
                $query->whereHas('employee', fn($q) => $q->where('department_id', $request->department_id));
            }

        } elseif ($this->isManager()) {
            // Manager: only their department
            $deptId = $this->managerDeptId();
            $query->whereHas('employee', fn($q) => $q->where('department_id', $deptId));

            if ($request->filled('employee_id')) {
                $query->where('employee_id', $request->employee_id);
            }

        } else {
            // Employee: only own attendance
            $employeeId = $user->employee?->id;
            if (!$employeeId) abort(403, 'Employee record not found.');
            $query->where('employee_id', $employeeId);
        }

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }
        if ($request->filled('month') && $request->filled('year')) {
            $query->whereMonth('date', $request->month)->whereYear('date', $request->year);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $attendance = $query->orderBy('date', 'desc')->orderBy('employee_id')->paginate($request->per_page ?? 10);

        return AttendanceResource::collection($attendance);
    }

    // ── POST /api/v1/attendance ───────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'employee_id'    => ['required', 'exists:employees,id'],
            'date'           => ['required', 'date'],
            'check_in'       => ['nullable', 'date_format:H:i'],
            'check_out'      => ['nullable', 'date_format:H:i', 'after:check_in'],
            'status'         => ['nullable', 'in:present,absent,late,half_day,holiday'],
            'overtime_hours' => ['nullable', 'numeric', 'min:0', 'max:12'],
            'note'           => ['nullable', 'string', 'max:500'],
        ]);

        $user = auth()->user();

        if ($this->isAdminOrHR()) {
            // Full access

        } elseif ($this->isManager()) {
            // Manager: only for their dept employees
            $employee = Employee::find($request->employee_id);
            if ($employee->department_id !== $this->managerDeptId()) {
                return response()->json(['message' => 'You can only manage attendance for your department.'], 403);
            }

        } else {
            // Employee: only themselves
            $employeeId = $user->employee?->id;
            if ((int) $request->employee_id !== $employeeId) {
                return response()->json(['message' => 'You can only manage your own attendance.'], 403);
            }
        }

        $attendance = Attendance::updateOrCreate(
            ['employee_id' => $request->employee_id, 'date' => $request->date],
            [
                'check_in'       => $request->check_in,
                'check_out'      => $request->check_out,
                'status'         => $request->status ?? 'present',
                'overtime_hours' => $request->overtime_hours ?? 0,
                'note'           => $request->note,
            ]
        );

        return response()->json([
            'message' => 'Attendance saved successfully.',
            'data'    => new AttendanceResource($attendance->load('employee.department')),
        ], 201);
    }

    // ── GET /api/v1/attendance/{attendance} ───────────────────────
    public function show(Attendance $attendance): JsonResponse
    {
        $user = auth()->user();

        if ($this->isAdminOrHR()) {
            // Full access
        } elseif ($this->isManager()) {
            if ($attendance->employee->department_id !== $this->managerDeptId()) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }
        } else {
            if ($attendance->employee_id !== $user->employee?->id) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }
        }

        return response()->json(['data' => new AttendanceResource($attendance->load('employee.department'))]);
    }

    // ── PUT /api/v1/attendance/{attendance} ───────────────────────
    public function update(Request $request, Attendance $attendance): JsonResponse
    {
        $user = auth()->user();

        if ($this->isAdminOrHR()) {
            // Full access
        } elseif ($this->isManager()) {
            if ($attendance->employee->department_id !== $this->managerDeptId()) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }
        } else {
            if ($attendance->employee_id !== $user->employee?->id) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }
        }

        $request->validate([
            'check_in'       => ['nullable', 'date_format:H:i'],
            'check_out'      => ['nullable', 'date_format:H:i'],
            'status'         => ['nullable', 'in:present,absent,late,half_day,holiday'],
            'overtime_hours' => ['nullable', 'numeric', 'min:0', 'max:12'],
            'note'           => ['nullable', 'string', 'max:500'],
        ]);

        $attendance->update($request->all());

        return response()->json([
            'message' => 'Attendance updated successfully.',
            'data'    => new AttendanceResource($attendance->load('employee.department')),
        ]);
    }

    // ── DELETE /api/v1/attendance/{attendance} ────────────────────
    public function destroy(Attendance $attendance): JsonResponse
    {
        if (!$this->isAdminOrHR()) {
            return response()->json(['message' => 'Only Admin/HR can delete attendance records.'], 403);
        }

        $attendance->delete();
        return response()->json(['message' => 'Attendance deleted successfully.']);
    }

    // ── POST /api/v1/attendance/checkin ───────────────────────────
    public function checkIn(Request $request): JsonResponse
    {
        $request->validate(['employee_id' => ['required', 'exists:employees,id']]);

        $user = auth()->user();

        if (!$this->isAdminOrHR()) {
            $employeeId = $user->employee?->id;
            if ((int) $request->employee_id !== $employeeId) {
                return response()->json(['message' => 'You can only check in for yourself.'], 403);
            }
        }

        $today = Carbon::today()->toDateString();
        $now   = Carbon::now()->format('H:i');

        $attendance = Attendance::updateOrCreate(
            ['employee_id' => $request->employee_id, 'date' => $today],
            ['check_in' => $now, 'status' => 'present']
        );

        return response()->json([
            'message' => 'Checked in at ' . $now,
            'data'    => new AttendanceResource($attendance->load('employee')),
        ]);
    }

    // ── POST /api/v1/attendance/checkout ──────────────────────────
    public function checkOut(Request $request): JsonResponse
    {
        $request->validate(['employee_id' => ['required', 'exists:employees,id']]);

        $user = auth()->user();

        if (!$this->isAdminOrHR()) {
            $employeeId = $user->employee?->id;
            if ((int) $request->employee_id !== $employeeId) {
                return response()->json(['message' => 'You can only check out for yourself.'], 403);
            }
        }

        $today      = Carbon::today()->toDateString();
        $attendance = Attendance::where('employee_id', $request->employee_id)->whereDate('date', $today)->first();

        if (!$attendance) {
            return response()->json(['message' => 'No check-in found for today.'], 422);
        }

        $now = Carbon::now()->format('H:i');
        $attendance->update(['check_out' => $now]);

        return response()->json([
            'message' => 'Checked out at ' . $now,
            'data'    => new AttendanceResource($attendance->load('employee')),
        ]);
    }

    // ── GET /api/v1/attendance/report/monthly ─────────────────────
    public function monthlyReport(Request $request): JsonResponse
    {
        $month = $request->get('month', Carbon::now()->month);
        $year  = $request->get('year',  Carbon::now()->year);
        $user  = auth()->user();

        $employeeQuery = Employee::with(['department'])->where('status', 'active');

        if ($this->isAdminOrHR()) {
            if ($request->filled('employee_id')) {
                $employeeQuery->where('id', $request->employee_id);
            }
        } elseif ($this->isManager()) {
            $employeeQuery->where('department_id', $this->managerDeptId());
        } else {
            $employeeId = $user->employee?->id;
            if (!$employeeId) return response()->json(['message' => 'Employee record not found.'], 403);
            $employeeQuery->where('id', $employeeId);
        }

        $employees = $employeeQuery->paginate(10);

        $report = $employees->getCollection()->map(function (Employee $employee) use ($month, $year) {
            $records = Attendance::where('employee_id', $employee->id)
                                 ->whereMonth('date', $month)
                                 ->whereYear('date', $year)
                                 ->get();

            return [
                'employee_id'    => $employee->id,
                'employee_code'  => $employee->employee_code,
                'full_name'      => $employee->full_name,
                'department'     => $employee->department?->name,
                'present'        => $records->where('status', 'present')->count(),
                'absent'         => $records->where('status', 'absent')->count(),
                'late'           => $records->where('status', 'late')->count(),
                'half_day'       => $records->where('status', 'half_day')->count(),
                'total_days'     => $records->count(),
                'overtime_hours' => $records->sum('overtime_hours'),
            ];
        });

        return response()->json(['month' => $month, 'year' => $year, 'data' => $report]);
    }

    // ── GET /api/v1/attendance/worksheet ──────────────────────────
    public function worksheet(Request $request): JsonResponse
    {
        $request->validate([
            'date'     => ['required', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $date    = $request->date;
        $perPage = $request->per_page ?? 10;
        $user    = auth()->user();

        $employeeQuery = \DB::table('employees')
            ->leftJoin('departments', 'employees.department_id', '=', 'departments.id')
            ->leftJoin('attendances', function ($join) use ($date) {
                $join->on('employees.id', '=', 'attendances.employee_id')
                     ->whereDate('attendances.date', $date);
            })
            ->where('employees.status', 'active')
            ->select([
                'employees.id as employee_id',
                'employees.first_name',
                'employees.last_name',
                'departments.name as department_name',
                'attendances.check_in',
                'attendances.check_out',
                'attendances.status',
                'attendances.overtime_hours',
                'attendances.note',
                \DB::raw('CASE WHEN attendances.id IS NOT NULL THEN 1 ELSE 0 END as is_saved'),
            ]);

        if ($this->isAdminOrHR()) {
            if ($request->filled('employee_id')) {
                $employeeQuery->where('employees.id', $request->employee_id);
            }
        } elseif ($this->isManager()) {
            $employeeQuery->where('employees.department_id', $this->managerDeptId());
        } else {
            $employeeId = $user->employee?->id;
            if (!$employeeId) return response()->json(['message' => 'Employee record not found.'], 403);
            $employeeQuery->where('employees.id', $employeeId);
        }

        $employees = $employeeQuery->paginate($perPage);

        $employees->getCollection()->transform(function ($row) use ($date) {
            $fullName = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));

            $leave = \DB::table('leaves')
                ->where('employee_id', $row->employee_id)
                ->where('status', 'approved')
                ->whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->first();

            if ($leave) {
                $defaultStatus = 'absent';
                $defaultNote   = 'On Approved Leave';
                $defaultIn     = '';
                $defaultOut    = '';
            } else {
                $defaultStatus = 'present';
                $defaultNote   = '';
                $defaultIn     = '09:00';
                $defaultOut    = '18:00';
            }

            return [
                'employee_id'    => (int) $row->employee_id,
                'name'           => !empty($fullName) ? $fullName : 'Unnamed Employee',
                'department'     => $row->department_name ?? 'N/A',
                'check_in'       => $row->check_in  ? substr($row->check_in,  0, 5) : $defaultIn,
                'check_out'      => $row->check_out ? substr($row->check_out, 0, 5) : $defaultOut,
                'status'         => $row->status ?? $defaultStatus,
                'overtime_hours' => (float) ($row->overtime_hours ?? 0),
                'note'           => $row->note ?? $defaultNote,
                'is_saved'       => (bool) $row->is_saved,
            ];
        });

        return response()->json($employees);
    }

    // ── POST /api/v1/attendance/bulk-store ────────────────────────
    public function bulkStore(Request $request): JsonResponse
    {
        // Admin/HR/Manager can bulk save
        if (!$this->isAdminOrHR() && !$this->isManager()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $request->validate([
            'date'                     => ['required', 'date'],
            'records'                  => ['required', 'array', 'min:1'],
            'records.*.employee_id'    => ['required', 'exists:employees,id'],
            'records.*.check_in'       => ['nullable', 'date_format:H:i'],
            'records.*.check_out'      => ['nullable', 'date_format:H:i'],
            'records.*.status'         => ['required', 'in:present,absent,late,half_day,holiday'],
            'records.*.overtime_hours' => ['nullable', 'numeric', 'min:0', 'max:12'],
            'records.*.note'           => ['nullable', 'string', 'max:500'],
        ]);

        $date         = $request->date;
        $updatedCount = 0;
        $deptId       = $this->isManager() ? $this->managerDeptId() : null;

        \DB::transaction(function () use ($request, $date, $deptId, &$updatedCount) {
            foreach ($request->records as $record) {
                // Manager: skip employees outside their dept
                if ($deptId) {
                    $employee = Employee::find($record['employee_id']);
                    if ($employee->department_id !== $deptId) continue;
                }

                Attendance::updateOrCreate(
                    ['employee_id' => $record['employee_id'], 'date' => $date],
                    [
                        'check_in'       => $record['check_in']       ?? null,
                        'check_out'      => $record['check_out']      ?? null,
                        'status'         => $record['status'],
                        'overtime_hours' => $record['overtime_hours'] ?? 0,
                        'note'           => $record['note']           ?? null,
                    ]
                );
                $updatedCount++;
            }
        });

        return response()->json([
            'message' => "Successfully updated attendance records for {$updatedCount} employees.",
            'count'   => $updatedCount,
        ]);
    }
    public function myCalendar(Request $request): JsonResponse
{
    $user  = auth()->user();
    $year  = (int) $request->query('year',  now()->year);
    $month = (int) $request->query('month', now()->month);
 
    $employee = $user->employee;
if (!$employee) {
    return response()->json(['message' => 'Employee record not found.'], 404);
}
 
    $start = \Carbon\Carbon::createFromDate($year, $month, 1)->startOfMonth();
    $end   = $start->copy()->endOfMonth();
    $today = \Carbon\Carbon::today();
 
    // Fetch all rows for this employee this month
    $rows = \DB::table('attendances')
        ->where('employee_id', $employee->id)
        ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
        ->get()
        ->keyBy('date');
 
    $records       = [];
    $present       = 0; $absent  = 0; $late     = 0;
    $halfDay       = 0; $onLeave = 0; $otHours  = 0.0;
    $workingDays   = 0; $attendable = 0;
 
    $period = new \DatePeriod(
        new \DateTime($start->toDateString()),
        new \DateInterval('P1D'),
        new \DateTime($end->copy()->addDay()->toDateString())
    );
 
    foreach ($period as $dt) {
        $dateStr   = $dt->format('Y-m-d');
        $dayOfWeek = (int) $dt->format('N'); // 1=Mon … 7=Sun
        $isFuture  = $dateStr > $today->toDateString();
 
        if ($isFuture) continue; // don't show future days
 
        $row = $rows[$dateStr] ?? null;
 
        if ($dayOfWeek === 7) {
            // Sunday → weekend
            $status = 'weekend';
        } elseif ($row) {
            $status = $row->status;
        } else {
            // No record yet for a past workday
            $status = 'absent';
        }
 
        // Accumulate
        if ($dayOfWeek !== 7 && $status !== 'holiday') {
            $workingDays++;
            match ($status) {
                'present'  => $present++,
                'absent'   => $absent++,
                'late'     => $late++,
                'half_day' => $halfDay++,
                'on_leave' => $onLeave++,
                default    => null,
            };
        }
 
        $wh = $row ? (float)($row->working_hours ?? 0) : null;
        $ot = $row ? (float)($row->overtime_hours ?? 0) : null;
        $otHours += $ot ?? 0;
 
        $records[] = [
            'date'           => $dateStr,
            'status'         => $status,
            'check_in'       => $row ? (isset($row->check_in)  ? substr($row->check_in,  0, 5) : null) : null,
            'check_out'      => $row ? (isset($row->check_out) ? substr($row->check_out, 0, 5) : null) : null,
            'working_hours'  => ($wh && $wh > 0) ? $wh : null,
            'overtime_hours' => ($ot && $ot > 0) ? $ot : null,
            'note'           => $row->note ?? null,
        ];
    }
 
    $attendable = $present + $absent + $late + $halfDay;
    $pct = $attendable > 0
        ? round(($present + $late + $halfDay * 0.5) / $attendable * 100)
        : 0;
 
    return response()->json([
        'year'  => $year,
        'month' => $month,
        'summary' => [
            'present'               => $present,
            'absent'                => $absent,
            'late'                  => $late,
            'half_day'              => $halfDay,
            'on_leave'              => $onLeave,
            'overtime_hours'        => round($otHours, 1),
            'working_days'          => $workingDays,
            'attendance_percentage' => $pct,
        ],
        'records' => $records,
    ]);
}
}
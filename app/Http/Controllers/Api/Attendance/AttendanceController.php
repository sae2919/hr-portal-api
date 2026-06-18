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
    return auth()->user()->hasRole('admin')
        || auth()->user()->hasRole('hr')
        || auth()->user()->hasRole('super admin')
        || auth()->user()->hasRole('super_admin');
}
    private function isManager(): bool
    {
        $user = auth()->user();
        if ($user->hasRole('admin') || $user->hasRole('hr') || auth()->user()->hasRole('super admin') || auth()->user()->hasRole('super_admin')) return false;
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

    private function isTeamLead(): bool
    {
        $user = auth()->user();
        if ($user->hasRole('admin') || $user->hasRole('hr') || auth()->user()->hasRole('super admin') || auth()->user()->hasRole('super_admin')) return false;
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
            if ($request->filled('employee_id')) {
                $query->where('employee_id', $request->employee_id);
            }
            if ($request->filled('department_id')) {
                $query->whereHas('employee', fn($q) => $q->where('department_id', $request->department_id));
            }

        } elseif ($this->isManager() || $this->isTeamLead()) {
            $myEmployeeId = $user->employee?->id;
            $query->whereHas('employee', fn($q) => $q->where('reporting_to', $myEmployeeId));

            if ($request->filled('employee_id')) {
                $query->where('employee_id', $request->employee_id);
            }

        } else {
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

        } elseif ($this->isManager() || $this->isTeamLead()) {
            $employee = Employee::find($request->employee_id);
            $myEmployeeId = $user->employee?->id;
            if ($employee->id !== $myEmployeeId && $employee->reporting_to !== $myEmployeeId) {
                return response()->json(['message' => 'You can only manage attendance for yourself or your direct reports.'], 403);
            }

        } else {
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
                'is_posted'      => $this->isAdminOrHR(),
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
        } elseif ($this->isManager() || $this->isTeamLead()) {
            $myEmployeeId = $user->employee?->id;
            if ($attendance->employee_id !== $myEmployeeId && $attendance->employee->reporting_to !== $myEmployeeId) {
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
        } elseif ($this->isManager() || $this->isTeamLead()) {
            $myEmployeeId = $user->employee?->id;
            if ($attendance->employee_id !== $myEmployeeId && $attendance->employee->reporting_to !== $myEmployeeId) {
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

        $updateData = $request->all();
        $updateData['is_posted'] = $this->isAdminOrHR();
        $attendance->update($updateData);

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
            ['check_in' => $now, 'status' => 'present', 'is_posted' => $this->isAdminOrHR()]
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
        $attendance->update(['check_out' => $now, 'is_posted' => $this->isAdminOrHR()]);

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
    } elseif ($this->isManager() || $this->isTeamLead()) {
        $employeeQuery->where('reporting_to', $user->employee?->id);
    } else {
        $employeeId = $user->employee?->id;
        if (!$employeeId) return response()->json(['message' => 'Employee record not found.'], 403);
        $employeeQuery->where('id', $employeeId);
    }

    $employees = $employeeQuery->get();

    $startDate = Carbon::create($year, $month, 1)->startOfMonth();
    $endDate = Carbon::create($year, $month, 1)->endOfMonth();

    $report = $employees->map(function (Employee $employee) use ($month, $year, $startDate, $endDate) {
        // Get all attendance records for the month
        $records = Attendance::where('employee_id', $employee->id)
                             ->whereMonth('date', $month)
                             ->whereYear('date', $year)
                             ->get()
                             ->keyBy('date');
        
        // Get approved leaves for this employee in this month
        $leaves = \DB::table('leaves')
            ->where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->where(function($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate->toDateString(), $endDate->toDateString()])
                  ->orWhereBetween('end_date', [$startDate->toDateString(), $endDate->toDateString()])
                  ->orWhere(function($q2) use ($startDate, $endDate) {
                      $q2->where('start_date', '<=', $startDate->toDateString())
                         ->where('end_date', '>=', $endDate->toDateString());
                  });
            })
            ->get();
        
        // Create a map of leave dates
        $leaveDates = [];
        foreach ($leaves as $leave) {
            $leaveStart = Carbon::parse($leave->start_date);
            $leaveEnd = Carbon::parse($leave->end_date);
            for ($d = $leaveStart->copy(); $d <= $leaveEnd; $d->addDay()) {
                $leaveDates[$d->toDateString()] = true;
            }
        }
        
        $present = 0;
        $absent = 0;
        $late = 0;
        $halfDay = 0;
        $onLeave = 0;
        $workingDaysCount = 0;
        
        // Loop through each day of the month
        $today = Carbon::today()->toDateString();
        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            $dayOfWeek = $date->dayOfWeek;
            if ($dayOfWeek == 0 || $dayOfWeek == 6) {
                continue; // Skip weekends
            }

            $dateStr = $date->toDateString();

            // Skip future dates — they haven't happened yet
            if ($dateStr > $today) {
                continue;
            }

            $workingDaysCount++;

            // Check if on leave first
            if (isset($leaveDates[$dateStr])) {
                $onLeave++;
                continue;
            }

            // Only count days that have an explicit attendance record
            // Days with NO record are simply untracked — NOT marked absent
            if (isset($records[$dateStr])) {
                $att = $records[$dateStr];
                switch ($att->status) {
                    case 'present':
                        $present++;
                        break;
                    case 'absent':
                        $absent++;
                        break;
                    case 'late':
                        $late++;
                        break;
                    case 'half_day':
                        $halfDay++;
                        break;
                }
            }
            // else: no record = untracked, do not count as absent
        }
        
        return [
            'employee_id'    => $employee->id,
            'employee_code'  => $employee->employee_code,
            'full_name'      => $employee->full_name,
            'department'     => $employee->department?->name,
            'present'        => $present,
            'absent'         => $absent,
            'late'           => $late,
            'half_day'       => $halfDay,
            'on_leave'       => $onLeave,
            'working_days'   => $workingDaysCount,
            'overtime_hours' => $records->sum('overtime_hours'),
        ];
    });

    return response()->json([
        'month' => $month, 
        'year' => $year, 
        'data' => $report
    ]);
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
            'attendances.id as attendance_id',
            'attendances.check_in',
            'attendances.check_out',
            'attendances.status',
            'attendances.overtime_hours',
            'attendances.note',
            \DB::raw('CASE WHEN attendances.id IS NOT NULL AND attendances.is_posted = 1 THEN 1 ELSE 0 END as is_saved'),
        ]);

    if ($this->isAdminOrHR()) {
        if ($request->filled('employee_id')) {
            $employeeQuery->where('employees.id', $request->employee_id);
        }
    } elseif ($this->isManager() || $this->isTeamLead()) {
        $employeeQuery->where('employees.reporting_to', $user->employee?->id);
    } else {
        $employeeId = $user->employee?->id;
        if (!$employeeId) return response()->json(['message' => 'Employee record not found.'], 403);
        $employeeQuery->where('employees.id', $employeeId);
    }

    $employees = $employeeQuery->paginate($perPage);

    // Pre-fetch ALL approved leaves for this date in ONE query (eliminates N+1)
    $employeeIdsOnPage = $employees->getCollection()->pluck('employee_id')->toArray();

    $leavesOnDate = \DB::table('leaves')
        ->whereIn('employee_id', $employeeIdsOnPage)
        ->where('status', 'approved')
        ->whereDate('start_date', '<=', $date)
        ->whereDate('end_date', '>=', $date)
        ->pluck('employee_id')
        ->flip()
        ->toArray(); // keyed by employee_id for O(1) lookup

    $employees->getCollection()->transform(function ($row) use ($date, $leavesOnDate) {
        $fullName = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));

        // Check if employee is on approved leave for this date (O(1) lookup, no DB hit)
        if (isset($leavesOnDate[$row->employee_id])) {
            return [
                'employee_id'    => (int) $row->employee_id,
                'name'           => !empty($fullName) ? $fullName : 'Unnamed Employee',
                'department'     => $row->department_name ?? 'N/A',
                'check_in'       => '',
                'check_out'      => '',
                'status'         => 'on_leave',
                'overtime_hours' => 0,
                'note'           => 'On Approved Leave',
                'is_saved'       => true,
            ];
        }

        // If attendance record exists, use it
        if ($row->attendance_id !== null) {
            return [
                'employee_id'    => (int) $row->employee_id,
                'name'           => !empty($fullName) ? $fullName : 'Unnamed Employee',
                'department'     => $row->department_name ?? 'N/A',
                'check_in'       => $row->check_in ? substr($row->check_in, 0, 5) : '',
                'check_out'      => $row->check_out ? substr($row->check_out, 0, 5) : '',
                'status'         => $row->status ?? 'present',
                'overtime_hours' => (float) ($row->overtime_hours ?? 0),
                'note'           => $row->note ?? '',
                'is_saved'       => (bool) $row->is_saved,
            ];
        }

        // Default for no attendance record and not on leave
        return [
            'employee_id'    => (int) $row->employee_id,
            'name'           => !empty($fullName) ? $fullName : 'Unnamed Employee',
            'department'     => $row->department_name ?? 'N/A',
            'check_in'       => '09:00',
            'check_out'      => '18:00',
            'status'         => 'present',
            'overtime_hours' => 0,
            'note'           => '',
            'is_saved'       => false,
        ];
    });

    return response()->json($employees);
}

    // ── POST /api/v1/attendance/bulk-store ────────────────────────

    public function bulkStore(Request $request): JsonResponse
    {
        if (!$this->isAdminOrHR() && !$this->isManager() && !$this->isTeamLead()) {
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

        $user         = auth()->user();
        $date         = $request->date;
        $updatedCount = 0;
        $myEmployeeId = ($this->isManager() || $this->isTeamLead()) ? $user->employee?->id : null;

        \DB::transaction(function () use ($request, $date, $myEmployeeId, &$updatedCount) {
            foreach ($request->records as $record) {
                if ($myEmployeeId) {
                    $employee = Employee::find($record['employee_id']);
                    if ($employee->reporting_to !== $myEmployeeId) continue;
                }

                Attendance::updateOrCreate(
                    ['employee_id' => $record['employee_id'], 'date' => $date],
                    [
                        'check_in'       => $record['check_in']       ?? null,
                        'check_out'      => $record['check_out']      ?? null,
                        'status'         => $record['status'],
                        'overtime_hours' => $record['overtime_hours'] ?? 0,
                        'note'           => $record['note']           ?? null,
                        'is_posted'      => true,
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

    // ── GET /api/v1/attendance/my-calendar ────────────────────────

public function myCalendar(Request $request): JsonResponse
{
    $user  = auth()->user();
    $year  = (int) $request->query('year',  now()->year);
    $month = (int) $request->query('month', now()->month);

    $employee = $user->employee;
    if (!$employee) {
        return response()->json(['message' => 'Employee record not found.'], 404);
    }

    $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
    $end   = $start->copy()->endOfMonth();
    $today = Carbon::today();

    // Get all attendance records for this employee
    $rows = \DB::table('attendances')
        ->where('employee_id', $employee->id)
        ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
        ->get()
        ->keyBy('date');

    // Get all approved leaves for this employee in this month
    $leaves = \DB::table('leaves')
        ->where('employee_id', $employee->id)
        ->where('status', 'approved')
        ->where(function($q) use ($start, $end) {
            $q->whereBetween('start_date', [$start->toDateString(), $end->toDateString()])
              ->orWhereBetween('end_date', [$start->toDateString(), $end->toDateString()])
              ->orWhere(function($q2) use ($start, $end) {
                  $q2->where('start_date', '<=', $start->toDateString())
                     ->where('end_date', '>=', $end->toDateString());
              });
        })
        ->get();

    // Create a map of dates that are on leave
    $leaveDates = [];
    foreach ($leaves as $leave) {
        $leaveStart = Carbon::parse($leave->start_date);
        $leaveEnd = Carbon::parse($leave->end_date);
        for ($d = $leaveStart->copy(); $d <= $leaveEnd; $d->addDay()) {
            $leaveDates[$d->toDateString()] = true;
        }
    }

    $records     = [];
    $present     = 0; $absent  = 0; $late    = 0;
    $halfDay     = 0; $onLeave = 0; $otHours = 0.0;
    $workingDays = 0;

    $period = new \DatePeriod(
        new \DateTime($start->toDateString()),
        new \DateInterval('P1D'),
        new \DateTime($end->copy()->addDay()->toDateString())
    );

    foreach ($period as $dt) {
        $dateStr   = $dt->format('Y-m-d');
        $dayOfWeek = (int) $dt->format('N'); // 1=Mon … 7=Sun
        $isFuture  = $dateStr > $today->toDateString();

        if ($isFuture) continue;

        // Check if on leave FIRST (overrides attendance)
        if (isset($leaveDates[$dateStr])) {
            $status = 'on_leave';
            if ($dayOfWeek !== 7) {
                $workingDays++;
                $onLeave++;
            }
            $records[] = [
                'date'           => $dateStr,
                'status'         => 'on_leave',
                'check_in'       => null,
                'check_out'      => null,
                'working_hours'  => null,
                'overtime_hours' => null,
                'note'           => 'On Approved Leave',
            ];
            continue;
        }

        if ($dayOfWeek === 7) {
            $status = 'weekend';
            $records[] = [
                'date'           => $dateStr,
                'status'         => 'weekend',
                'check_in'       => null,
                'check_out'      => null,
                'working_hours'  => null,
                'overtime_hours' => null,
                'note'           => null,
            ];
            continue;
        }

        $workingDays++;
        $row = $rows[$dateStr] ?? null;

        if ($row) {
            $status = $row->status;
            match ($status) {
                'present'  => $present++,
                'absent'   => $absent++,
                'late'     => $late++,
                'half_day' => $halfDay++,
                default    => null,
            };
            $wh = (float) ($row->working_hours ?? 0);
            $ot = (float) ($row->overtime_hours ?? 0);
            $otHours += $ot;
        } else {
            $status = 'absent';
            $absent++;
            $wh = null;
            $ot = null;
        }

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

    $attendable = $present + $absent + $late + $halfDay + $onLeave;
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

    // ── GET /api/v1/attendance/month-leaves ───────────────────────
    //
    // Returns all approved leaves overlapping the requested month.
    // Used by the admin/HR Leave Calendar and the Team Lead calendar
    // to show a popup of who is on leave on a given day.
    //
    // Access:
    //   Admin / HR    → all employees
    //   Manager / TL  → their department only
    //   Employee      → 403

    public function monthLeaves(Request $request): JsonResponse
    {
        if (!auth()->check()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $year  = (int) $request->query('year',  now()->year);
        $month = (int) $request->query('month', now()->month);

        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        $query = \DB::table('leaves')
            ->join('employees', 'leaves.employee_id', '=', 'employees.id')
            ->leftJoin('departments', 'employees.department_id', '=', 'departments.id')
            ->leftJoin('leave_types', 'leaves.leave_type_id', '=', 'leave_types.id')
            ->where('leaves.status', 'approved')
            ->where('leaves.start_date', '<=', $end->toDateString())
            ->where('leaves.end_date',   '>=', $start->toDateString())
            ->select([
                'leaves.id as leave_id',
                'leaves.employee_id',
                'leaves.start_date',
                'leaves.end_date',
                \DB::raw("CONCAT(employees.first_name, ' ', employees.last_name) as employee_name"),
                'departments.name as department',
                'leave_types.name as leave_type',
            ]);

        // Manager / Team Lead: scope to own reporting employees only
        if (!$this->isAdminOrHR()) {
            $myEmployeeId = auth()->user()->employee?->id;
            if ($myEmployeeId) {
                $query->where('employees.reporting_to', $myEmployeeId);
            }
        }

        $leaves = $query->orderBy('leaves.start_date')->get();

        return response()->json([
            'year'  => $year,
            'month' => $month,
            'data'  => $leaves,
        ]);
    }
}
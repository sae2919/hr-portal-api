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
    // ── Helper: check if the current user is an admin ─────────────
    private function isAdmin(): bool
    {
        $user = auth()->user();
        return $user && ($user->role === 'admin' || $user->tokenCan('manage attendance'));
    }

    // ── GET /api/v1/attendance ────────────────────────────────────
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Attendance::with(['employee.department']);

        // Non-admins are always scoped to their own record
        if (!$this->isAdmin()) {
            $employeeId = auth()->user()->employee?->id;
            if (!$employeeId) {
                abort(403, 'Employee record not found.');
            }
            $query->where('employee_id', $employeeId);
        } elseif ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        if ($request->filled('month') && $request->filled('year')) {
            $query->whereMonth('date', $request->month)
                  ->whereYear('date', $request->year);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($this->isAdmin() && $request->filled('department_id')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('department_id', $request->department_id);
            });
        }

        $attendance = $query
            ->orderBy('date', 'desc')
            ->orderBy('employee_id')
            ->paginate($request->per_page ?? 10);

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

        // Non-admins can only save their own attendance
        if (!$this->isAdmin()) {
            $employeeId = auth()->user()->employee?->id;
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
        if (!$this->isAdmin()) {
            $employeeId = auth()->user()->employee?->id;
            if ($attendance->employee_id !== $employeeId) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }
        }

        return response()->json([
            'data' => new AttendanceResource($attendance->load('employee.department')),
        ]);
    }

    // ── PUT /api/v1/attendance/{attendance} ───────────────────────
    public function update(Request $request, Attendance $attendance): JsonResponse
    {
        if (!$this->isAdmin()) {
            $employeeId = auth()->user()->employee?->id;
            if ($attendance->employee_id !== $employeeId) {
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
        if (!$this->isAdmin()) {
            $employeeId = auth()->user()->employee?->id;
            if ($attendance->employee_id !== $employeeId) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }
        }

        $attendance->delete();
        return response()->json(['message' => 'Attendance deleted successfully.']);
    }

    // ── POST /api/v1/attendance/checkin ───────────────────────────
    public function checkIn(Request $request): JsonResponse
    {
        $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
        ]);

        // Non-admins can only check in as themselves
        if (!$this->isAdmin()) {
            $employeeId = auth()->user()->employee?->id;
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
        $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
        ]);

        // Non-admins can only check out as themselves
        if (!$this->isAdmin()) {
            $employeeId = auth()->user()->employee?->id;
            if ((int) $request->employee_id !== $employeeId) {
                return response()->json(['message' => 'You can only check out for yourself.'], 403);
            }
        }

        $today = Carbon::today()->toDateString();

        $attendance = Attendance::where('employee_id', $request->employee_id)
                                ->whereDate('date', $today)
                                ->first();

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

        $employeeQuery = Employee::with(['department'])->where('status', 'active');

        // Non-admins only see their own monthly summary
        if (!$this->isAdmin()) {
            $employeeId = auth()->user()->employee?->id;
            if (!$employeeId) {
                return response()->json(['message' => 'Employee record not found.'], 403);
            }
            $employeeQuery->where('id', $employeeId);
        } elseif ($request->filled('employee_id')) {
            $employeeQuery->where('id', $request->employee_id);
        }

        $employees = $employeeQuery->paginate(10);

        $report = $employees->getCollection()->map(function (Employee $employee) use ($month, $year) {
            // Fixed: use get() not paginate() inside map
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

        return response()->json([
            'month' => $month,
            'year'  => $year,
            'data'  => $report,
        ]);
    }

    // ── GET /api/v1/attendance/worksheet ─────────────────────────
    public function worksheet(Request $request): JsonResponse
    {
        $request->validate([
            'date'     => ['required', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $date    = $request->date;
        $perPage = $request->per_page ?? 10;

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

        // Non-admins only see their own row
        if (!$this->isAdmin()) {
            $employeeId = auth()->user()->employee?->id;
            if (!$employeeId) {
                return response()->json(['message' => 'Employee record not found.'], 403);
            }
            $employeeQuery->where('employees.id', $employeeId);
        } elseif ($request->filled('employee_id')) {
            $employeeQuery->where('employees.id', $request->employee_id);
        }

        $employees = $employeeQuery->paginate($perPage);

        $employees->getCollection()->transform(function ($row) use ($date) {
            $fullName = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));

            // Check for approved leave covering this date
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
        // Only admins can bulk-save the full sheet
        if (!$this->isAdmin()) {
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

        \DB::transaction(function () use ($request, $date, &$updatedCount) {
            foreach ($request->records as $record) {
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
}
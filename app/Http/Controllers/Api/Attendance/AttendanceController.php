<?php

namespace App\Http\Controllers\Api\Attendance;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    // GET /api/v1/attendance
    public function index(Request $request): JsonResponse
    {
        $query = Attendance::with(['employee.department']);

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        if ($request->filled('month') && $request->filled('year')) {
            $query->whereMonth('date', $request->month)
                  ->whereYear('date', $request->year);
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('department_id')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('department_id', $request->department_id);
            });
        }

        $attendance = $query->orderBy('date', 'desc')
                            ->orderBy('employee_id')
                            ->get();

        return response()->json([
            'data' => AttendanceResource::collection($attendance),
        ]);
    }

    // POST /api/v1/attendance
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

        $attendance = Attendance::updateOrCreate(
            [
                'employee_id' => $request->employee_id,
                'date'        => $request->date,
            ],
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

    // GET /api/v1/attendance/{attendance}
    public function show(Attendance $attendance): JsonResponse
    {
        return response()->json([
            'data' => new AttendanceResource($attendance->load('employee.department')),
        ]);
    }

    // PUT /api/v1/attendance/{attendance}
    public function update(Request $request, Attendance $attendance): JsonResponse
    {
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

    // DELETE /api/v1/attendance/{attendance}
    public function destroy(Attendance $attendance): JsonResponse
    {
        $attendance->delete();
        return response()->json(['message' => 'Attendance deleted successfully.']);
    }

    // POST /api/v1/attendance/checkin
    public function checkIn(Request $request): JsonResponse
    {
        $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
        ]);

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

    // POST /api/v1/attendance/checkout
    public function checkOut(Request $request): JsonResponse
    {
        $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
        ]);

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

    // GET /api/v1/attendance/report/monthly
    public function monthlyReport(Request $request): JsonResponse
    {
        $month = $request->get('month', Carbon::now()->month);
        $year  = $request->get('year',  Carbon::now()->year);

        $employees = Employee::with(['department'])
                             ->where('status', 'active')
                             ->get();

        $report = $employees->map(function (Employee $employee) use ($month, $year) {
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
            'month'  => $month,
            'year'   => $year,
            'data'   => $report,
        ]);
    }
}
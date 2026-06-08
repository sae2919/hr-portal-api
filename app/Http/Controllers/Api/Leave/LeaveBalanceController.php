<?php

namespace App\Http\Controllers\Api\Leave;

use App\Http\Controllers\Controller;
use App\Http\Resources\LeaveBalanceResource;
use App\Http\Resources\EmployeeLeaveBalancesResource;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class LeaveBalanceController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $isAdmin = $user->role === 'admin' || $user->tokenCan('manage leaves');

        $year = $request->get('year', Carbon::now()->year);

        // Check if single employee's balances are requested
        if ($request->has('employee_id') && !empty($request->get('employee_id')) && $request->get('employee_id') !== 'all') {
            $employee_id = $request->get('employee_id');
            if (!$isAdmin && $employee_id != ($user->employee_id ?? $user->employee->id)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $balances = LeaveBalance::whereHas('leaveType', function ($q) {
                    $q->where('status', 'active');
                })
                ->with('leaveType')
                ->where('employee_id', $employee_id)
                ->where('year', $year)
                ->paginate($request->per_page ?? 10);

            return LeaveBalanceResource::collection($balances);
        }

        // Otherwise, if not admin, return current user's balances (simple array)
        if (!$isAdmin) {
            $employee_id = $user->employee_id ?? $user->employee->id;
            $balances = LeaveBalance::whereHas('leaveType', function ($q) {
                    $q->where('status', 'active');
                })
                ->with('leaveType')
                ->where('employee_id', $employee_id)
                ->where('year', $year)
                ->paginate($request->per_page ?? 10);

            return LeaveBalanceResource::collection($balances);
        }

        // Admin viewing all: Grouped and paginated by Employee!
        $query = Employee::with([
            'department',
            'leaveBalances' => function ($q) use ($year) {
                $q->where('year', $year)->whereHas('leaveType', function ($query) {
                    $query->where('status', 'active');
                })->with('leaveType');
            }
        ])->where('status', 'active');

        // Apply filters
        if ($request->has('search') && !empty($request->get('search'))) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('employee_code', 'like', "%{$search}%");
            });
        }

        if ($request->has('department_id') && !empty($request->get('department_id'))) {
            $query->where('department_id', $request->get('department_id'));
        }

        if ($request->has('leave_type_id') && !empty($request->get('leave_type_id'))) {
            $leaveTypeId = $request->get('leave_type_id');
            $query->whereHas('leaveBalances', function ($q) use ($leaveTypeId, $year) {
                $q->where('leave_type_id', $leaveTypeId)->where('year', $year)->whereHas('leaveType', function ($query) {
                    $query->where('status', 'active');
                });
            });
            $query->with([
                'leaveBalances' => function ($q) use ($leaveTypeId, $year) {
                    $q->where('leave_type_id', $leaveTypeId)->where('year', $year)->whereHas('leaveType', function ($query) {
                        $query->where('status', 'active');
                    })->with('leaveType');
                }
            ]);
        }

        $employees = $query->paginate($request->per_page ?? 10);

        return EmployeeLeaveBalancesResource::collection($employees);
    }

    // Initialize balances for all employees for a given year
    public function initialize(
        Request $request
    ): JsonResponse {

        $year = $request->get(
            'year',
            Carbon::now()->year
        );

        $types = LeaveType::where(
            'status',
            'active'
        )->get();

        $emps = Employee::where(
            'status',
            'active'
        )->get();

        foreach ($emps as $emp) {
            foreach ($types as $type) {
                // Check if balance already exists
                $exists = LeaveBalance::where('employee_id', $emp->id)
                    ->where('leave_type_id', $type->id)
                    ->where('year', $year)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $carryForwardDays = 0.0;
                if ($type->carry_forward) {
                    $prevYear = $year - 1;
                    $prevBalance = LeaveBalance::where('employee_id', $emp->id)
                        ->where('leave_type_id', $type->id)
                        ->where('year', $prevYear)
                        ->first();
                    if ($prevBalance) {
                        $carryForwardDays = (float) $prevBalance->remaining_days;
                    }
                }

                $totalDays = (float) $type->days_per_year + $carryForwardDays;

                LeaveBalance::create([
                    'employee_id'    => $emp->id,
                    'leave_type_id'  => $type->id,
                    'year'           => $year,
                    'total_days'     => $totalDays,
                    'used_days'      => 0,
                    'remaining_days' => $totalDays,
                ]);
            }
        }

        return response()->json([

            'message' =>
                "Leave balances initialized for {$year}.",

        ]);
    }
}
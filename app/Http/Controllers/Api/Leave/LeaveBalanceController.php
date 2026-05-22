<?php

namespace App\Http\Controllers\Api\Leave;

use App\Http\Controllers\Controller;
use App\Http\Resources\LeaveBalanceResource;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class LeaveBalanceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $employee_id = $request->get('employee_id');
        $year        = $request->get('year', Carbon::now()->year);

        $balances = LeaveBalance::with('leaveType')
                                ->where('employee_id', $employee_id)
                                ->where('year', $year)
                                ->get();

        return response()->json(['data' => LeaveBalanceResource::collection($balances)]);
    }

    // Initialize balances for all employees for a given year
    public function initialize(Request $request): JsonResponse
    {
        $year  = $request->get('year', Carbon::now()->year);
        $types = LeaveType::where('status', 'active')->get();
        $emps  = Employee::where('status', 'active')->get();

        foreach ($emps as $emp) {
            foreach ($types as $type) {
                LeaveBalance::firstOrCreate(
                    ['employee_id' => $emp->id, 'leave_type_id' => $type->id, 'year' => $year],
                    [
                        'total_days'     => $type->days_per_year,
                        'used_days'      => 0,
                        'remaining_days' => $type->days_per_year,
                    ]
                );
            }
        }

        return response()->json(['message' => "Leave balances initialized for {$year}."]);
    }
}
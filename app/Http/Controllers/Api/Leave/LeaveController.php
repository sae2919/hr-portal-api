<?php

namespace App\Http\Controllers\Api\Leave;

use App\Http\Controllers\Controller;
use App\Http\Resources\LeaveResource;
use App\Models\Leave;
use App\Models\LeaveBalance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class LeaveController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Leave::with(['employee.department', 'leaveType']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }
        if ($request->filled('leave_type_id')) {
            $query->where('leave_type_id', $request->leave_type_id);
        }
        if ($request->filled('department_id')) {
            $query->whereHas('employee', fn($q) =>
                $q->where('department_id', $request->department_id)
            );
        }

        $leaves = $query->orderBy('created_at', 'desc')->get();

        return response()->json(['data' => LeaveResource::collection($leaves)]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'employee_id'   => ['required', 'exists:employees,id'],
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'start_date'    => ['required', 'date'],
            'end_date'      => ['required', 'date', 'gte:start_date'],
            'reason'        => ['required', 'string', 'min:10'],
        ]);

        $days = Leave::calculateDays($request->start_date, $request->end_date);

        // Check balance
        $balance = LeaveBalance::where('employee_id', $request->employee_id)
                               ->where('leave_type_id', $request->leave_type_id)
                               ->where('year', Carbon::now()->year)
                               ->first();

        if ($balance && $balance->remaining_days < $days) {
            return response()->json([
                'message' => "Insufficient leave balance. Available: {$balance->remaining_days} days.",
            ], 422);
        }

        $leave = Leave::create([
            'employee_id'   => $request->employee_id,
            'leave_type_id' => $request->leave_type_id,
            'start_date'    => $request->start_date,
            'end_date'      => $request->end_date,
            'days'          => $days,
            'reason'        => $request->reason,
            'status'        => 'pending',
        ]);

        return response()->json([
            'message' => 'Leave application submitted.',
            'data'    => new LeaveResource($leave->load(['employee.department', 'leaveType'])),
        ], 201);
    }

    public function show(Leave $leave): JsonResponse
    {
        return response()->json([
            'data' => new LeaveResource($leave->load(['employee.department', 'leaveType'])),
        ]);
    }

    public function approve(Request $request, Leave $leave): JsonResponse
    {
        if ($leave->status !== 'pending') {
            return response()->json(['message' => 'Only pending leaves can be approved.'], 422);
        }

        $leave->update([
            'status'      => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        // Deduct from balance
        LeaveBalance::where('employee_id', $leave->employee_id)
                    ->where('leave_type_id', $leave->leave_type_id)
                    ->where('year', $leave->start_date->year)
                    ->increment('used_days', $leave->days);

        LeaveBalance::where('employee_id', $leave->employee_id)
                    ->where('leave_type_id', $leave->leave_type_id)
                    ->where('year', $leave->start_date->year)
                    ->decrement('remaining_days', $leave->days);

        return response()->json([
            'message' => 'Leave approved.',
            'data'    => new LeaveResource($leave->load(['employee', 'leaveType'])),
        ]);
    }

    public function reject(Request $request, Leave $leave): JsonResponse
    {
        $request->validate([
            'rejection_reason' => ['required', 'string', 'min:5'],
        ]);

        if ($leave->status !== 'pending') {
            return response()->json(['message' => 'Only pending leaves can be rejected.'], 422);
        }

        $leave->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->rejection_reason,
            'approved_by'      => auth()->id(),
            'approved_at'      => now(),
        ]);

        return response()->json([
            'message' => 'Leave rejected.',
            'data'    => new LeaveResource($leave->load(['employee', 'leaveType'])),
        ]);
    }

    public function destroy(Leave $leave): JsonResponse
    {
        if ($leave->status === 'approved') {
            return response()->json(['message' => 'Cannot delete approved leave.'], 422);
        }
        $leave->delete();
        return response()->json(['message' => 'Leave deleted.']);
    }
}
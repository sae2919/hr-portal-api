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
    public function index(Request $request)
    {
        $user  = auth()->user();
        $query = Leave::with(['employee.department', 'leaveType']);

        // Enforce strict table column role check
        $isAdmin = $user && $user->role === 'admin';

        if (!$isAdmin) {
            // Force isolation down to the current session profile
            $query->where('employee_id', $user->employee_id ?? $user->employee->id);
        } elseif ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('leave_type_id')) {
            $query->where('leave_type_id', $request->leave_type_id);
        }

        if ($request->filled('department_id')) {
            $query->whereHas('employee', fn($q) =>
                $q->where('department_id', $request->department_id)
            );
        }

        $leaves = $query
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 10);

        return LeaveResource::collection($leaves);
    }

    public function store(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        // FIXED: Enforce strict table role strings to overwrite wildcard API token abilities
        $isAdmin = $user && $user->role === 'admin';

        // Force employee ID context onto non-administrative connections
        if (!$isAdmin) {
            $request->merge(['employee_id' => $user->employee_id ?? $user->employee->id]);
        }

        $request->validate([
            'employee_id'   => ['required', 'exists:employees,id'],
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'start_date'    => ['required', 'date'],
            'end_date'      => ['required', 'date', 'gte:start_date'],
            'reason'        => ['required', 'string', 'min:10'],
        ]);

        $startDate = Carbon::parse($request->start_date);
        $days = Leave::calculateDays($request->start_date, $request->end_date);

        // Validate Leave Balance
        $balance = LeaveBalance::where('employee_id', $request->employee_id)
                               ->where('leave_type_id', $request->leave_type_id)
                               ->where('year', $startDate->year)
                               ->first();

        if ($balance && $balance->remaining_days < $days) {
            return response()->json([
                'message' => "Insufficient leave balance. Available: {$balance->remaining_days} days.",
            ], 422);
        }

        $leave = null;

        // FIXED: Explicitly map parameters to force strict execution constraints
        $status     = $isAdmin ? 'approved' : 'pending';
        $approvedBy = $isAdmin ? auth()->id() : null;
        $approvedAt = $isAdmin ? now() : null;

        \DB::transaction(function () use ($request, $days, $startDate, $status, $approvedBy, $approvedAt, &$leave) {
            $leave = Leave::create([
                'employee_id'   => $request->employee_id,
                'leave_type_id' => $request->leave_type_id,
                'start_date'    => $request->start_date,
                'end_date'      => $request->end_date,
                'days'          => $days,
                'reason'        => $request->reason,
                'status'        => $status,
                'approved_by'   => $approvedBy,
                'approved_at'   => $approvedAt,
            ]);

            // Deduct balance IMMEDIATELY to hold the requested days
            LeaveBalance::where('employee_id', $request->employee_id)
                        ->where('leave_type_id', $request->leave_type_id)
                        ->where('year', $startDate->year)
                        ->increment('used_days', $days);

            LeaveBalance::where('employee_id', $request->employee_id)
                        ->where('leave_type_id', $request->leave_type_id)
                        ->where('year', $startDate->year)
                        ->decrement('remaining_days', $days);
        });

        $message = $isAdmin 
            ? 'Leave added and automatically approved by Admin.' 
            : 'Leave application submitted successfully. Pending approval.';

        return response()->json([
            'message' => $message,
            'data'    => new LeaveResource($leave->load(['employee.department', 'leaveType'])),
        ], 201);
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

        return response()->json([
            'message' => 'Leave approved successfully.',
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

        \DB::transaction(function () use ($request, $leave) {
            $leave->update([
                'status'           => 'rejected',
                'rejection_reason' => $request->rejection_reason,
                'approved_by'      => auth()->id(),
                'approved_at'      => now(),
            ]);

            // REFUND DAYS back to balance if rejected
            $year = Carbon::parse($leave->start_date)->year;

            LeaveBalance::where('employee_id', $leave->employee_id)
                        ->where('leave_type_id', $leave->leave_type_id)
                        ->where('year', $year)
                        ->decrement('used_days', $leave->days);

            LeaveBalance::where('employee_id', $leave->employee_id)
                        ->where('leave_type_id', $leave->leave_type_id)
                        ->where('year', $year)
                        ->increment('remaining_days', $leave->days);
        });

        return response()->json([
            'message' => 'Leave application rejected. Allocation days refunded.',
            'data'    => new LeaveResource($leave->load(['employee', 'leaveType'])),
        ]);
    }

    public function destroy(Leave $leave): JsonResponse
    {
        if ($leave->status === 'approved') {
            return response()->json(['message' => 'Cannot delete approved leaves.'], 422);
        }

        \DB::transaction(function () use ($leave) {
            if ($leave->status === 'pending') {
                $year = Carbon::parse($leave->start_date)->year;

                LeaveBalance::where('employee_id', $leave->employee_id)
                            ->where('leave_type_id', $leave->leave_type_id)
                            ->where('year', $year)
                            ->decrement('used_days', $leave->days);

                LeaveBalance::where('employee_id', $leave->employee_id)
                            ->where('leave_type_id', $leave->leave_type_id)
                            ->where('year', $year)
                            ->increment('remaining_days', $leave->days);
            }

            $leave->delete();
        });

        return response()->json(['message' => 'Leave entry dropped successfully.']);
    }
}
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

    public function index(Request $request)
    {
        $user  = auth()->user();
        $query = Leave::with(['employee.department', 'leaveType']);

        if ($this->isAdminOrHR()) {
            // Admin/HR: see all leaves
            if ($request->filled('employee_id')) {
                $query->where('employee_id', $request->employee_id);
            }
            if ($request->filled('department_id')) {
                $query->whereHas('employee', fn($q) => $q->where('department_id', $request->department_id));
            }

        } elseif ($this->isManager()) {
            // Manager: only their department's leaves
            $query->whereHas('employee', fn($q) => $q->where('department_id', $this->managerDeptId()));

            if ($request->filled('employee_id')) {
                $query->where('employee_id', $request->employee_id);
            }

        } else {
            // Employee: only their own leaves
            $query->where('employee_id', $user->employee?->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('leave_type_id')) {
            $query->where('leave_type_id', $request->leave_type_id);
        }

        $leaves = $query->orderBy('created_at', 'desc')->paginate($request->per_page ?? 10);

        return LeaveResource::collection($leaves);
    }

    public function store(Request $request): JsonResponse
    {
        $user         = auth()->user();
        $isAdminOrHR  = $this->isAdminOrHR();

        // Force employee_id for non-admin/hr
        if (!$isAdminOrHR) {
            $request->merge(['employee_id' => $user->employee?->id]);
        }

        $request->validate([
            'employee_id'   => ['required', 'exists:employees,id'],
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'start_date'    => ['required', 'date'],
            'end_date'      => ['required', 'date', 'gte:start_date'],
            'reason'        => ['required', 'string', 'min:10'],
        ]);

        $startDate = Carbon::parse($request->start_date);
        $days      = Leave::calculateDays($request->start_date, $request->end_date);

        $balance = LeaveBalance::where('employee_id', $request->employee_id)
                               ->where('leave_type_id', $request->leave_type_id)
                               ->where('year', $startDate->year)
                               ->first();

        if ($balance && $balance->remaining_days < $days) {
            return response()->json([
                'message' => "Insufficient leave balance. Available: {$balance->remaining_days} days.",
            ], 422);
        }

        $leave      = null;
        $status     = $isAdminOrHR ? 'approved' : 'pending';
        $approvedBy = $isAdminOrHR ? auth()->id() : null;
        $approvedAt = $isAdminOrHR ? now() : null;

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

            LeaveBalance::where('employee_id', $request->employee_id)
                        ->where('leave_type_id', $request->leave_type_id)
                        ->where('year', $startDate->year)
                        ->increment('used_days', $days);

            LeaveBalance::where('employee_id', $request->employee_id)
                        ->where('leave_type_id', $request->leave_type_id)
                        ->where('year', $startDate->year)
                        ->decrement('remaining_days', $days);
        });

        $message = $isAdminOrHR
            ? 'Leave added and automatically approved.'
            : 'Leave application submitted. Pending approval.';

        return response()->json([
            'message' => $message,
            'data'    => new LeaveResource($leave->load(['employee.department', 'leaveType'])),
        ], 201);
    }

    public function approve(Request $request, Leave $leave): JsonResponse
    {
        // Admin, HR, or Manager (only for their dept) can approve
        if (!$this->isAdminOrHR()) {
            if ($this->isManager()) {
                if ($leave->employee->department_id !== $this->managerDeptId()) {
                    return response()->json(['message' => 'Unauthorized.'], 403);
                }
            } else {
                return response()->json(['message' => 'Unauthorized.'], 403);
            }
        }

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
        // Admin, HR, or Manager (only for their dept) can reject
        if (!$this->isAdminOrHR()) {
            if ($this->isManager()) {
                if ($leave->employee->department_id !== $this->managerDeptId()) {
                    return response()->json(['message' => 'Unauthorized.'], 403);
                }
            } else {
                return response()->json(['message' => 'Unauthorized.'], 403);
            }
        }

        $request->validate(['rejection_reason' => ['required', 'string', 'min:5']]);

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
            'message' => 'Leave rejected. Balance refunded.',
            'data'    => new LeaveResource($leave->load(['employee', 'leaveType'])),
        ]);
    }

    public function destroy(Leave $leave): JsonResponse
    {
        $user = auth()->user();

        // Only admin/HR or the employee themselves can delete
        if (!$this->isAdminOrHR() && $leave->employee_id !== $user->employee?->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

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

        return response()->json(['message' => 'Leave deleted successfully.']);
    }
}
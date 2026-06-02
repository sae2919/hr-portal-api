<?php

namespace App\Http\Controllers\Api\Leave;

use App\Http\Controllers\Controller;
use App\Http\Resources\LeaveResource;
use App\Models\Leave;
use App\Models\LeaveBalance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LeaveController extends Controller
{
    // ── Role helpers ──────────────────────────────────────────────
    private function isAdminOrHR(): bool
    {
        return auth()->user()->hasRole('admin') || auth()->user()->hasRole('hr');
    }

    private function isTeamLead(): bool
    {
        $user = auth()->user();
        if ($user->hasRole('admin') || $user->hasRole('hr')) return false;
        if ($user->hasRole('manager') || $user->hasRole('team_lead')) return true;

        $employee = $user->employee;
        if ($employee) {
            $level = strtolower($employee->position_level ?? '');
            if ($level === 'manager' || $level === 'team_lead') return true;

            $designation = strtolower($employee->designation?->title ?? '');
            if (str_contains($designation, 'manager') || str_contains($designation, 'team lead') || str_contains($designation, 'lead')) return true;
        }
        return false;
    }

    private function myDeptId(): ?int
    {
        return auth()->user()->employee?->department_id;
    }

    // ── Index ─────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $user  = auth()->user();
        $query = Leave::with(['employee.department', 'leaveType', 'teamLead']);

        if ($this->isAdminOrHR()) {
            // Admin/HR: see everything
            if ($request->filled('employee_id'))  $query->where('employee_id', $request->employee_id);
            if ($request->filled('department_id')) {
                $query->whereHas('employee', fn($q) => $q->where('department_id', $request->department_id));
            }
        } elseif ($this->isTeamLead()) {
            // Team lead: only their dept
            $query->whereHas('employee', fn($q) => $q->where('department_id', $this->myDeptId()));
            if ($request->filled('employee_id')) $query->where('employee_id', $request->employee_id);
        } else {
            // Employee: own leaves only
            $query->where('employee_id', $user->employee?->id);
        }

        if ($request->filled('status'))        $query->where('status', $request->status);
        if ($request->filled('leave_type_id')) $query->where('leave_type_id', $request->leave_type_id);
        if ($request->filled('team_lead_status')) $query->where('team_lead_status', $request->team_lead_status);

        $leaves = $query->orderBy('created_at', 'desc')
                        ->paginate($request->per_page ?? 10);

        return LeaveResource::collection($leaves);
    }

    // ── Store (apply) ─────────────────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        $user        = auth()->user();
        $isAdminOrHR = $this->isAdminOrHR();

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

        $leave = null;

        // Admin/HR applying = auto approved (skip TL step)
        $status           = $isAdminOrHR ? 'approved' : 'pending';
        $teamLeadStatus   = $isAdminOrHR ? 'approved' : 'pending';
        $approvedBy       = $isAdminOrHR ? auth()->id() : null;
        $approvedAt       = $isAdminOrHR ? now()        : null;

        DB::transaction(function () use ($request, $days, $startDate, $status, $teamLeadStatus, $approvedBy, $approvedAt, &$leave) {
            $leave = Leave::create([
                'employee_id'      => $request->employee_id,
                'leave_type_id'    => $request->leave_type_id,
                'start_date'       => $request->start_date,
                'end_date'         => $request->end_date,
                'days'             => $days,
                'reason'           => $request->reason,
                'status'           => $status,
                'team_lead_status' => $teamLeadStatus,
                'approved_by'      => $approvedBy,
                'approved_at'      => $approvedAt,
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
            : 'Leave application submitted. Awaiting team lead review.';

        return response()->json([
            'message' => $message,
            'data'    => new LeaveResource($leave->load(['employee.department', 'leaveType', 'teamLead'])),
        ], 201);
    }

    // ── Team Lead Approve ─────────────────────────────────────────
    public function teamLeadApprove(Request $request, Leave $leave): JsonResponse
    {
        if (!$this->isTeamLead() && !$this->isAdminOrHR()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // Team lead can only act on their own dept
        if ($this->isTeamLead() && !$this->isAdminOrHR()) {
            if ($leave->employee->department_id !== $this->myDeptId()) {
                return response()->json(['message' => 'Unauthorized — not your department.'], 403);
            }
        }

        if ($leave->team_lead_status !== 'pending') {
            return response()->json(['message' => 'Team lead has already acted on this leave.'], 422);
        }

        if ($leave->status !== 'pending') {
            return response()->json(['message' => 'Leave is no longer pending.'], 422);
        }

        $leave->update([
            'team_lead_status'   => 'approved',
            'team_lead_id'       => auth()->id(),
            'team_lead_acted_at' => now(),
            // Overall status stays 'pending' — HR must still final approve
        ]);

        return response()->json([
            'message' => 'Team lead approved. Awaiting HR/Admin final approval.',
            'data'    => new LeaveResource($leave->load(['employee', 'leaveType', 'teamLead'])),
        ]);
    }

    // ── Team Lead Reject ──────────────────────────────────────────
    public function teamLeadReject(Request $request, Leave $leave): JsonResponse
    {
        if (!$this->isTeamLead() && !$this->isAdminOrHR()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($this->isTeamLead() && !$this->isAdminOrHR()) {
            if ($leave->employee->department_id !== $this->myDeptId()) {
                return response()->json(['message' => 'Unauthorized — not your department.'], 403);
            }
        }

        if ($leave->team_lead_status !== 'pending') {
            return response()->json(['message' => 'Team lead has already acted on this leave.'], 422);
        }

        $request->validate(['rejection_reason' => ['required', 'string', 'min:5']]);

        // TL rejection sets overall status to 'rejected'
        // BUT HR can still override (see approve() below)
        DB::transaction(function () use ($request, $leave) {
            $leave->update([
                'team_lead_status'            => 'rejected',
                'team_lead_id'                => auth()->id(),
                'team_lead_acted_at'          => now(),
                'team_lead_rejection_reason'  => $request->rejection_reason,
                'status'                      => 'rejected',
                'rejection_reason'            => $request->rejection_reason,
                'approved_by'                 => auth()->id(),
                'approved_at'                 => now(),
            ]);

            // Refund balance on TL rejection
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
            'message' => 'Leave rejected by team lead.',
            'data'    => new LeaveResource($leave->load(['employee', 'leaveType', 'teamLead'])),
        ]);
    }

    // ── HR/Admin Final Approve (can override TL rejection) ────────
    public function approve(Request $request, Leave $leave): JsonResponse
    {
        if (!$this->isAdminOrHR()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // HR can approve even if TL rejected — this is the override
        // Block only if already HR-approved
        if ($leave->status === 'approved') {
            return response()->json(['message' => 'Leave is already approved.'], 422);
        }

        // If TL hasn't acted yet, warn but allow HR to approve anyway
        $wasOverride = $leave->team_lead_status === 'rejected';

        DB::transaction(function () use ($leave, $wasOverride) {
            $leave->update([
                'status'      => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                // If HR is overriding a TL rejection, restore balance first
                // (balance was already refunded when TL rejected)
                // We re-deduct it now
            ]);

            if ($wasOverride) {
                // Re-deduct balance since TL rejection had refunded it
                $year = Carbon::parse($leave->start_date)->year;
                LeaveBalance::where('employee_id', $leave->employee_id)
                            ->where('leave_type_id', $leave->leave_type_id)
                            ->where('year', $year)
                            ->increment('used_days', $leave->days);
                LeaveBalance::where('employee_id', $leave->employee_id)
                            ->where('leave_type_id', $leave->leave_type_id)
                            ->where('year', $year)
                            ->decrement('remaining_days', $leave->days);
            }
        });

        $message = $wasOverride
            ? 'Leave approved by HR (team lead rejection overridden).'
            : 'Leave approved successfully.';

        return response()->json([
            'message' => $message,
            'data'    => new LeaveResource($leave->load(['employee', 'leaveType', 'teamLead'])),
        ]);
    }

    // ── HR/Admin Reject ───────────────────────────────────────────
    public function reject(Request $request, Leave $leave): JsonResponse
    {
        if (!$this->isAdminOrHR()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($leave->status === 'approved') {
            return response()->json(['message' => 'Cannot reject an already approved leave.'], 422);
        }

        $request->validate(['rejection_reason' => ['required', 'string', 'min:5']]);

        DB::transaction(function () use ($request, $leave) {
            $leave->update([
                'status'           => 'rejected',
                'rejection_reason' => $request->rejection_reason,
                'approved_by'      => auth()->id(),
                'approved_at'      => now(),
            ]);

            // Only refund if not already refunded by TL rejection
            if ($leave->getOriginal('status') === 'pending') {
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
        });

        return response()->json([
            'message' => 'Leave rejected.',
            'data'    => new LeaveResource($leave->load(['employee', 'leaveType', 'teamLead'])),
        ]);
    }

    // ── Delete ────────────────────────────────────────────────────
    public function destroy(Leave $leave): JsonResponse
    {
        $user = auth()->user();

        if (!$this->isAdminOrHR() && $leave->employee_id !== $user->employee?->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($leave->status === 'approved') {
            return response()->json(['message' => 'Cannot delete approved leaves.'], 422);
        }

        DB::transaction(function () use ($leave) {
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
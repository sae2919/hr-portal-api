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
            // Team lead / Head:
            // 1. See leaves of employees who report directly to them
            // 2. See their own leaves
            $myEmployeeId = $user->employee?->id;
            
            if ($request->filled('employee_id')) {
                $reqEmpId = (int) $request->employee_id;
                $query->where('employee_id', $reqEmpId)
                      ->whereHas('employee', function($q) use ($myEmployeeId, $reqEmpId) {
                          if ($reqEmpId !== $myEmployeeId) {
                              $q->where('reporting_to', $myEmployeeId);
                          }
                      });
            } else {
                $query->whereHas('employee', function($q) use ($myEmployeeId) {
                    $q->where('reporting_to', $myEmployeeId)
                      ->orWhere('id', $myEmployeeId);
                });
            }
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

        $leaveType = \App\Models\LeaveType::find($request->leave_type_id);
        $isCompOff = str_contains(strtolower($leaveType->name ?? ''), 'compensatory') || str_contains(strtolower($leaveType->name ?? ''), 'comp');

        if ($isCompOff && !$isAdminOrHR) {
            // 1. Check leave dates are today or tomorrow only
            $todayStr = Carbon::today()->toDateString();
            $tomorrowStr = Carbon::tomorrow()->toDateString();

            if ($request->start_date !== $todayStr && $request->start_date !== $tomorrowStr) {
                return response()->json(['message' => 'Comp Off can only be applied for today or tomorrow.'], 422);
            }
            if ($request->end_date !== $todayStr && $request->end_date !== $tomorrowStr) {
                return response()->json(['message' => 'Comp Off can only be applied for today or tomorrow.'], 422);
            }

            // 2. Check if employee worked today (attendance record exists for today with check_in or status present/late/half_day)
            $attendance = \App\Models\Attendance::where('employee_id', $request->employee_id)
                ->whereDate('date', Carbon::today())
                ->first();

            if (!$attendance || (!$attendance->check_in && !in_array($attendance->status, ['present', 'late', 'half_day']))) {
                return response()->json(['message' => 'You can only apply for a Comp Off if you have worked today.'], 422);
            }
        }

        $isCompOffClaim = false;
        if ($isCompOff) {
            if ($isAdminOrHR) {
                $isCompOffClaim = true;
            } else {
                $todayStr = Carbon::today()->toDateString();
                $tomorrowStr = Carbon::tomorrow()->toDateString();
                $isTodayOrTomorrow = ($request->start_date === $todayStr || $request->start_date === $tomorrowStr) && 
                                     ($request->end_date === $todayStr || $request->end_date === $tomorrowStr);
                                     
                if ($isTodayOrTomorrow) {
                    $attendance = \App\Models\Attendance::where('employee_id', $request->employee_id)
                        ->whereDate('date', Carbon::today())
                        ->first();
                    $workedToday = $attendance && ($attendance->check_in || in_array($attendance->status, ['present', 'late', 'half_day']));
                    
                    if ($workedToday) {
                        $isCompOffClaim = true;
                    }
                }
            }
        }

        $startDate = Carbon::parse($request->start_date);
        $days      = Leave::calculateDays($request->start_date, $request->end_date);

        $balance = LeaveBalance::where('employee_id', $request->employee_id)
                               ->where('leave_type_id', $request->leave_type_id)
                               ->where('year', $startDate->year)
                               ->first();

        if (!$isCompOffClaim) {
            if (!$balance || $balance->remaining_days < $days) {
                return response()->json([
                    'message' => "Insufficient leave balance. Available: " . ($balance ? $balance->remaining_days : 0) . " days.",
                ], 422);
            }
        }

        $leave = null;

        // Admin/HR applying = auto approved (skip TL step) for normal leaves.
        // For Comp Off, they must go through the TL approval step first.
        $isCompOffAppliedByAdmin = $isCompOff && $isAdminOrHR;

        $status           = ($isAdminOrHR && !$isCompOffAppliedByAdmin) ? 'approved' : 'pending';
        $teamLeadStatus   = ($isAdminOrHR && !$isCompOffAppliedByAdmin) ? 'approved' : 'pending';
        $approvedBy       = ($isAdminOrHR && !$isCompOffAppliedByAdmin) ? auth()->id() : null;
        $approvedAt       = ($isAdminOrHR && !$isCompOffAppliedByAdmin) ? now()        : null;

        DB::transaction(function () use ($request, $days, $startDate, $status, $teamLeadStatus, $approvedBy, $approvedAt, $isCompOffAppliedByAdmin, $isCompOffClaim, &$leave) {
            $leave = Leave::create([
                'employee_id'       => $request->employee_id,
                'leave_type_id'     => $request->leave_type_id,
                'start_date'        => $request->start_date,
                'end_date'          => $request->end_date,
                'days'              => $days,
                'reason'            => $request->reason,
                'status'            => $status,
                'team_lead_status'  => $teamLeadStatus,
                'approved_by'       => $approvedBy,
                'approved_at'       => $approvedAt,
                'applied_by_admin'  => $isCompOffAppliedByAdmin,
                'is_comp_off_claim' => $isCompOffClaim,
            ]);

            // Only deduct balance immediately if this is NOT a Comp Off claim
            if (!$isCompOffClaim) {
                LeaveBalance::where('employee_id', $request->employee_id)
                            ->where('leave_type_id', $request->leave_type_id)
                            ->where('year', $startDate->year)
                            ->increment('used_days', $days);

                LeaveBalance::where('employee_id', $request->employee_id)
                            ->where('leave_type_id', $request->leave_type_id)
                            ->where('year', $startDate->year)
                            ->decrement('remaining_days', $days);
            }
        });

        $message = ($isAdminOrHR && !$isCompOffAppliedByAdmin)
            ? 'Leave added and automatically approved.'
            : ($isCompOffAppliedByAdmin 
                ? 'Comp Off application submitted on behalf of employee. Awaiting team lead review.' 
                : 'Leave application submitted. Awaiting team lead review.');

        // Trigger template mail to employee's manager
        if ($leave->employee->manager && $leave->employee->manager->email) {
            \App\Services\MailService::sendTemplateMail(
                $leave->employee->manager->email,
                'leave_request_submitted',
                [
                    'name' => $leave->employee->manager->full_name,
                    'employee_name' => $leave->employee->full_name,
                    'leave_type' => $leave->leaveType->name,
                    'leave_dates' => $leave->start_date . ' to ' . $leave->end_date,
                    'reason' => $leave->reason,
                    'days' => $leave->days,
                ]
            );
        }

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

        if ($leave->employee_id === auth()->user()->employee?->id) {
            return response()->json(['message' => 'You cannot approve your own leave request.'], 422);
        }

        // Team lead can only act on employees who report directly to them
        if ($this->isTeamLead() && !$this->isAdminOrHR()) {
            if ($leave->employee->reporting_to !== auth()->user()->employee?->id) {
                return response()->json(['message' => 'Unauthorized — employee does not report to you.'], 403);
            }
        }

        if ($leave->team_lead_status !== 'pending') {
            return response()->json(['message' => 'Team lead has already acted on this leave.'], 422);
        }

        if ($leave->status !== 'pending') {
            return response()->json(['message' => 'Leave is no longer pending.'], 422);
        }

        $isCompOff = str_contains(strtolower($leave->leaveType->name ?? ''), 'compensatory') || str_contains(strtolower($leave->leaveType->name ?? ''), 'comp');
        $isCompOffAppliedByAdmin = $isCompOff && $leave->applied_by_admin;

        if ($isCompOffAppliedByAdmin) {
            DB::transaction(function () use ($leave) {
                $leave->update([
                    'team_lead_status'   => 'approved',
                    'team_lead_id'       => auth()->id(),
                    'team_lead_acted_at' => now(),
                    'status'             => 'approved',
                    'approved_by'        => auth()->id(),
                    'approved_at'        => now(),
                ]);

                // Earning Claim: Increment balance upon approval
                if ($leave->is_comp_off_claim) {
                    $year = Carbon::parse($leave->start_date)->year;
                    $balance = LeaveBalance::firstOrCreate(
                        ['employee_id' => $leave->employee_id, 'leave_type_id' => $leave->leave_type_id, 'year' => $year],
                        ['total_days' => 0, 'used_days' => 0, 'remaining_days' => 0]
                    );
                    $balance->increment('total_days', $leave->days);
                    $balance->increment('remaining_days', $leave->days);
                }
            });

            // Trigger template mail to employee for approved Comp Off
            \App\Services\MailService::sendTemplateMail(
                $leave->employee->email,
                'leave_request_approved',
                [
                    'name' => $leave->employee->full_name,
                    'employee_name' => $leave->employee->full_name,
                    'leave_type' => $leave->leaveType->name,
                    'leave_dates' => $leave->start_date . ' to ' . $leave->end_date,
                    'days' => $leave->days,
                ]
            );

            return response()->json([
                'message' => 'Team lead approved. Since this Comp Off was applied by Admin/HR, it has been automatically finalized and approved.',
                'data'    => new LeaveResource($leave->load(['employee', 'leaveType', 'teamLead'])),
            ]);
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

        if ($leave->employee_id === auth()->user()->employee?->id) {
            return response()->json(['message' => 'You cannot reject your own leave request.'], 422);
        }

        // Team lead can only act on employees who report directly to them
        if ($this->isTeamLead() && !$this->isAdminOrHR()) {
            if ($leave->employee->reporting_to !== auth()->user()->employee?->id) {
                return response()->json(['message' => 'Unauthorized — employee does not report to you.'], 403);
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

            // Refund balance on TL rejection ONLY if not a Comp Off claim
            if (!$leave->is_comp_off_claim) {
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

        // Trigger template mail to employee for Team Lead rejection
        \App\Services\MailService::sendTemplateMail(
            $leave->employee->email,
            'leave_request_rejected',
            [
                'name' => $leave->employee->full_name,
                'employee_name' => $leave->employee->full_name,
                'leave_type' => $leave->leaveType->name,
                'leave_dates' => $leave->start_date . ' to ' . $leave->end_date,
                'rejection_reason' => $leave->team_lead_rejection_reason ?? $leave->rejection_reason ?? 'Rejected by Team Lead',
                'days' => $leave->days,
            ]
        );

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

        if ($leave->employee_id === auth()->user()->employee?->id) {
            return response()->json(['message' => 'You cannot approve your own leave request.'], 422);
        }

        $isCompOff = str_contains(strtolower($leave->leaveType->name ?? ''), 'compensatory') || str_contains(strtolower($leave->leaveType->name ?? ''), 'comp');
        if ($isCompOff && $leave->team_lead_status !== 'approved') {
            return response()->json([
                'message' => 'This Comp Off request must first be approved by the Team Lead.',
            ], 422);
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
            ]);

            // Earning Claim: Increment balance upon approval
            if ($leave->is_comp_off_claim) {
                $year = Carbon::parse($leave->start_date)->year;
                $balance = LeaveBalance::firstOrCreate(
                    ['employee_id' => $leave->employee_id, 'leave_type_id' => $leave->leave_type_id, 'year' => $year],
                    ['total_days' => 0, 'used_days' => 0, 'remaining_days' => 0]
                );
                $balance->increment('total_days', $leave->days);
                $balance->increment('remaining_days', $leave->days);
            } else {
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
            }
        });

        $message = $wasOverride
            ? 'Leave approved by HR (team lead rejection overridden).'
            : 'Leave approved successfully.';

        // Trigger template mail to employee for final approval
        \App\Services\MailService::sendTemplateMail(
            $leave->employee->email,
            'leave_request_approved',
            [
                'name' => $leave->employee->full_name,
                'employee_name' => $leave->employee->full_name,
                'leave_type' => $leave->leaveType->name,
                'leave_dates' => $leave->start_date . ' to ' . $leave->end_date,
                'days' => $leave->days,
            ]
        );

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

        if ($leave->employee_id === auth()->user()->employee?->id) {
            return response()->json(['message' => 'You cannot reject your own leave request.'], 422);
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

            // Only refund if not already refunded by TL rejection AND not a Comp Off claim
            if ($leave->getOriginal('status') === 'pending' && !$leave->is_comp_off_claim) {
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

        // Trigger template mail to employee for final rejection
        \App\Services\MailService::sendTemplateMail(
            $leave->employee->email,
            'leave_request_rejected',
            [
                'name' => $leave->employee->full_name,
                'employee_name' => $leave->employee->full_name,
                'leave_type' => $leave->leaveType->name,
                'leave_dates' => $leave->start_date . ' to ' . $leave->end_date,
                'rejection_reason' => $leave->rejection_reason ?? 'Rejected by HR/Admin',
                'days' => $leave->days,
            ]
        );

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
            if ($leave->status === 'pending' && !$leave->is_comp_off_claim) {
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
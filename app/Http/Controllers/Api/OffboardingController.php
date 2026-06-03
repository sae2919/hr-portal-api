<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OffboardingRequest;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class OffboardingController extends Controller
{
    private function isAdminOrHR(): bool
    {
        return auth()->user()->hasRole('super_admin') 
            || auth()->user()->hasRole('admin') 
            || auth()->user()->hasRole('hr');
    }

    /**
     * Get exit requests
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $query = OffboardingRequest::with(['employee.department', 'employee.designation', 'approver']);

        if (!$this->isAdminOrHR()) {
            // Standard employees only see their own requests
            $employeeId = $user->employee_id;
            if (!$employeeId) {
                return response()->json([
                    'success' => true,
                    'data' => []
                ]);
            }
            $query->where('employee_id', $employeeId);
        } else {
            // Filters for Admin/HR
            if ($request->filled('employee_id')) {
                $query->where('employee_id', $request->employee_id);
            }
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            if ($request->filled('search')) {
                $search = $request->search;
                $query->whereHas('employee', function($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('employee_code', 'like', "%{$search}%");
                });
            }
        }

        $requests = $query->latest()->paginate($request->per_page ?? 10);

        return response()->json([
            'success' => true,
            'data' => $requests
        ]);
    }

    /**
     * Store new exit request
     */
    public function store(Request $request): JsonResponse
    {
        $user = auth()->user();
        $isAdmin = $this->isAdminOrHR();

        $request->validate([
            'employee_id' => $isAdmin ? 'required|exists:employees,id' : 'nullable',
            'resignation_date' => 'required|date',
            'last_working_day' => 'nullable|date|after_or_equal:resignation_date',
            'reason' => 'required|string|min:10',
        ]);

        $employeeId = $isAdmin ? $request->employee_id : $user->employee_id;

        if (!$employeeId) {
            return response()->json([
                'success' => false,
                'message' => 'No employee record linked to user.'
            ], 422);
        }

        // Check if there is already a pending or approved exit request for this employee
        $existing = OffboardingRequest::where('employee_id', $employeeId)
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'An active offboarding request already exists for this employee.'
            ], 422);
        }

        // Default clearance tasks
        $defaultTasks = [
            ['id' => 1, 'task_name' => 'Asset Return', 'status' => 'pending'],
            ['id' => 2, 'task_name' => 'IT Account Deactivation', 'status' => 'pending'],
            ['id' => 3, 'task_name' => 'Exit Interview', 'status' => 'pending'],
            ['id' => 4, 'task_name' => 'Full & Final Settlement', 'status' => 'pending']
        ];

        $offboarding = OffboardingRequest::create([
            'employee_id' => $employeeId,
            'resignation_date' => $request->resignation_date,
            'last_working_day' => $request->last_working_day,
            'reason' => $request->reason,
            'status' => 'pending',
            'tasks' => $defaultTasks,
            'created_by' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Resignation/Exit request created successfully',
            'data' => $offboarding->load('employee')
        ], 201);
    }

    /**
     * Approve exit request
     */
    public function approve(Request $request, $id): JsonResponse
    {
        if (!$this->isAdminOrHR()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $offboarding = OffboardingRequest::findOrFail($id);

        if ($offboarding->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending exit requests can be approved.'
            ], 422);
        }

        $request->validate([
            'last_working_day' => 'required|date|after_or_equal:' . $offboarding->resignation_date->toDateString(),
        ]);

        $offboarding->update([
            'status' => 'approved',
            'last_working_day' => $request->last_working_day,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Exit request approved successfully',
            'data' => $offboarding->load('employee')
        ]);
    }

    /**
     * Reject exit request
     */
    public function reject(Request $request, $id): JsonResponse
    {
        if (!$this->isAdminOrHR()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $offboarding = OffboardingRequest::findOrFail($id);

        if ($offboarding->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending exit requests can be rejected.'
            ], 422);
        }

        $request->validate([
            'rejection_reason' => 'required|string|min:10'
        ]);

        $offboarding->update([
            'status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Exit request rejected.',
            'data' => $offboarding->load('employee')
        ]);
    }

    /**
     * Complete exit request (marks employee terminated/inactive)
     */
    public function complete(Request $request, $id): JsonResponse
    {
        if (!$this->isAdminOrHR()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $offboarding = OffboardingRequest::findOrFail($id);

        if ($offboarding->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Only approved requests can be completed.'
            ], 422);
        }

        // Verify that all clearance tasks are completed
        $pendingTasks = collect($offboarding->tasks)->filter(fn($t) => $t['status'] !== 'completed');

        if ($pendingTasks->isNotEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot complete exit. Some clearance tasks are still pending.'
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Update offboarding status
            $offboarding->update([
                'status' => 'completed',
            ]);

            // Update Employee profile
            $employee = $offboarding->employee;
            $employee->update([
                'status' => 'terminated',
                'exit_date' => $offboarding->last_working_day ?? now()->toDateString(),
            ]);

            // Deactivate User account if exists
            $user = User::where('employee_id', $employee->id)->first();
            if ($user) {
                $user->update([
                    'status' => 'inactive',
                ]);
                // Delete user tokens to force logout
                $user->tokens()->delete();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Offboarding completed successfully. Employee record is now terminated/inactive.',
                'data' => $offboarding->load('employee')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete offboarding: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update exit checklist tasks
     */
    public function updateTasks(Request $request, $id): JsonResponse
    {
        if (!$this->isAdminOrHR()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $offboarding = OffboardingRequest::findOrFail($id);

        $request->validate([
            'tasks' => 'required|array',
            'tasks.*.id' => 'required|integer',
            'tasks.*.task_name' => 'required|string',
            'tasks.*.status' => 'required|in:pending,completed',
        ]);

        $offboarding->update([
            'tasks' => $request->tasks,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Checklist tasks updated successfully',
            'data' => $offboarding
        ]);
    }
}

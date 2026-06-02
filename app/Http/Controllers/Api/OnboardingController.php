<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OnboardingRequest;
use App\Models\OnboardingDocument;
use App\Models\OnboardingTask;
use App\Models\OfferLetter;
use App\Models\AssetAllocation;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class OnboardingController extends Controller
{
    /**
     * Get all onboarding requests
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        $query = OnboardingRequest::with(['documents', 'tasks', 'offerLetters']);
        
        // Filter by status
        if ($request->status) {
            $query->where('status', $request->status);
        }
        
        // Filter by department
        if ($request->department) {
            $query->where('department', $request->department);
        }
        
        // Filter by date range
        if ($request->start_date) {
            $query->where('joining_date', '>=', $request->start_date);
        }
        if ($request->end_date) {
            $query->where('joining_date', '<=', $request->end_date);
        }
        
        // Search
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('candidate_name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%")
                  ->orWhere('position', 'like', "%{$request->search}%");
            });
        }
        
        $onboardingRequests = $query->latest()->paginate($request->per_page ?? 10);
        
        return response()->json([
            'success' => true,
            'data' => $onboardingRequests
        ]);
    }
    
    /**
     * Store a new onboarding request
     */
    public function store(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        $request->validate([
            'candidate_name' => 'required|string|max:255',
            'email' => 'required|email|unique:onboarding_requests,email',
            'phone' => 'nullable|string|max:20',
            'position' => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'joining_date' => 'required|date',
            'ctc' => 'nullable|numeric|min:0',
        ]);
        
        DB::beginTransaction();
        
        try {
            // Create onboarding request
            $onboardingRequest = OnboardingRequest::create([
                'candidate_name' => $request->candidate_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'position' => $request->position,
                'department' => $request->department,
                'joining_date' => $request->joining_date,
                'ctc' => $request->ctc,
                'status' => 'pending',
                'created_by' => $user->id,
            ]);
            
            // Create default onboarding tasks
            $defaultTasks = [
                ['task_name' => 'Review Candidate Documents', 'assigned_to' => 'HR', 'due_days' => 1],
                ['task_name' => 'Prepare Offer Letter', 'assigned_to' => 'HR', 'due_days' => 2],
                ['task_name' => 'Setup Email Account', 'assigned_to' => 'IT', 'due_days' => 5],
                ['task_name' => 'Prepare Laptop/System', 'assigned_to' => 'IT', 'due_days' => 5],
                ['task_name' => 'Assign Workspace', 'assigned_to' => 'Admin', 'due_days' => 5],
                ['task_name' => 'Create Employee Record', 'assigned_to' => 'HR', 'due_days' => 3],
                ['task_name' => 'Schedule Orientation', 'assigned_to' => 'HR', 'due_days' => 7],
            ];
            
            $joiningDate = Carbon::parse($request->joining_date);
            
            foreach ($defaultTasks as $task) {
                OnboardingTask::create([
                    'onboarding_request_id' => $onboardingRequest->id,
                    'task_name' => $task['task_name'],
                    'assigned_to' => $task['assigned_to'],
                    'description' => "Complete {$task['task_name']} for {$request->candidate_name}",
                    'due_date' => $joiningDate->copy()->subDays($task['due_days']),
                    'status' => 'pending',
                ]);
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Onboarding request created successfully',
                'data' => $onboardingRequest->load(['documents', 'tasks'])
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create onboarding request: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get single onboarding request
     */
    public function show(OnboardingRequest $onboardingRequest): JsonResponse
    {
        $onboardingRequest->load(['documents', 'tasks', 'offerLetters', 'assetAllocations.asset']);
        
        return response()->json([
            'success' => true,
            'data' => $onboardingRequest
        ]);
    }
    
    /**
     * Update onboarding request
     */
    public function update(Request $request, OnboardingRequest $onboardingRequest): JsonResponse
    {
        $request->validate([
            'candidate_name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:20',
            'position' => 'sometimes|string|max:255',
            'department' => 'sometimes|string|max:255',
            'joining_date' => 'sometimes|date',
            'ctc' => 'nullable|numeric|min:0',
        ]);
        
        $onboardingRequest->update($request->only([
            'candidate_name', 'phone', 'position', 'department', 'joining_date', 'ctc'
        ]));
        
        return response()->json([
            'success' => true,
            'message' => 'Onboarding request updated successfully',
            'data' => $onboardingRequest
        ]);
    }
    
    /**
     * Approve onboarding request
     */
    public function approve(OnboardingRequest $onboardingRequest): JsonResponse
    {
        $user = auth()->user();
        
        if ($onboardingRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending requests can be approved'
            ], 422);
        }
        
        $onboardingRequest->update([
            'status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Onboarding request approved successfully',
            'data' => $onboardingRequest
        ]);
    }
    
    /**
     * Reject onboarding request
     */
    public function reject(Request $request, OnboardingRequest $onboardingRequest): JsonResponse
    {
        $request->validate([
            'rejection_reason' => 'required|string|min:10'
        ]);
        
        if ($onboardingRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending requests can be rejected'
            ], 422);
        }
        
        $onboardingRequest->update([
            'status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Onboarding request rejected',
            'data' => $onboardingRequest
        ]);
    }
    
    /**
     * Complete onboarding (create employee record)
     */
    public function complete(OnboardingRequest $onboardingRequest): JsonResponse
    {
        if ($onboardingRequest->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Only approved requests can be completed'
            ], 422);
        }
        
        DB::beginTransaction();
        
        try {
            // Create employee record
            $nameParts = explode(' ', $onboardingRequest->candidate_name, 2);
            $firstName = $nameParts[0];
            $lastName = $nameParts[1] ?? '';
            
            $employee = Employee::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $onboardingRequest->email,
                'phone' => $onboardingRequest->phone,
                'employee_code' => 'EMP' . str_pad(Employee::count() + 1, 4, '0', STR_PAD_LEFT),
                'department' => $onboardingRequest->department,
                'designation' => $onboardingRequest->position,
                'joining_date' => $onboardingRequest->joining_date,
                'status' => 'active',
            ]);
            
            // Create user account for employee
            $user = User::create([
                'name' => $onboardingRequest->candidate_name,
                'email' => $onboardingRequest->email,
                'password' => Hash::make('password123'), // Send email with temp password
                'employee_id' => $employee->id,
            ]);
            
            $user->assignRole('employee');
            
            // Update onboarding request status
            $onboardingRequest->update([
                'status' => 'onboarded',
            ]);
            
            // Mark tasks as completed
            OnboardingTask::where('onboarding_request_id', $onboardingRequest->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Employee onboarded successfully',
                'data' => [
                    'employee' => $employee,
                    'user' => $user
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete onboarding: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Delete onboarding request
     */
    public function destroy(OnboardingRequest $onboardingRequest): JsonResponse
    {
        // Delete associated files
        foreach ($onboardingRequest->documents as $document) {
            Storage::disk('public')->delete($document->file_path);
        }
        
        $onboardingRequest->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Onboarding request deleted successfully'
        ]);
    }
}
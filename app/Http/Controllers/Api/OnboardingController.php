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
            'onboarding_type' => 'nullable|in:full_time,intern,free_intern',
            'custom_heading' => 'nullable|string|max:255',
            'required_documents' => 'nullable|array',
            'optional_documents' => 'nullable|array',
            'custom_document_labels' => 'nullable|array',
        ]);
        
        $onboardingType = $request->onboarding_type ?: 'full_time';
        $requiredDocs = $request->required_documents;
        $optionalDocs = $request->optional_documents;
        
        if (is_null($requiredDocs) || is_null($optionalDocs)) {
            if ($onboardingType === 'free_intern') {
                $requiredDocs = $requiredDocs ?? ['resume', 'id_proof', 'address_proof', 'degree', 'aadhaar_card'];
                $optionalDocs = $optionalDocs ?? ['passport'];
            } elseif ($onboardingType === 'intern') {
                $requiredDocs = $requiredDocs ?? ['resume', 'id_proof', 'address_proof', 'degree', 'bank_details', 'pan_card', 'aadhaar_card'];
                $optionalDocs = $optionalDocs ?? ['passport'];
            } else { // full_time
                $requiredDocs = $requiredDocs ?? ['resume', 'id_proof', 'address_proof', 'degree', 'bank_details', 'pan_card', 'aadhaar_card'];
                $optionalDocs = $optionalDocs ?? ['payslips', 'experience_letter', 'passport'];
            }
        }
        
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
                'onboarding_type' => $onboardingType,
                'custom_heading' => $request->custom_heading,
                'required_documents' => $requiredDocs,
                'optional_documents' => $optionalDocs,
                'custom_document_labels' => $request->custom_document_labels,
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

            // Trigger welcome onboarding mail to candidate
            \App\Jobs\SendReusableMail::dispatch(
                'candidate_onboarding_welcome',
                $onboardingRequest->email,
                [
                    'name' => $onboardingRequest->candidate_name,
                    'employee_name' => $onboardingRequest->candidate_name,
                    'position' => $onboardingRequest->position,
                    'department' => $onboardingRequest->department,
                    'joining_date' => $onboardingRequest->joining_date,
                    'portal_link' => env('FRONTEND_URL', 'http://127.0.0.1:3000') . '/onboarding/candidate/' . $onboardingRequest->access_token,
                ]
            );

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
            'onboarding_type' => 'sometimes|in:full_time,intern,free_intern',
            'custom_heading' => 'nullable|string|max:255',
            'required_documents' => 'nullable|array',
            'optional_documents' => 'nullable|array',
            'custom_document_labels' => 'nullable|array',
        ]);
        
        $onboardingRequest->update($request->only([
            'candidate_name', 'phone', 'position', 'department', 'joining_date', 'ctc',
            'onboarding_type', 'custom_heading', 'required_documents', 'optional_documents',
            'custom_document_labels'
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
        
        // Trigger approval mail to candidate
        \App\Jobs\SendReusableMail::dispatch(
            'candidate_onboarding_approved',
            $onboardingRequest->email,
            [
                'name' => $onboardingRequest->candidate_name,
                'employee_name' => $onboardingRequest->candidate_name,
                'position' => $onboardingRequest->position,
                'department' => $onboardingRequest->department,
                'joining_date' => $onboardingRequest->joining_date,
            ]
        );

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
        
        // Trigger rejection mail to candidate
        \App\Jobs\SendReusableMail::dispatch(
            'candidate_onboarding_rejected',
            $onboardingRequest->email,
            [
                'name' => $onboardingRequest->candidate_name,
                'employee_name' => $onboardingRequest->candidate_name,
                'position' => $onboardingRequest->position,
                'department' => $onboardingRequest->department,
                'joining_date' => $onboardingRequest->joining_date,
                'rejection_reason' => $onboardingRequest->rejection_reason,
            ]
        );

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
            // Extract personal details from onboarding request
            $personal = $onboardingRequest->personal_details ?? [];
            if (is_string($personal)) {
                $personal = json_decode($personal, true) ?? [];
            }

            // Create employee record
            $nameParts = explode(' ', $onboardingRequest->candidate_name, 2);
            $firstName = $nameParts[0];
            $lastName = $nameParts[1] ?? '';
            
            $employee = Employee::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $onboardingRequest->email,
                'phone' => $onboardingRequest->phone ?: ($personal['phone'] ?? null),
                'department' => $onboardingRequest->department,
                'designation' => $onboardingRequest->position,
                'joining_date' => $onboardingRequest->joining_date,
                'employment_type' => ($onboardingRequest->onboarding_type === 'full_time') ? 'full_time' : 'intern',
                'status' => 'active',
                'dob' => $personal['dob'] ?? null,
                'gender' => $personal['gender'] ?? null,
                'address' => $personal['address'] ?? null,
                'bank_name' => $personal['bank_name'] ?? null,
                'bank_account_number' => $personal['bank_account_number'] ?? null,
                'bank_ifsc' => $personal['bank_ifsc'] ?? null,
                'bank_branch' => $personal['bank_branch'] ?? null,
                'pan_number' => $personal['pan_number'] ?? null,
                'aadhaar_number' => $personal['aadhaar_number'] ?? null,
                'passport_number' => $personal['passport_number'] ?? null,
                'driving_license' => $personal['driving_license'] ?? null,
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

            // Link asset allocations to the new employee
            AssetAllocation::where('onboarding_request_id', $onboardingRequest->id)
                ->update(['employee_id' => $employee->id]);
            
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
    
    /**
     * Get onboarding request details for public candidate portal
     */
    public function showPublic(OnboardingRequest $onboardingRequest): JsonResponse
    {
        if ($onboardingRequest->isLinkExpired()) {
            return response()->json([
                'success' => false,
                'message' => 'This onboarding link has expired. Onboarding links are only valid for 48 hours.'
            ], 403);
        }

        $onboardingRequest->load(['documents']);
        
        return response()->json([
            'success' => true,
            'data' => $onboardingRequest
        ]);
    }
    
    /**
     * Update onboarding details (like phone number) from public candidate portal
     */
    public function updatePublic(Request $request, OnboardingRequest $onboardingRequest): JsonResponse
    {
        if ($onboardingRequest->isLinkExpired()) {
            return response()->json([
                'success' => false,
                'message' => 'This onboarding link has expired.'
            ], 403);
        }

        if ($onboardingRequest->status === 'onboarded') {
            return response()->json([
                'success' => false,
                'message' => 'Onboarding has already been completed.'
            ], 422);
        }

        $request->validate([
            'phone' => 'nullable|string|max:20',
        ]);
        
        $onboardingRequest->update($request->only(['phone']));
        
        return response()->json([
            'success' => true,
            'message' => 'Details updated successfully',
            'data' => $onboardingRequest->load(['documents'])
        ]);
    }
    
    /**
     * Submit onboarding documents from public candidate portal
     */
    public function submitPublic(Request $request, OnboardingRequest $onboardingRequest): JsonResponse
    {
        if ($onboardingRequest->isLinkExpired()) {
            return response()->json([
                'success' => false,
                'message' => 'This onboarding link has expired.'
            ], 403);
        }

        if ($onboardingRequest->status === 'onboarded') {
            return response()->json([
                'success' => false,
                'message' => 'Onboarding has already been completed.'
            ], 422);
        }

        $request->validate([
            'phone' => 'required|string|max:20',
            'dob' => 'required|date',
            'gender' => 'required|in:male,female,other',
            'address' => 'required|string|max:1000',
            'bank_name' => 'required|string|max:255',
            'bank_account_number' => 'required|string|max:50',
            'bank_ifsc' => 'required|string|max:20',
            'bank_branch' => 'required|string|max:255',
            'pan_number' => 'nullable|string|max:20',
            'aadhaar_number' => 'nullable|string|max:20',
            'passport_number' => 'nullable|string|max:20',
            'driving_license' => 'nullable|string|max:20',
        ]);

        $details = $request->only([
            'dob', 'gender', 'address', 'bank_name', 'bank_account_number',
            'bank_ifsc', 'bank_branch', 'pan_number', 'aadhaar_number',
            'passport_number', 'driving_license'
        ]);

        $onboardingRequest->update([
            'phone' => $request->phone,
            'personal_details' => $details,
            'status' => $onboardingRequest->status === 'rejected' ? 'pending' : $onboardingRequest->status,
            'rejection_reason' => $onboardingRequest->status === 'rejected' ? null : $onboardingRequest->rejection_reason,
        ]);

        // Generate PDF
        $pdf = \PDF::loadView('pdf.onboarding-form', [
            'candidate' => $onboardingRequest,
            'details' => $details,
        ]);

        $fileName = "onboarding_form_{$onboardingRequest->id}.pdf";
        $filePath = "onboarding/{$onboardingRequest->id}/{$fileName}";

        Storage::disk('public')->put($filePath, $pdf->output());

        // Upsert onboarding document
        $document = OnboardingDocument::where('onboarding_request_id', $onboardingRequest->id)
            ->where('document_type', 'onboarding_form')
            ->first();

        if ($document) {
            Storage::disk('public')->delete($document->file_path);
            $document->update([
                'original_name' => 'Onboarding_Details_Form.pdf',
                'file_path' => $filePath,
                'file_size' => $this->formatBytes(Storage::disk('public')->size($filePath)),
                'mime_type' => 'application/pdf',
                'status' => 'pending',
            ]);
        } else {
            OnboardingDocument::create([
                'onboarding_request_id' => $onboardingRequest->id,
                'document_type' => 'onboarding_form',
                'original_name' => 'Onboarding_Details_Form.pdf',
                'file_path' => $filePath,
                'file_size' => $this->formatBytes(Storage::disk('public')->size($filePath)),
                'mime_type' => 'application/pdf',
                'status' => 'pending',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Onboarding documents and details form submitted successfully!',
            'data' => $onboardingRequest->load(['documents'])
        ]);
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
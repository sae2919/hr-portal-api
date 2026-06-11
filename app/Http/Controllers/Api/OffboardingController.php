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

            // Load relations to make sure PDF views and email placeholders have all info
            $offboarding->load(['employee.department', 'employee.designation', 'approver']);
            $employee = $offboarding->employee;

            try {
                // Generate clearance letter PDF using DomPDF
                $salutation = 'Mr.';
                if (isset($employee->gender) && strtolower($employee->gender) === 'female') {
                    $salutation = 'Ms.';
                }
                
                $empFullName = ($employee->first_name ?? '') . ' ' . ($employee->last_name ?? '');
                $joiningDateFormatted = $employee->joining_date ? \Carbon\Carbon::parse($employee->joining_date)->format('d-M-Y') : '';
                $lastDayFormatted = $offboarding->last_working_day ? \Carbon\Carbon::parse($offboarding->last_working_day)->format('d-M-Y') : '';
                
                $companyName = \App\Models\CompanySetting::getValue('company_name') ?? 'Techsprout AI Labs Pvt. Ltd';
                $companyLogo = \App\Models\CompanySetting::getValue('company_logo') ?? null;
                $designationName = $employee->designation->name ?? ($employee->designation->title ?? '-');

                $resignationDateFormatted = $offboarding->resignation_date ? ($offboarding->resignation_date instanceof \Carbon\Carbon ? $offboarding->resignation_date->format('d-M-Y') : \Carbon\Carbon::parse($offboarding->resignation_date)->format('d-M-Y')) : '';

                $variables = [
                    'offboarding' => $offboarding,
                    'employee' => $employee,
                    'salutation' => $salutation,
                    'employee_name' => $empFullName,
                    'company_name' => $companyName,
                    'company_logo' => $companyLogo,
                    'designation' => $designationName,
                    'joining_date' => $joiningDateFormatted,
                    'last_working_day' => $lastDayFormatted,
                    'resignation_date' => $resignationDateFormatted,
                    'employee_code' => $employee->employee_code ?? '-',
                    'date' => \Carbon\Carbon::now()->format('d-M-Y'),
                ];

                // Generate clearance letter PDF dynamically from DB template
                $pdf = \App\Services\DocumentService::render('exit_relieving_letter', $variables);

                $filename = "Relieving_Letter_" . str_replace(' ', '_', $employee->full_name) . ".pdf";

                // Trigger offboarding completed template email via background queue
                \App\Jobs\SendReusableMail::dispatch(
                    'employee_offboarding_complete',
                    $employee->email,
                    [
                        'name' => $employee->full_name,
                        'employee_name' => $employee->full_name,
                        'employee_code' => $employee->employee_code,
                        'resignation_date' => $offboarding->resignation_date ? $offboarding->resignation_date->format('d-M-Y') : '',
                        'last_working_day' => $offboarding->last_working_day ? $offboarding->last_working_day->format('d-M-Y') : '',
                    ],
                    null,
                    [
                        [
                            'data' => base64_encode($pdf->output()),
                            'name' => $filename,
                            'mime' => 'application/pdf',
                            'base64' => true,
                        ]
                    ]
                );
            } catch (\Exception $mailEx) {
                \Illuminate\Support\Facades\Log::error('Exit clearance PDF mail trigger failed: ' . $mailEx->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Offboarding completed successfully. Employee record is now terminated/inactive and Relieving Letter emailed.',
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
     * Download exit clearance PDF
     */
    public function download($id)
    {
        $user = auth()->user();
        $offboarding = OffboardingRequest::with(['employee.department', 'employee.designation', 'approver'])->findOrFail($id);

        if (!$this->isAdminOrHR()) {
            $employeeId = $user->employee->id ?? null;
            if ($offboarding->employee_id !== $employeeId) {
                abort(403, 'Unauthorized.');
            }
        }

        if ($offboarding->status !== 'completed' && $offboarding->status !== 'approved') {
            abort(400, 'Relieving letter is only available after approval or completion.');
        }

        $employee = $offboarding->employee;

        $salutation = 'Mr.';
        if (isset($employee->gender) && strtolower($employee->gender) === 'female') {
            $salutation = 'Ms.';
        }
        
        $empFullName = ($employee->first_name ?? '') . ' ' . ($employee->last_name ?? '');
        $joiningDateFormatted = $employee->joining_date ? \Carbon\Carbon::parse($employee->joining_date)->format('d-M-Y') : '';
        $lastDayFormatted = $offboarding->last_working_day ? \Carbon\Carbon::parse($offboarding->last_working_day)->format('d-M-Y') : '';
        
        $companyName = \App\Models\CompanySetting::getValue('company_name') ?? 'Techsprout AI Labs Pvt. Ltd';
        $companyLogo = \App\Models\CompanySetting::getValue('company_logo') ?? null;
        $designationName = $employee->designation->name ?? ($employee->designation->title ?? '-');

        $resignationDateFormatted = $offboarding->resignation_date ? ($offboarding->resignation_date instanceof \Carbon\Carbon ? $offboarding->resignation_date->format('d-M-Y') : \Carbon\Carbon::parse($offboarding->resignation_date)->format('d-M-Y')) : '';

        $variables = [
            'offboarding' => $offboarding,
            'employee' => $employee,
            'salutation' => $salutation,
            'employee_name' => $empFullName,
            'company_name' => $companyName,
            'company_logo' => $companyLogo,
            'designation' => $designationName,
            'joining_date' => $joiningDateFormatted,
            'last_working_day' => $lastDayFormatted,
            'resignation_date' => $resignationDateFormatted,
            'employee_code' => $employee->employee_code ?? '-',
            'date' => \Carbon\Carbon::now()->format('d-M-Y'),
        ];

        // Generate clearance letter PDF dynamically from DB template
        $pdf = \App\Services\DocumentService::render('exit_relieving_letter', $variables);

        $filename = "Relieving_Letter_" . str_replace(' ', '_', $employee->full_name) . ".pdf";

        return $pdf->stream($filename);
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

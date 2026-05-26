<?php

namespace App\Http\Controllers\Api;

use App\Mail\EmployeePayslipMail;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Models\PayslipRequest; 
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;

class PayrollController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = Payroll::with([
            'employee.department',
            'salaryStructure'
        ]);

        // ── ROLE SECURITY CHECK ──
        if ($user->role !== 'admin') {
            if ($user->employee) {
                $query->where('employee_id', $user->employee->id);
            } else {
                return response()->json(['data' => [], 'message' => 'No employee record linked.'], 200);
            }
        }

        if ($request->month) {
            $query->where('month', $request->month);
        }

        if ($request->year) {
            $query->where('year', $request->year);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $payrolls = $query
            ->latest()
            ->paginate($request->per_page ?? 10);

        return response()->json($payrolls);
    }

    public function markPaid(Payroll $payroll)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized administrative action.'], 403);
        }

        $payroll->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        return response()->json(['message' => 'Payroll marked as paid']);
    }

    public function sendEmail($payrollId)
    {
        $payroll = Payroll::with(['employee.user'])->findOrFail($payrollId);

        $employeeEmail = $payroll->employee->user->email ?? null;
        $employeeName = $payroll->employee->first_name . ' ' . $payroll->employee->last_name;

        if (!$employeeEmail) {
            return response()->json([
                'message' => "Could not send payslip. No registered email address found for {$employeeName}."
            ], 422);
        }

        try {
            Mail::to($employeeEmail)->send(new EmployeePayslipMail($payroll));

            return response()->json([
                'message' => "Payslip successfully generated and emailed to {$employeeEmail}."
            ], 200);

        } catch (\Exception $e) {
            Log::error("Mail delivery failed: " . $e->getMessage());
            
            return response()->json([
                'message' => "Mail server error: Failed to deliver email to {$employeeEmail}."
            ], 500);
        }
    }

    /**
     * ── Secure PDF Download Endpoint ──
     * Handles traditional header authentication AND URL query token parameters
     */
    public function downloadPayslip(Request $request, $id)
    {
        // 1. Fallback Query Authentication Interceptor for standard <a> tab links
        if (!auth()->check()) {
            $tokenStr = $request->bearerToken() ?? $request->query('token');

            if ($tokenStr) {
                $token = PersonalAccessToken::findToken($tokenStr);
                
                // FIXED: Standardized expiration check for strict package compatibility
                if ($token && ($token->expires_at === null || $token->expires_at->isFuture())) {
                    auth()->login($token->tokenable);
                }
            }
        }

        $user = auth()->user();
        if (!$user) {
            abort(401, 'Unauthenticated access attempt.');
        }

        $payroll = Payroll::with(['employee.department', 'salaryStructure'])->findOrFail($id);
        $employeeId = $user->employee->id ?? null;

        // 2. Security isolation guard
        if ($user->role !== 'admin' && $payroll->employee_id !== $employeeId) {
            abort(403, 'Unauthorized access request. You can only view your own files.');
        }

        // 3. Status guard: Employees cannot look up slips until approved ('paid')
        if ($user->role !== 'admin' && $payroll->status !== 'paid') {
            abort(403, 'This statement period has not been released or approved yet.');
        }

        // 4. Generate your PDF view stream output
        $pdf = \PDF::loadView('emails.payslip', compact('payroll'));
        
        $filename = "payslip-{$payroll->year}-{$payroll->month}-{$id}.pdf";
        return $pdf->stream($filename);
    }

    /**
     * Log a formal request for a monthly payslip statement workflow.
     */
    public function requestPayslip(Payroll $payroll): JsonResponse
    {
        $user = auth()->user();
        $isAdmin = $user->role === 'admin';

        $employeeId = $isAdmin 
            ? $payroll->employee_id 
            : ($user->employee->id ?? null);

        if (!$employeeId) {
            return response()->json([
                'message' => 'No valid employee profile associated with this account context.'
            ], 422);
        }

        if (!$isAdmin && $payroll->employee_id !== $employeeId) {
            return response()->json([
                'message' => 'Unauthorized action. You can only dispatch payslip requests for your own data statement profiles.'
            ], 403);
        }

        $exists = PayslipRequest::where('payroll_id', $payroll->id)
                                ->where('employee_id', $employeeId)
                                ->where('status', 'pending')
                                ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'An active pending request has already been submitted for this monthly pay cycle period.'
            ], 422);
        }

        PayslipRequest::create([
            'payroll_id'  => $payroll->id,
            'employee_id' => $employeeId,
            'status'      => 'pending',
        ]);

        return response()->json([
            'message' => 'Your payslip request has been successfully captured and logged for HR verification.'
        ], 200);
    }

    /**
     * Fetch all employee payslip requests (Admin Only).
     */
    public function indexRequests(Request $request): JsonResponse
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized administrative action.'], 403);
        }

        $requests = PayslipRequest::with(['employee.department', 'payroll'])
            ->when($request->status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->latest()
            ->paginate($request->per_page ?? 10);

        return response()->json($requests);
    }

    /**
     * Fulfill or Reject an employee payslip request (Admin Only).
     */
    public function fulfillRequest(Request $request, $id): JsonResponse
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized administrative action.'], 403);
        }

        $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'admin_notes' => ['nullable', 'string']
        ]);

        $payslipRequest = PayslipRequest::with('payroll')->findOrFail($id);

        DB::transaction(function () use ($request, $payslipRequest) {
            $payslipRequest->update([
                'status' => $request->status,
                'admin_notes' => $request->admin_notes,
            ]);

            if ($request->status === 'approved') {
                $payslipRequest->payroll->update([
                    'status' => 'paid',
                    'paid_at' => now()
                ]);
            }
        });

        if ($request->status === 'approved') {
            $this->sendEmail($payslipRequest->payroll_id);
            $message = 'Request approved and payslip successfully emailed to the employee.';
        } else {
            $message = 'Payslip request has been marked as rejected.';
        }

        return response()->json(['message' => $message], 200);
    }
}
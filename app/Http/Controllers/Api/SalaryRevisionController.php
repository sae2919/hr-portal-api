<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\SalaryRevision;
use App\Models\SalaryStructure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SalaryRevisionController extends Controller
{
    /**
     * Role checks
     */
    private function isAdminOrHR(): bool
    {
        $user = auth()->user();
        return in_array($user->role, ['super_admin', 'super admin', 'admin', 'hr']);
    }

    /**
     * GET /api/v1/salary-revisions
     * List all salary revisions (scoped by role)
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $query = SalaryRevision::with(['employee.department', 'employee.designation', 'approver']);

        if (!$this->isAdminOrHR()) {
            $employeeId = $user->employee->id ?? null;
            if (!$employeeId) {
                return response()->json(['data' => [], 'message' => 'No employee record linked.'], 200);
            }
            $query->where('employee_id', $employeeId);
        } else {
            if ($request->filled('employee_id')) {
                $query->where('employee_id', $request->employee_id);
            }
        }

        $revisions = $query->orderBy('effective_date', 'desc')
                           ->orderBy('created_at', 'desc')
                           ->paginate($request->per_page ?? 10);

        return response()->json($revisions);
    }

    /**
     * POST /api/v1/salary-revisions
     * Create a new salary revision (Admin/HR only)
     */
    public function store(Request $request): JsonResponse
    {
        if (!$this->isAdminOrHR()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'employee_id'        => ['required', 'exists:employees,id'],
            'new_basic_salary'   => ['required', 'numeric', 'min:0'],
            'new_hra'            => ['required', 'numeric', 'min:0'],
            'new_allowances'     => ['required', 'numeric', 'min:0'],
            'new_bonus'          => ['required', 'numeric', 'min:0'],
            'effective_date'     => ['required', 'date'],
            'reason'             => ['required', 'string', 'max:255'],
        ]);

        $employeeId = $request->employee_id;
        $effectiveDate = Carbon::parse($request->effective_date);

        // Fetch current active salary structure
        $currentStructure = SalaryStructure::where('employee_id', $employeeId)
            ->where('status', 'active')
            ->latest('effective_from')
            ->first();

        // Old values default to 0 if no structure exists
        $oldBasic = $currentStructure ? (float) $currentStructure->basic_salary : 0.0;
        $oldHra = $currentStructure ? (float) $currentStructure->hra : 0.0;
        $oldAllowances = $currentStructure ? (float) $currentStructure->allowances : 0.0;
        $oldBonus = $currentStructure ? (float) $currentStructure->bonus : 0.0;
        $oldGross = $currentStructure ? (float) $currentStructure->gross_salary : 0.0;
        $oldNet = $currentStructure ? (float) $currentStructure->net_salary : 0.0;

        $newBasic = (float) $request->new_basic_salary;
        $newHra = (float) $request->new_hra;
        $newAllowances = (float) $request->new_allowances;
        $newBonus = (float) $request->new_bonus;
        $newGross = $newBasic + $newHra + $newAllowances + $newBonus;

        // Deductions copy from old structure, or default to 0
        $pfDeduction = $currentStructure ? (float) $currentStructure->pf_deduction : 0.0;
        $taxDeduction = $currentStructure ? (float) $currentStructure->tax_deduction : 0.0;
        $otherDeductions = $currentStructure ? (float) $currentStructure->other_deductions : 0.0;

        $newDeductions = $pfDeduction + $taxDeduction + $otherDeductions;
        $newNet = max(0, $newGross - $newDeductions);

        // Calculate increment percentage
        $incrementPercentage = 0.0;
        if ($oldGross > 0) {
            $incrementPercentage = round((($newGross - $oldGross) / $oldGross) * 100, 2);
        }

        $revision = null;

        DB::transaction(function () use (
            $employeeId, $oldBasic, $oldHra, $oldAllowances, $oldBonus, $oldGross, $oldNet,
            $newBasic, $newHra, $newAllowances, $newBonus, $newGross, $newNet,
            $incrementPercentage, $effectiveDate, $request,
            $pfDeduction, $taxDeduction, $otherDeductions, &$revision
        ) {
            // 1. Create SalaryRevision record
            $revision = SalaryRevision::create([
                'employee_id'          => $employeeId,
                'old_basic_salary'     => $oldBasic,
                'old_hra'              => $oldHra,
                'old_allowances'       => $oldAllowances,
                'old_bonus'            => $oldBonus,
                'old_gross_salary'     => $oldGross,
                'old_net_salary'       => $oldNet,
                'new_basic_salary'     => $newBasic,
                'new_hra'              => $newHra,
                'new_allowances'       => $newAllowances,
                'new_bonus'            => $newBonus,
                'new_gross_salary'     => $newGross,
                'new_net_salary'       => $newNet,
                'increment_percentage' => $incrementPercentage,
                'effective_date'       => $effectiveDate,
                'reason'               => $request->reason,
                'approved_by'          => auth()->id(),
            ]);

            // 2. Mark old active structures as inactive
            SalaryStructure::where('employee_id', $employeeId)
                ->where('status', 'active')
                ->update(['status' => 'inactive']);

            // 3. Create a new active SalaryStructure
            SalaryStructure::create([
                'employee_id'      => $employeeId,
                'basic_salary'     => $newBasic,
                'hra'              => $newHra,
                'allowances'       => $newAllowances,
                'bonus'            => $newBonus,
                'pf_deduction'     => $pfDeduction,
                'tax_deduction'    => $taxDeduction,
                'other_deductions' => $otherDeductions,
                'gross_salary'     => $newGross,
                'net_salary'       => $newNet,
                'effective_from'   => $effectiveDate,
                'status'           => 'active',
            ]);
        });

        $revision->load(['employee.department', 'employee.designation', 'approver']);
        $employee = $revision->employee;

        // Render blade view to PDF
        $pdf = \PDF::loadView('pdf.salary_revision', [
            'revision' => $revision,
            'employee' => $employee,
            'company_name' => \App\Models\CompanySetting::getValue('company_name') ?? 'HR Portal',
            'company_logo' => \App\Models\CompanySetting::getValue('company_logo') ?? null,
        ]);

        $dateStr = Carbon::parse($revision->effective_date)->format('Y-m-d');
        $filename = "salary-revision-{$employee->employee_code}-{$dateStr}.pdf";

        // Trigger template mail to employee regarding salary revision via background queue
        \App\Jobs\SendReusableMail::dispatch(
            'salary_revision_notice',
            $employee->email,
            [
                'name' => $employee->full_name,
                'employee_name' => $employee->full_name,
                'old_gross' => $revision->old_gross_salary,
                'new_gross' => $revision->new_gross_salary,
                'increment_percentage' => $revision->increment_percentage,
                'effective_date' => $revision->effective_date,
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

        return response()->json([
            'message' => 'Salary revision created successfully and new salary structure activated.',
            'data'    => $revision->load(['employee', 'approver']),
        ], 201);
    }

    /**
     * GET /api/v1/salary-revisions/{id}/download
     * Compile and stream a revised salary letter PDF
     */
    public function download($id)
    {
        $user = auth()->user();
        $revision = SalaryRevision::with(['employee.department', 'employee.designation', 'approver'])->findOrFail($id);
        
        // Authorization check
        if (!$this->isAdminOrHR()) {
            $employeeId = $user->employee->id ?? null;
            if ($revision->employee_id !== $employeeId) {
                abort(403, 'Unauthorized.');
            }
        }

        $employee = $revision->employee;

        // Render blade view to PDF
        $pdf = \PDF::loadView('pdf.salary_revision', [
            'revision' => $revision,
            'employee' => $employee,
            'company_name' => \App\Models\CompanySetting::getValue('company_name') ?? 'HR Portal',
            'company_logo' => \App\Models\CompanySetting::getValue('company_logo') ?? null,
        ]);

        $dateStr = Carbon::parse($revision->effective_date)->format('Y-m-d');
        $filename = "salary-revision-{$employee->employee_code}-{$dateStr}.pdf";

        return $pdf->stream($filename);
    }
}

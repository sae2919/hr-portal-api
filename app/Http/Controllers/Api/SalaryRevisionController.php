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
        $query = SalaryRevision::with(['employee.department', 'employee.designation', 'approver', 'oldDesignation', 'newDesignation']);

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
            'new_designation_id' => ['nullable', 'exists:designations,id'],
            'new_employment_type' => ['nullable', 'string', 'in:full_time,part_time,contract,intern'],
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

        // Fetch employee to read their settings (PF %, state for PT)
        $emp = Employee::with('user')->findOrFail($employeeId);

        $oldEmpType = $emp->employment_type;
        $newEmpType = $request->new_employment_type ?? $oldEmpType;

        if ($request->filled('new_designation_id')) {
            $newDesignation = \App\Models\Designation::find($request->new_designation_id);
            if ($newDesignation) {
                $title = strtolower($newDesignation->title);
                if ($oldEmpType === 'intern' && stripos($title, 'intern') === false) {
                    $newEmpType = 'full_time';
                }
            }
        }

        // 1. Recalculate PF Deduction
        $pfPercentage = (int) ($emp->pf_percentage ?? 0);
        $pfDeduction = round(($newBasic * $pfPercentage) / 100);

        // 2. Recalculate ESI
        $esiEmployee = 0.0;
        $esiEmployer = 0.0;
        $hasEsi = ($emp->esi_employee > 0 || ($currentStructure && $currentStructure->esi_employee > 0));
        if ($hasEsi && $newGross <= 21000) {
            $esiEmployee = round($newGross * 0.0075);
            $esiEmployer = round($newGross * 0.0325);
        }

        // 3. Recalculate PT
        $ptState = $emp->pt_state;
        $ptAmount = 0.0;
        if ($ptState) {
            if ($ptState === 'Andhra Pradesh' || $ptState === 'Telangana' || $ptState === 'Karnataka') {
                $ptAmount = ($newGross <= 15000) ? 0 : 200;
                if ($ptState === 'Karnataka' && $newGross > 15000 && $newGross <= 25000) {
                    $ptAmount = 150;
                }
            } elseif ($ptState === 'Maharashtra') {
                if ($newGross <= 7500) $ptAmount = 0;
                elseif ($newGross <= 10000) $ptAmount = 175;
                else $ptAmount = 200;
            } elseif ($ptState === 'Tamil Nadu') {
                $ptAmount = ($newGross <= 21000) ? 0 : 208;
            } elseif ($ptState === 'West Bengal') {
                if ($newGross <= 10000) $ptAmount = 0;
                elseif ($newGross <= 15000) $ptAmount = 110;
                elseif ($newGross <= 25000) $ptAmount = 130;
                elseif ($newGross <= 40000) $ptAmount = 150;
                else $ptAmount = 200;
            } elseif ($ptState === 'Gujarat') {
                if ($newGross <= 5999) $ptAmount = 0;
                elseif ($newGross <= 8999) $ptAmount = 80;
                elseif ($newGross <= 11999) $ptAmount = 150;
                else $ptAmount = 200;
            } elseif ($ptState === 'Madhya Pradesh') {
                $ptAmount = ($newGross <= 18750) ? 0 : 208;
            } elseif ($ptState === 'Kerala') {
                if ($newGross <= 11999) $ptAmount = 0;
                elseif ($newGross <= 17999) $ptAmount = 120;
                elseif ($newGross <= 29999) $ptAmount = 180;
                else $ptAmount = 208;
            } else {
                $ptAmount = (float) ($emp->pt_amount ?? 0);
            }
        }

        // 4. TDS and Other Deductions copy from old structure, or default to 0
        $taxDeduction = $currentStructure ? (float) $currentStructure->tax_deduction : 0.0;
        $otherDeductions = $currentStructure ? (float) $currentStructure->other_deductions : 0.0;

        $newDeductions = $pfDeduction + $taxDeduction + $otherDeductions + $esiEmployee + $ptAmount;
        $newNet = max(0, $newGross - $newDeductions);
        $newCtc = $newGross * 12 + $esiEmployer * 12;

        // Calculate increment percentage
        $incrementPercentage = 0.0;
        if ($oldGross > 0) {
            $incrementPercentage = round((($newGross - $oldGross) / $oldGross) * 100, 2);
        } elseif ($newGross > 0) {
            $incrementPercentage = 100.00;
        }

        $revision = null;

        DB::transaction(function () use (
            $emp, $oldBasic, $oldHra, $oldAllowances, $oldBonus, $oldGross, $oldNet,
            $newBasic, $newHra, $newAllowances, $newBonus, $newGross, $newNet, $newCtc,
            $pfDeduction, $esiEmployee, $esiEmployer, $ptAmount, $taxDeduction, $otherDeductions,
            $incrementPercentage, $effectiveDate, $request, &$revision, $oldEmpType, $newEmpType
        ) {
            // 1. Create SalaryRevision record
            $revision = SalaryRevision::create([
                'employee_id'          => $emp->id,
                'old_basic_salary'     => $oldBasic,
                'old_hra'              => $oldHra,
                'old_allowances'       => $oldAllowances,
                'old_bonus'            => $oldBonus,
                'old_gross_salary'     => $oldGross,
                'old_net_salary'       => $oldNet,
                'old_employment_type'  => $oldEmpType,
                'old_designation_id'   => $emp->designation_id,
                'new_basic_salary'     => $newBasic,
                'new_hra'              => $newHra,
                'new_allowances'       => $newAllowances,
                'new_bonus'            => $newBonus,
                'new_gross_salary'     => $newGross,
                'new_net_salary'       => $newNet,
                'new_employment_type'  => $newEmpType,
                'new_designation_id'   => $request->new_designation_id ?? $emp->designation_id,
                'increment_percentage' => $incrementPercentage,
                'effective_date'       => $effectiveDate,
                'reason'               => $request->reason,
                'approved_by'          => auth()->id(),
            ]);

            // 2. Update employee designation and other fields if new_designation_id is provided
            if ($request->filled('new_designation_id')) {
                if (strcasecmp($request->reason, 'Promotion') === 0) {
                    $emp->previous_designation_id = $emp->designation_id;
                }
                $emp->designation_id = $request->new_designation_id;

                $newDesignation = \App\Models\Designation::find($request->new_designation_id);
                if ($newDesignation) {
                    $title = strtolower($newDesignation->title);

                    // A. Transition from intern to full_time if new designation is not an intern
                    if ($emp->employment_type === 'intern' && stripos($title, 'intern') === false) {
                        $emp->employment_type = 'full_time';
                    }

                    // B. Auto-update position_level and user roles based on designation keywords
                    $resolvedPositionLevel = 'staff';
                    $resolvedRole = 'employee';

                    if (preg_match('/\b(ceo|founder|president|co-founder|co_founder|cto|cfo|coo|chief)\b/', $title)) {
                        $resolvedPositionLevel = 'c_level';
                        $resolvedRole = 'admin';
                    } elseif (preg_match('/\b(manager|director)\b/', $title)) {
                        $resolvedPositionLevel = 'manager';
                        $resolvedRole = 'manager';
                    } elseif (preg_match('/\b(lead|head|supervisor)\b/', $title)) {
                        $resolvedPositionLevel = 'team_lead';
                        $resolvedRole = 'team_lead';
                    }

                    $emp->position_level = $resolvedPositionLevel;

                    // Sync with linked User record
                    if ($emp->user) {
                        $emp->user->role = $resolvedRole;
                        $emp->user->save();

                        // Sync Spatie role
                        $emp->user->syncRoles([$resolvedRole]);
                    }
                }
            }

            // Sync the revised salary fields directly to the employee model properties
            $emp->employment_type = $newEmpType;
            $emp->basic_salary = $newBasic;
            $emp->hra = $newHra;
            $emp->allowances = [['type' => 'other', 'amount' => $newAllowances]];
            $emp->bonus = $newBonus;
            $emp->pf_deduction = $pfDeduction;
            $emp->esi_employee = $esiEmployee;
            $emp->esi_employer = $esiEmployer;
            $emp->pt_amount = $ptAmount;
            $emp->tds_amount = $taxDeduction;
            $emp->other_deductions = $otherDeductions;
            $emp->ctc = $newCtc;

            // Save quietly to prevent triggering saved event (avoid duplicate active SalaryStructure)
            $emp->saveQuietly();

            // 3. Mark old active structures as inactive
            SalaryStructure::where('employee_id', $emp->id)
                ->where('status', 'active')
                ->update(['status' => 'inactive']);

            // 4. Create a new active SalaryStructure
            SalaryStructure::create([
                'employee_id'      => $emp->id,
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

        $revision->load(['employee.department', 'employee.designation', 'approver', 'oldDesignation', 'newDesignation']);
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
        $revision = SalaryRevision::with(['employee.department', 'employee.designation', 'approver', 'oldDesignation', 'newDesignation'])->findOrFail($id);
        
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

    /**
     * PUT /api/v1/salary-revisions/{id}
     * Update a salary revision record (Admin/HR only)
     */
    public function update(Request $request, $id): JsonResponse
    {
        if (!$this->isAdminOrHR()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'new_basic_salary'   => ['required', 'numeric', 'min:0'],
            'new_hra'            => ['required', 'numeric', 'min:0'],
            'new_allowances'     => ['required', 'numeric', 'min:0'],
            'new_bonus'          => ['required', 'numeric', 'min:0'],
            'effective_date'     => ['required', 'date'],
            'reason'             => ['required', 'string', 'max:255'],
            'new_employment_type' => ['nullable', 'string', 'in:full_time,part_time,contract,intern'],
            'new_designation_id' => ['nullable', 'exists:designations,id'],
        ]);

        $revision = SalaryRevision::findOrFail($id);
        $emp = Employee::findOrFail($revision->employee_id);

        $newBasic = (float) $request->new_basic_salary;
        $newHra = (float) $request->new_hra;
        $newAllowances = (float) $request->new_allowances;
        $newBonus = (float) $request->new_bonus;
        $newGross = $newBasic + $newHra + $newAllowances + $newBonus;

        // 1. Recalculate PF Deduction
        $pfPercentage = (int) ($emp->pf_percentage ?? 0);
        $pfDeduction = round(($newBasic * $pfPercentage) / 100);

        // 2. Recalculate ESI
        $esiEmployee = 0.0;
        $esiEmployer = 0.0;
        $hasEsi = ($emp->esi_employee > 0);
        if ($hasEsi && $newGross <= 21000) {
            $esiEmployee = round($newGross * 0.0075);
            $esiEmployer = round($newGross * 0.0325);
        }

        // 3. Recalculate PT
        $ptState = $emp->pt_state;
        $ptAmount = 0.0;
        if ($ptState) {
            if ($ptState === 'Andhra Pradesh' || $ptState === 'Telangana' || $ptState === 'Karnataka') {
                $ptAmount = ($newGross <= 15000) ? 0 : 200;
                if ($ptState === 'Karnataka' && $newGross > 15000 && $newGross <= 25000) {
                    $ptAmount = 150;
                }
            } elseif ($ptState === 'Maharashtra') {
                if ($newGross <= 7500) $ptAmount = 0;
                elseif ($newGross <= 10000) $ptAmount = 175;
                else $ptAmount = 200;
            } elseif ($ptState === 'Tamil Nadu') {
                $ptAmount = ($newGross <= 21000) ? 0 : 208;
            } elseif ($ptState === 'West Bengal') {
                if ($newGross <= 10000) $ptAmount = 0;
                elseif ($newGross <= 15000) $ptAmount = 110;
                elseif ($newGross <= 25000) $ptAmount = 130;
                elseif ($newGross <= 40000) $ptAmount = 150;
                else $ptAmount = 200;
            } elseif ($ptState === 'Gujarat') {
                if ($newGross <= 5999) $ptAmount = 0;
                elseif ($newGross <= 8999) $ptAmount = 80;
                elseif ($newGross <= 11999) $ptAmount = 150;
                else $ptAmount = 200;
            } elseif ($ptState === 'Madhya Pradesh') {
                $ptAmount = ($newGross <= 18750) ? 0 : 208;
            } elseif ($ptState === 'Kerala') {
                if ($newGross <= 11999) $ptAmount = 0;
                elseif ($newGross <= 17999) $ptAmount = 120;
                elseif ($newGross <= 29999) $ptAmount = 180;
                else $ptAmount = 208;
            } else {
                $ptAmount = (float) ($emp->pt_amount ?? 0);
            }
        }

        $taxDeduction = $emp->tds_amount ?? 0.0;
        $otherDeductions = $emp->other_deductions ?? 0.0;

        $newDeductions = $pfDeduction + $taxDeduction + $otherDeductions + $esiEmployee + $ptAmount;
        $newNet = max(0, $newGross - $newDeductions);
        $newCtc = $newGross * 12 + $esiEmployer * 12;

        // Calculate increment percentage
        $incrementPercentage = 0.0;
        $oldGross = (float) $revision->old_gross_salary;
        if ($oldGross > 0) {
            $incrementPercentage = round((($newGross - $oldGross) / $oldGross) * 100, 2);
        } elseif ($newGross > 0) {
            $incrementPercentage = 100.00;
        }

        DB::transaction(function () use (
            $emp, $revision, $newBasic, $newHra, $newAllowances, $newBonus, $newGross, $newNet, $newCtc,
            $pfDeduction, $esiEmployee, $esiEmployer, $ptAmount, $taxDeduction, $otherDeductions,
            $incrementPercentage, $request
        ) {
            // Update SalaryRevision record
            $revision->update([
                'new_basic_salary'     => $newBasic,
                'new_hra'              => $newHra,
                'new_allowances'       => $newAllowances,
                'new_bonus'            => $newBonus,
                'new_gross_salary'     => $newGross,
                'new_net_salary'       => $newNet,
                'new_employment_type'  => $request->new_employment_type ?? $revision->new_employment_type,
                'new_designation_id'   => $request->new_designation_id ?? $revision->new_designation_id,
                'increment_percentage' => $incrementPercentage,
                'effective_date'       => Carbon::parse($request->effective_date),
                'reason'               => $request->reason,
            ]);

            // Sync the revised salary fields directly to the employee model properties
            if ($request->filled('new_employment_type')) {
                $emp->employment_type = $request->new_employment_type;
            }

            // Sync designation and resolve roles!
            if ($request->filled('new_designation_id')) {
                if (strcasecmp($request->reason, 'Promotion') === 0) {
                    $emp->previous_designation_id = $emp->designation_id;
                }
                $emp->designation_id = $request->new_designation_id;

                $newDesignation = \App\Models\Designation::find($request->new_designation_id);
                if ($newDesignation) {
                    $title = strtolower($newDesignation->title);

                    // A. Transition from intern to full_time if new designation is not an intern
                    if ($emp->employment_type === 'intern' && stripos($title, 'intern') === false) {
                        $emp->employment_type = 'full_time';
                    }

                    // B. Auto-update position_level and user roles based on designation keywords
                    $resolvedPositionLevel = 'staff';
                    $resolvedRole = 'employee';

                    if (preg_match('/\b(ceo|founder|president|co-founder|co_founder|cto|cfo|coo|chief)\b/', $title)) {
                        $resolvedPositionLevel = 'c_level';
                        $resolvedRole = 'admin';
                    } elseif (preg_match('/\b(manager|director)\b/', $title)) {
                        $resolvedPositionLevel = 'manager';
                        $resolvedRole = 'manager';
                    } elseif (preg_match('/\b(lead|head|supervisor)\b/', $title)) {
                        $resolvedPositionLevel = 'team_lead';
                        $resolvedRole = 'team_lead';
                    }

                    $emp->position_level = $resolvedPositionLevel;

                    // Sync with linked User record
                    if ($emp->user) {
                        $emp->user->role = $resolvedRole;
                        $emp->user->save();

                        // Sync Spatie role
                        $emp->user->syncRoles([$resolvedRole]);
                    }
                }
            }

            $emp->basic_salary = $newBasic;
            $emp->hra = $newHra;
            $emp->allowances = [['type' => 'other', 'amount' => $newAllowances]];
            $emp->bonus = $newBonus;
            $emp->pf_deduction = $pfDeduction;
            $emp->esi_employee = $esiEmployee;
            $emp->esi_employer = $esiEmployer;
            $emp->pt_amount = $ptAmount;
            $emp->tds_amount = $taxDeduction;
            $emp->other_deductions = $otherDeductions;
            $emp->ctc = $newCtc;

            // Save quietly
            $emp->saveQuietly();

            // Find the SalaryStructure created on this revision's effective date and update it,
            // or if not found, update the current active one.
            $structure = SalaryStructure::where('employee_id', $emp->id)
                ->where('status', 'active')
                ->latest('effective_from')
                ->first();

            if ($structure) {
                $structure->update([
                    'basic_salary'     => $newBasic,
                    'hra'              => $newHra,
                    'allowances'       => $newAllowances,
                    'bonus'            => $newBonus,
                    'pf_deduction'     => $pfDeduction,
                    'tax_deduction'    => $taxDeduction,
                    'other_deductions' => $otherDeductions,
                    'gross_salary'     => $newGross,
                    'net_salary'       => $newNet,
                    'effective_from'   => Carbon::parse($request->effective_date),
                ]);
            }
        });

        return response()->json([
            'message' => 'Salary revision updated successfully.',
            'data'    => $revision->load(['employee', 'approver', 'oldDesignation', 'newDesignation']),
        ]);
    }

    /**
     * DELETE /api/v1/salary-revisions/{id}
     * Delete a salary revision record (Admin/HR only)
     */
    public function destroy($id): JsonResponse
    {
        if (!$this->isAdminOrHR()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $revision = SalaryRevision::findOrFail($id);
        $revision->delete();

        return response()->json(['message' => 'Salary revision record deleted successfully.']);
    }
}

<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    // ── Role helpers ──────────────────────────────────────────────
    private function isAdminOrHR(): bool
{
    return auth()->user()->hasRole('super_admin')
        || auth()->user()->hasRole('admin')
        || auth()->user()->hasRole('hr');
}

    private function isManager(): bool
    {
        $role = auth()->user()->role ?? '';
        return auth()->user()->hasRole('manager')
            || auth()->user()->hasRole('team_lead')
            || auth()->user()->hasRole('sales_manager')
            || in_array($role, ['manager', 'team_lead', 'sales_manager']);
    }

    private function managerDeptId(): ?int
    {
        return auth()->user()->employee?->department_id;
    }

    // ── Index ─────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $user  = auth()->user();
        $query = Employee::with(['department', 'designation', 'manager']);

        if ($this->isAdminOrHR()) {
            if ($request->filled('search')) {
                $query->where(function ($q) use ($request) {
                    $q->where('first_name', 'like', "%{$request->search}%")
                      ->orWhere('last_name', 'like', "%{$request->search}%")
                      ->orWhere('email', 'like', "%{$request->search}%")
                      ->orWhere('employee_code', 'like', "%{$request->search}%")
                      ->orWhere('phone', 'like', "%{$request->search}%");
                });
            }
            if ($request->filled('department_id')) $query->where('department_id', $request->department_id);
            if ($request->filled('status')) $query->where('status', $request->status);
            if ($request->filled('employment_type')) $query->where('employment_type', $request->employment_type);
            if ($request->filled('reporting_to')) $query->where('reporting_to', $request->reporting_to);
        } elseif ($this->isManager()) {
            $deptId = $this->managerDeptId();
            if (!$deptId) return response()->json(['data' => [], 'message' => 'No department assigned.'], 200);
            $query->where('department_id', $deptId);
            if ($request->filled('search')) {
                $query->where(function ($q) use ($request) {
                    $q->where('first_name', 'like', "%{$request->search}%")
                      ->orWhere('last_name', 'like', "%{$request->search}%")
                      ->orWhere('email', 'like', "%{$request->search}%");
                });
            }
            if ($request->filled('status')) $query->where('status', $request->status);
        } else {
            $employeeId = $user->employee?->id;
            if (!$employeeId) return response()->json(['data' => [], 'message' => 'No employee record linked.'], 200);
            $query->where('id', $employeeId);
        }

        $employees = $query->orderBy('first_name')->get();
        return EmployeeResource::collection($employees);
    }

    // ── Managers (for Reporting To dropdown) ─────────────────────
    public function managers(Request $request): JsonResponse
    {
        $keywords = ['manager', 'lead', 'director', 'ceo', 'cto', 'cfo', 'coo', 'hr', 'head', 'chief', 'president', 'admin', 'supervisor'];

        $query = Employee::with(['department', 'designation'])
            ->where('status', 'active')
            ->whereHas('designation', function ($q) use ($keywords) {
                $q->where(function ($inner) use ($keywords) {
                    foreach ($keywords as $kw) {
                        $inner->orWhere('title', 'like', "%{$kw}%");
                    }
                });
            });

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        return response()->json([
            'data' => EmployeeResource::collection($query->orderBy('first_name')->get()),
        ]);
    }

    // ── Store ─────────────────────────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        if (!$this->isAdminOrHR()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            // Personal
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:employees,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'gender' => ['nullable', 'in:male,female,other'],
            'blood_group' => ['nullable', 'string', 'max:10'],
            'dob' => ['nullable', 'date'],
            // Documents
            'pan_number' => ['required', 'string', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/'],
            'aadhaar_number' => ['required', 'string', 'digits:12'],
            'driving_license' => ['nullable', 'string'],
            'passport_number' => ['nullable', 'string'],
            'voter_id' => ['nullable', 'string'],
            'uan_number' => ['nullable', 'string'],
            // Address
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string'],
            'state' => ['nullable', 'string'],
            'country' => ['nullable', 'string'],
            'pincode' => ['nullable', 'string'],
            // Job
            'department_id' => ['nullable', 'exists:departments,id'],
            'designation_id' => ['nullable', 'exists:designations,id'],
            'reporting_to' => ['nullable', 'exists:employees,id'],
            'joining_date' => ['required', 'date'],
            'employment_type' => ['nullable', 'in:full_time,part_time,contract,intern'],
            'status' => ['nullable', 'in:active,inactive,terminated'],
            // Emergency
            'emergency_contact_name' => ['required', 'string'],
            'emergency_contact_phone' => ['required', 'string'],
            'emergency_contact_relation' => ['nullable', 'string'],
            // Bank
            'bank_name' => ['required', 'string'],
            'bank_account_number' => ['required', 'string'],
            'bank_ifsc' => ['required', 'string'],
            'bank_branch' => ['nullable', 'string'],
            // Salary
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'hra' => ['nullable', 'numeric', 'min:0'],
            // ✅ Allowances as JSON array with validated types
            'allowances' => ['nullable', 'array'],
            'allowances.*.type' => ['nullable', 'string', 'in:transport,food,medical,special,other'],
            'allowances.*.amount' => ['nullable', 'numeric', 'min:0'],
            // ✅ Bonus field
            'bonus' => ['nullable', 'numeric', 'min:0'],
            'pf_percentage' => ['nullable', 'integer', 'min:0', 'max:12'],
            'pf_deduction' => ['nullable', 'numeric', 'min:0'],
            'esi_employee' => ['nullable', 'numeric', 'min:0'],
            'esi_employer' => ['nullable', 'numeric', 'min:0'],
            'pt_amount' => ['nullable', 'numeric', 'min:0'],
            'pt_state' => ['nullable', 'string', 'max:100'],
            'tds_amount' => ['nullable', 'numeric', 'min:0'],
            'other_deductions' => ['nullable', 'numeric', 'min:0'],
            'ctc' => ['nullable', 'numeric', 'min:0'],
        ]);

        $salaryFields = [
            'basic_salary', 'hra', 'allowances', 'bonus',
            'pf_percentage', 'pf_deduction', 'esi_employee', 'esi_employer',
            'pt_amount', 'pt_state', 'tds_amount', 'other_deductions', 'ctc',
        ];

        $salaryData = [
            'basic_salary' => $request->basic_salary,
            'hra' => $request->hra ?? 0,
            'allowances' => $request->allowances ?? [],  // ✅ JSON array
            'bonus' => $request->bonus ?? 0,  // ✅ Bonus
            'pf_percentage' => $request->pf_percentage ?? 0,
            'pf_deduction' => $request->pf_deduction ?? 0,
            'esi_employee' => $request->esi_employee ?? 0,
            'esi_employer' => $request->esi_employer ?? 0,
            'pt_amount' => $request->pt_amount ?? 0,
            'pt_state' => $request->pt_state,
            'tds_amount' => $request->tds_amount ?? 0,
            'other_deductions' => $request->other_deductions ?? 0,
            'ctc' => $request->ctc ?? 0,
        ];

        $employeeData = array_merge($request->except($salaryFields), $salaryData);

        $employee = new Employee($employeeData);
        $employee->_salary_data = $salaryData;
        $employee->save();

        return response()->json([
            'message' => 'Employee created successfully.',
            'data' => new EmployeeResource($employee->load(['department', 'designation', 'manager'])),
        ], 201);
    }

    // ── Show ──────────────────────────────────────────────────────
    public function show(Employee $employee): JsonResponse
    {
        $user = auth()->user();

        if ($this->isAdminOrHR()) {
            // full access
        } elseif ($this->isManager()) {
            if ($employee->department_id !== $this->managerDeptId()) {
                return response()->json(['message' => 'Unauthorized.'], 403);
            }
        } else {
            if ($user->employee?->id !== $employee->id) {
                return response()->json(['message' => 'Unauthorized.'], 403);
            }
        }

        return response()->json([
            'data' => new EmployeeResource($employee->load(['department', 'designation', 'manager'])),
        ]);
    }

    // ── Update ────────────────────────────────────────────────────
    public function update(Request $request, Employee $employee): JsonResponse
    {
        if (!$this->isAdminOrHR()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'first_name' => ['sometimes', 'string', 'max:100'],
            'last_name' => ['sometimes', 'string', 'max:100'],
            'email' => ['sometimes', 'email', "unique:employees,email,{$employee->id}"],
            'phone' => ['nullable', 'string', 'max:20'],
            'gender' => ['nullable', 'in:male,female,other'],
            'blood_group' => ['nullable', 'string', 'max:10'],
            'dob' => ['nullable', 'date'],
            'pan_number' => ['sometimes', 'nullable', 'string', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/'],
            'aadhaar_number' => ['sometimes', 'nullable', 'string', 'digits:12'],
            'driving_license' => ['nullable', 'string'],
            'passport_number' => ['nullable', 'string'],
            'voter_id' => ['nullable', 'string'],
            'uan_number' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string'],
            'state' => ['nullable', 'string'],
            'country' => ['nullable', 'string'],
            'pincode' => ['nullable', 'string'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'designation_id' => ['nullable', 'exists:designations,id'],
            'reporting_to' => ['nullable', 'exists:employees,id'],
            'joining_date' => ['sometimes', 'date'],
            'employment_type' => ['nullable', 'in:full_time,part_time,contract,intern'],
            'status' => ['nullable', 'in:active,inactive,terminated'],
            'emergency_contact_name' => ['nullable', 'string'],
            'emergency_contact_phone' => ['nullable', 'string'],
            'emergency_contact_relation' => ['nullable', 'string'],
            'bank_name' => ['nullable', 'string'],
            'bank_account_number' => ['nullable', 'string'],
            'bank_ifsc' => ['nullable', 'string'],
            'bank_branch' => ['nullable', 'string'],
            'basic_salary' => ['sometimes', 'numeric', 'min:0'],
            'hra' => ['nullable', 'numeric', 'min:0'],
            // ✅ Allowances validation for update
            'allowances' => ['nullable', 'array'],
            'allowances.*.type' => ['nullable', 'string', 'in:transport,food,medical,special,other'],
            'allowances.*.amount' => ['nullable', 'numeric', 'min:0'],
            // ✅ Bonus validation
            'bonus' => ['nullable', 'numeric', 'min:0'],
            'pf_percentage' => ['nullable', 'integer', 'min:0', 'max:12'],
            'pf_deduction' => ['nullable', 'numeric', 'min:0'],
            'esi_employee' => ['nullable', 'numeric', 'min:0'],
            'esi_employer' => ['nullable', 'numeric', 'min:0'],
            'pt_amount' => ['nullable', 'numeric', 'min:0'],
            'pt_state' => ['nullable', 'string', 'max:100'],
            'tds_amount' => ['nullable', 'numeric', 'min:0'],
            'other_deductions' => ['nullable', 'numeric', 'min:0'],
            'ctc' => ['nullable', 'numeric', 'min:0'],
        ]);

        if ($validator->fails()) {
            \Log::error('Employee update validation failed for ID ' . $employee->id . ':', $validator->errors()->toArray());
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        $employee->update($request->all());

        return response()->json([
            'message' => 'Employee updated successfully.',
            'data' => new EmployeeResource($employee->load(['department', 'designation', 'manager'])),
        ]);
    }

    // ── Destroy ───────────────────────────────────────────────────
    public function destroy(Employee $employee): JsonResponse
    {
        if (!auth()->user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $employee->delete();
        return response()->json(['message' => 'Employee deleted successfully.']);
    }
}

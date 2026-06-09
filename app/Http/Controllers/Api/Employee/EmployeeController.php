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
        $query = Employee::with(['department', 'designation', 'manager.designation']);

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
            $employeeId = $user->employee?->id;
            if ($employeeId) {
                $query->where(function ($q) use ($employeeId) {
                    $q->where('reporting_to', $employeeId)
                      ->orWhere('id', $employeeId);
                });
            } else {
                $query->whereRaw('0 = 1');
            }

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
        $query = Employee::with(['department', 'designation', 'manager.designation'])
            ->where('status', 'active');

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
            'data' => new EmployeeResource($employee->load(['department', 'designation', 'manager', 'previousDesignation', 'assetAllocations.asset', 'salaryRevisions'])),
        ], 201);
    }

    // ── Show ──────────────────────────────────────────────────────
    public function show(Employee $employee): JsonResponse
    {
        $user = auth()->user();

        if ($this->isAdminOrHR()) {
            // full access
        } elseif ($this->isManager()) {
            $myEmployeeId = $user->employee?->id;
            if ($employee->id !== $myEmployeeId && $employee->reporting_to !== $myEmployeeId) {
                return response()->json(['message' => 'Unauthorized.'], 403);
            }
        } else {
            if ($user->employee?->id !== $employee->id) {
                return response()->json(['message' => 'Unauthorized.'], 403);
            }
        }

        return response()->json([
            'data' => new EmployeeResource($employee->load(['department', 'designation', 'manager', 'previousDesignation', 'assetAllocations.asset', 'salaryRevisions'])),
        ]);
    }

    // ── Update ────────────────────────────────────────────────────
    public function update(Request $request, Employee $employee): JsonResponse
    {
        $isSelf = auth()->user()->employee?->id === $employee->id;

        if (!$this->isAdminOrHR() && !$isSelf) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $updateData = $request->all();

        if (!$this->isAdminOrHR() && $isSelf) {
            // Prevent email change
            if ($request->has('email') && $request->email !== $employee->email) {
                return response()->json([
                    'message' => 'Email cannot be changed by the employee.',
                    'errors' => ['email' => ['Email cannot be changed by the employee.']]
                ], 422);
            }

            // Prevent first name change
            if ($request->has('first_name') && $request->first_name !== $employee->first_name) {
                return response()->json([
                    'message' => 'First name cannot be changed by the employee.',
                    'errors' => ['first_name' => ['First name cannot be changed by the employee.']]
                ], 422);
            }

            // Prevent last name change
            if ($request->has('last_name') && $request->last_name !== $employee->last_name) {
                return response()->json([
                    'message' => 'Last name cannot be changed by the employee.',
                    'errors' => ['last_name' => ['Last name cannot be changed by the employee.']]
                ], 422);
            }

            // Only allow editing personal information and emergency contact (excluding name/email)
            $allowedFields = [
                'phone', 'gender', 'blood_group', 'dob',
                'address', 'city', 'state', 'country', 'pincode',
                'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relation'
            ];
            $updateData = $request->only($allowedFields);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($updateData, [
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

        $oldEmail = $employee->email;
        $employee->update($updateData);

        // If email was changed, sync it to the associated user login record
        if (isset($updateData['email']) && $employee->email !== $oldEmail && $employee->user) {
            $employee->user->update([
                'email' => $employee->email
            ]);
        }

        return response()->json([
            'message' => 'Employee updated successfully.',
            'data' => new EmployeeResource($employee->load(['department', 'designation', 'manager', 'previousDesignation', 'assetAllocations.asset', 'salaryRevisions'])),
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

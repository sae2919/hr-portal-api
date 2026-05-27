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
        return auth()->user()->hasRole('admin') || auth()->user()->hasRole('hr');
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

    public function index(Request $request)
    {
        $user  = auth()->user();
        $query = Employee::with(['department', 'designation']);

        if ($this->isAdminOrHR()) {
            // Admin/HR: see all employees with full filters
            if ($request->filled('search')) {
                $query->where(function ($q) use ($request) {
                    $q->where('first_name', 'like', "%{$request->search}%")
                      ->orWhere('last_name', 'like', "%{$request->search}%")
                      ->orWhere('email', 'like', "%{$request->search}%")
                      ->orWhere('employee_code', 'like', "%{$request->search}%")
                      ->orWhere('phone', 'like', "%{$request->search}%");
                });
            }

            if ($request->filled('department_id')) {
                $query->where('department_id', $request->department_id);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('employment_type')) {
                $query->where('employment_type', $request->employment_type);
            }

        } elseif ($this->isManager()) {
            // Manager / Team Lead / Sales Manager: only their own department
            $deptId = $this->managerDeptId();
            if (!$deptId) {
                return response()->json(['data' => [], 'message' => 'No department assigned to this manager.'], 200);
            }
            $query->where('department_id', $deptId);

            if ($request->filled('search')) {
                $query->where(function ($q) use ($request) {
                    $q->where('first_name', 'like', "%{$request->search}%")
                      ->orWhere('last_name', 'like', "%{$request->search}%")
                      ->orWhere('email', 'like', "%{$request->search}%");
                });
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

        } else {
            // Employee: only their own profile
            $employeeId = $user->employee?->id;
            if (!$employeeId) {
                return response()->json(['data' => [], 'message' => 'No employee record linked.'], 200);
            }
            $query->where('id', $employeeId);
        }

        $employees = $query->orderBy('first_name')->paginate(10);

        return EmployeeResource::collection($employees);
    }

    public function store(Request $request): JsonResponse
    {
        if (!$this->isAdminOrHR()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'first_name'      => ['required', 'string', 'max:100'],
            'last_name'       => ['required', 'string', 'max:100'],
            'email'           => ['required', 'email', 'unique:employees,email'],
            'phone'           => ['nullable', 'string', 'max:20'],
            'gender'          => ['nullable', 'in:male,female,other'],
            'blood_group'     => ['nullable', 'string', 'max:10'],
            'dob'             => ['nullable', 'date'],
            'address'         => ['nullable', 'string'],
            'city'            => ['nullable', 'string'],
            'state'           => ['nullable', 'string'],
            'country'         => ['nullable', 'string'],
            'pincode'         => ['nullable', 'string'],
            'department_id'   => ['nullable', 'exists:departments,id'],
            'designation_id'  => ['nullable', 'exists:designations,id'],
            'joining_date'    => ['required', 'date'],
            'employment_type' => ['nullable', 'in:full_time,part_time,contract,intern'],
            'status'          => ['nullable', 'in:active,inactive,terminated'],
            'emergency_contact_name'     => ['nullable', 'string'],
            'emergency_contact_phone'    => ['nullable', 'string'],
            'emergency_contact_relation' => ['nullable', 'string'],
            'bank_name'           => ['nullable', 'string'],
            'bank_account_number' => ['nullable', 'string'],
            'bank_ifsc'           => ['nullable', 'string'],
            'bank_branch'         => ['nullable', 'string'],
        ]);

        $employee = Employee::create($request->all());

        return response()->json([
            'message' => 'Employee created successfully.',
            'data'    => new EmployeeResource($employee->load(['department', 'designation'])),
        ], 201);
    }

    public function show(Employee $employee): JsonResponse
    {
        $user = auth()->user();

        if ($this->isAdminOrHR()) {
            // Full access
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
            'data' => new EmployeeResource($employee->load(['department', 'designation'])),
        ]);
    }

    public function update(Request $request, Employee $employee): JsonResponse
    {
        if (!$this->isAdminOrHR()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'first_name'      => ['sometimes', 'string', 'max:100'],
            'last_name'       => ['sometimes', 'string', 'max:100'],
            'email'           => ['sometimes', 'email', "unique:employees,email,{$employee->id}"],
            'phone'           => ['nullable', 'string', 'max:20'],
            'gender'          => ['nullable', 'in:male,female,other'],
            'blood_group'     => ['nullable', 'string', 'max:10'],
            'dob'             => ['nullable', 'date'],
            'department_id'   => ['nullable', 'exists:departments,id'],
            'designation_id'  => ['nullable', 'exists:designations,id'],
            'joining_date'    => ['sometimes', 'date'],
            'employment_type' => ['nullable', 'in:full_time,part_time,contract,intern'],
            'status'          => ['nullable', 'in:active,inactive,terminated'],
        ]);

        $employee->update($request->all());

        return response()->json([
            'message' => 'Employee updated successfully.',
            'data'    => new EmployeeResource($employee->load(['department', 'designation'])),
        ]);
    }

    public function destroy(Employee $employee): JsonResponse
    {
        if (!auth()->user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $employee->delete();

        return response()->json(['message' => 'Employee deleted successfully.']);
    }
}
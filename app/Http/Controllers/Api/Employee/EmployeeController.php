<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Employee::with(['department', 'designation']);

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

        $employees = $query->orderBy('first_name')->get();

        return response()->json([
            'data' => EmployeeResource::collection($employees),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'first_name'      => ['required', 'string', 'max:100'],
            'last_name'       => ['required', 'string', 'max:100'],
            'email'           => ['required', 'email', 'unique:employees,email'],
            'phone'           => ['nullable', 'string', 'max:20'],
            'gender'          => ['nullable', 'in:male,female,other'],
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
            // Emergency
            'emergency_contact_name'     => ['nullable', 'string'],
            'emergency_contact_phone'    => ['nullable', 'string'],
            'emergency_contact_relation' => ['nullable', 'string'],
            // Bank
            'bank_name'           => ['nullable', 'string'],
            'bank_account_number' => ['nullable', 'string'],
            'bank_ifsc'           => ['nullable', 'string'],
            'bank_branch'         => ['nullable', 'string'],
        ]);

        $employee = Employee::create($request->all());

        return response()->json([
            'message' => 'Employee created successfully.',
            'data'    => new EmployeeResource(
                $employee->load(['department', 'designation'])
            ),
        ], 201);
    }

    public function show(Employee $employee): JsonResponse
    {
        return response()->json([
            'data' => new EmployeeResource(
                $employee->load(['department', 'designation'])
            ),
        ]);
    }

    public function update(Request $request, Employee $employee): JsonResponse
    {
        $request->validate([
            'first_name'      => ['sometimes', 'string', 'max:100'],
            'last_name'       => ['sometimes', 'string', 'max:100'],
            'email'           => ['sometimes', 'email', "unique:employees,email,{$employee->id}"],
            'phone'           => ['nullable', 'string', 'max:20'],
            'gender'          => ['nullable', 'in:male,female,other'],
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
            'data'    => new EmployeeResource(
                $employee->load(['department', 'designation'])
            ),
        ]);
    }

    public function destroy(Employee $employee): JsonResponse
    {
        $employee->delete();

        return response()->json([
            'message' => 'Employee deleted successfully.',
        ]);
    }
}
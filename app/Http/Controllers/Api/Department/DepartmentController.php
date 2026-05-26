<?php

namespace App\Http\Controllers\Api\Department;

use App\Http\Controllers\Controller;
use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        // 1. Explicitly fetch specific primitive data types to avoid passing arrays into query builders
        $perPage = $request->integer('per_page', 10);
        $search = $request->string('search')->trim();
        $status = $request->string('status')->trim();

        $query = Department::with([
            'parent'
        ])->withCount('employees');

        // 2. Safe check against string lengths instead of raw request object extraction
        if ($search->isNotEmpty()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($status->isNotEmpty()) {
            $query->where('status', $status);
        }

        $departments = $query
            ->orderBy('name')
            ->paginate($perPage); // 👈 Dynamically uses your project global per_page property 

        return DepartmentResource::collection($departments);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'        => ['required', 'string', 'min:2', 'max:100', 'unique:departments,name'],
            'code'        => ['nullable', 'string', 'max:20', 'unique:departments,code'],
            'description' => ['nullable', 'string', 'max:500'],
            'status'      => ['nullable', 'in:active,inactive'],
            'parent_id'   => ['nullable', 'exists:departments,id'],
        ]);

        $department = Department::create([
            'name'        => $request->name,
            'code'        => $request->code ?? strtoupper(substr($request->name, 0, 4)),
            'description' => $request->description,
            'status'      => $request->status ?? 'active',
            'parent_id'   => $request->parent_id,
        ]);

        return response()->json([
            'message' => 'Department created successfully.',
            'data'    => new DepartmentResource($department->load('parent')),
        ], 201);
    }

    public function show(Department $department): JsonResponse
    {
        return response()->json([
            'data' => new DepartmentResource($department->load('parent')),
        ]);
    }

    public function update(Request $request, Department $department): JsonResponse
    {
        // 1. Validate inputs thoroughly
        $validatedData = $request->validate([
            'name'        => ['required', 'string', 'min:2', 'max:100', "unique:departments,name,{$department->id}"],
            'code'        => ['nullable', 'string', 'max:20', "unique:departments,code,{$department->id}"],
            'description' => ['nullable', 'string', 'max:500'],
            'status'      => ['nullable', 'in:active,inactive'],
            'parent_id'   => ['nullable', 'exists:departments,id'],
        ]);

        if ($request->parent_id && (int)$request->parent_id === $department->id) {
            return response()->json([
                'message' => 'A department cannot be its own parent.'
            ], 422);
        }

        // 💡 CRITICAL SAFETY FIX: Use only clean, validated data fields.
        // This explicitly blocks 'page' or 'per_page' arrays from leaking into the update process.
        $department->update($validatedData);

        return response()->json([
            'message' => 'Department updated successfully.',
            'data'    => new DepartmentResource($department->load('parent')),
        ]);
    }

    public function destroy(Department $department): JsonResponse
    {
        if ($department->employees()->exists()) {
            return response()->json([
                'message' => 'Cannot delete department with assigned employees.',
            ], 422);
        }

        if ($department->children()->exists()) {
            return response()->json([
                'message' => 'Cannot delete department that has sub-departments.',
            ], 422);
        }

        $department->delete();

        return response()->json([
            'message' => 'Department deleted successfully.'
        ]);
    }
}
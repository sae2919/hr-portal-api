<?php

namespace App\Http\Controllers\Api\Leave;

use App\Http\Controllers\Controller;
use App\Http\Resources\LeaveTypeResource;
use App\Models\LeaveType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveTypeController extends Controller
{
    public function index()
{
    // Fetch all records sorted alphabetically for dropdown consistency
    $types = LeaveType::orderBy('name')->get();
    
    return LeaveTypeResource::collection($types);
}

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'          => ['required', 'string', 'unique:leave_types,name'],
            'code'          => ['required', 'string', 'unique:leave_types,code'],
            'days_per_year' => ['required', 'integer', 'min:0'],
            'carry_forward' => ['boolean'],
            'is_paid'       => ['boolean'],
            'color'         => ['nullable', 'string'],
            'description'   => ['nullable', 'string'],
        ]);

        $type = LeaveType::create($request->all());
        return response()->json([
            'message' => 'Leave type created.',
            'data'    => new LeaveTypeResource($type),
        ], 201);
    }

    public function update(Request $request, LeaveType $leaveType): JsonResponse
    {
        $request->validate([
            'name'          => ['sometimes', 'required', 'string', "unique:leave_types,name,{$leaveType->id}"],
            'code'          => ['sometimes', 'required', 'string', "unique:leave_types,code,{$leaveType->id}"],
            'days_per_year' => ['sometimes', 'required', 'integer', 'min:0'],
            'carry_forward' => ['boolean'],
            'is_paid'       => ['boolean'],
            'color'         => ['nullable', 'string'],
            'description'   => ['nullable', 'string'],
            'status'        => ['sometimes', 'string', 'in:active,inactive'],
        ]);

        $leaveType->update($request->all());
        return response()->json([
            'message' => 'Leave type updated.',
            'data'    => new LeaveTypeResource($leaveType),
        ]);
    }

    public function destroy(LeaveType $leaveType): JsonResponse
    {
        if ($leaveType->leaves()->exists()) {
            return response()->json(['message' => 'Cannot delete leave type with existing applications.'], 422);
        }
        $leaveType->delete();
        return response()->json(['message' => 'Leave type deleted.']);
    }
}
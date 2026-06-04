<?php

namespace App\Http\Controllers\Api\Designation;

use App\Http\Controllers\Controller;
use App\Http\Resources\DesignationResource;
use App\Models\Designation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DesignationController extends Controller
{
    public function index(Request $request)
    {
        $query = Designation::with('department');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', "%{$request->search}%")
                  ->orWhere('code', 'like', "%{$request->search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        $designations = $query
            ->orderBy('title')
            ->paginate($request->per_page ?? 10);

        return DesignationResource::collection($designations);
    }

    public function store(Request $request): JsonResponse
    {
        // 💡 CRITICAL FIX: If code is blank, auto-generate a guaranteed unique code BEFORE validation
        if (!$request->filled('code') && $request->filled('title')) {
            // Remove special characters and take first 4 letters
            $cleanTitle = preg_replace('/[^A-Za-z0-9]/', '', $request->title);
            $baseCode = strtoupper(substr($cleanTitle, 0, 4));
            if (empty($baseCode)) {
                $baseCode = 'DESG';
            }
            
            // Loop until we find a unique code in the database
            $code = $baseCode;
            $counter = 1;
            while (Designation::where('code', $code)->exists()) {
                $code = substr($baseCode, 0, 4 - strlen((string)$counter)) . $counter;
                $counter++;
            }
            
            $request->merge(['code' => $code]);
        }

        try {
            $request->validate([
                'title'         => ['required', 'string', 'min:2', 'max:100', 'unique:designations,title'],
                'code'          => ['nullable', 'string', 'max:20', 'unique:designations,code'],
                'description'   => ['nullable', 'string', 'max:500'],
                'status'        => ['nullable', 'in:active,inactive'],
                'department_id' => ['nullable', 'exists:departments,id'],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Designation creation validation failed: ' . json_encode($e->errors()));
            throw $e;
        }

        $designation = Designation::create([
            'title'         => $request->title,
            'code'          => $request->code, // Guaranteed unique and validated
            'description'   => $request->description,
            'status'        => $request->status ?? 'active',
            'department_id' => $request->department_id,
        ]);

        return response()->json([
            'message' => 'Designation created successfully.',
            'data'    => new DesignationResource($designation->load('department')),
        ], 201);
    }

    public function show(Designation $designation): JsonResponse
    {
        return response()->json([
            'data' => new DesignationResource($designation->load('department')),
        ]);
    }

    public function update(Request $request, Designation $designation): JsonResponse
    {
        $request->validate([
            'title'         => ['required', 'string', 'min:2', 'max:100', "unique:designations,title,{$designation->id}"],
            'code'          => ['nullable', 'string', 'max:20', "unique:designations,code,{$designation->id}"],
            'description'   => ['nullable', 'string', 'max:500'],
            'status'        => ['nullable', 'in:active,inactive'],
            'department_id' => ['nullable', 'exists:departments,id'],
        ]);

        $designation->update($request->all());

        return response()->json([
            'message' => 'Designation updated successfully.',
            'data'    => new DesignationResource($designation->load('department')),
        ]);
    }

    public function destroy(Designation $designation): JsonResponse
    {
        if ($designation->employees()->exists()) {
            return response()->json([
                'message' => 'Cannot delete designation with assigned employees.',
            ], 422);
        }

        $designation->delete();

        return response()->json([
            'message' => 'Designation deleted successfully.',
        ]);
    }
}
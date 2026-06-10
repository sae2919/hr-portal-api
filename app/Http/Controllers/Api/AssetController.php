<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetAllocation;
use App\Models\OnboardingRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AssetController extends Controller
{
    /**
     * Get all assets
     */
    public function index(Request $request): JsonResponse
    {
        $query = Asset::with(['currentAllocation.employee', 'currentAllocation.onboardingRequest', 'currentAllocation.allocator']);
        
        // Filter by type
        if ($request->type) {
            if ($request->type === 'other') {
                $query->whereNotIn('type', ['laptop', 'monitor', 'phone', 'keyboard', 'mouse', 'headset', 'docking_station']);
            } else {
                $query->where('type', $request->type);
            }
        }
        
        // Filter by status
        if ($request->status) {
            if ($request->status === 'exclude_assigned') {
                $query->where('status', '!=', 'assigned');
            } else {
                $query->where('status', $request->status);
            }
        }
        
        // Search
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('asset_code', 'like', "%{$request->search}%")
                  ->orWhere('serial_number', 'like', "%{$request->search}%");
            });
        }
        
        $assets = $query->latest()->paginate($request->per_page ?? 10);
        
        return response()->json([
            'success' => true,
            'data' => $assets,
            'total_registered' => Asset::count()
        ]);
    }
    
    /**
     * Store a new asset
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|unique:assets,serial_number',
            'purchase_date' => 'nullable|date',
            'purchase_price' => 'nullable|numeric|min:0',
            'specifications' => 'nullable|string',
            'has_charger' => 'nullable|boolean',
            'has_sim' => 'nullable|boolean',
        ]);
        
        $asset = Asset::create($request->all());
        
        return response()->json([
            'success' => true,
            'message' => 'Asset created successfully',
            'data' => $asset
        ], 201);
    }
    
    /**
     * Get single asset
     */
    public function show(Asset $asset): JsonResponse
    {
        $asset->load(['allocations.employee', 'allocations.onboardingRequest']);
        
        return response()->json([
            'success' => true,
            'data' => $asset
        ]);
    }
    
    /**
     * Update asset
     */
    public function update(Request $request, Asset $asset): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|string|max:255',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|unique:assets,serial_number,' . $asset->id,
            'status' => 'sometimes|in:available,assigned,maintenance,scrapped',
            'specifications' => 'nullable|string',
            'has_charger' => 'nullable|boolean',
            'has_sim' => 'nullable|boolean',
        ]);
        
        $asset->update($request->all());
        
        return response()->json([
            'success' => true,
            'message' => 'Asset updated successfully',
            'data' => $asset
        ]);
    }
    
    /**
     * Allocate asset to onboarding request
     */
    public function allocate(Request $request, Asset $asset): JsonResponse
    {
        $request->validate([
            'onboarding_request_id' => 'required_without:employee_id|nullable|exists:onboarding_requests,id',
            'employee_id' => 'required_without:onboarding_request_id|nullable|exists:employees,id',
            'condition_notes' => 'nullable|string',
            'charger_given' => 'nullable|boolean',
            'sim_given' => 'nullable|boolean',
        ]);
        
        if ($asset->status !== 'available') {
            return response()->json([
                'success' => false,
                'message' => 'Asset is not available for allocation'
            ], 422);
        }
        
        DB::beginTransaction();
        
        try {
            $allocation = AssetAllocation::create([
                'asset_id' => $asset->id,
                'onboarding_request_id' => $request->onboarding_request_id,
                'employee_id' => $request->employee_id,
                'allocated_date' => now(),
                'status' => 'allocated',
                'condition_notes' => $request->condition_notes,
                'allocated_by' => auth()->id(),
                'charger_given' => $request->charger_given,
                'sim_given' => $request->sim_given,
            ]);
            
            $asset->update(['status' => 'assigned']);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Asset allocated successfully',
                'data' => $allocation->load('asset')
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to allocate asset: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Return allocated asset
     */
    public function returnAsset(AssetAllocation $allocation, Request $request): JsonResponse
    {
        $request->validate([
            'return_notes' => 'nullable|string',
            'condition' => 'nullable|string',
        ]);
        
        $allocation->update([
            'return_date' => now(),
            'status' => 'returned',
            'return_notes' => $request->return_notes,
        ]);
        
        $allocation->asset->update(['status' => 'available']);
        
        return response()->json([
            'success' => true,
            'message' => 'Asset returned successfully',
            'data' => $allocation
        ]);
    }
    
    /**
     * Delete asset
     */
    public function destroy(Asset $asset): JsonResponse
    {
        if ($asset->status === 'assigned') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete assigned asset. Please return it first.'
            ], 422);
        }
        
        $asset->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Asset deleted successfully'
        ]);
    }
    
    /**
     * Get available assets for dropdown
     */
    public function available(): JsonResponse
    {
        $assets = Asset::where('status', 'available')
            ->get(['id', 'name', 'asset_code', 'type', 'brand', 'model']);
        
        return response()->json([
            'success' => true,
            'data' => $assets
        ]);
    }
}
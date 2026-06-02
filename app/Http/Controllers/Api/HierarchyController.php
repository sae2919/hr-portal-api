<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Services\HierarchyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HierarchyController extends Controller
{
    protected HierarchyService $hierarchyService;
    
    public function __construct(HierarchyService $hierarchyService)
    {
        $this->hierarchyService = $hierarchyService;
    }
    
    /**
     * Get complete organization tree
     */
    public function orgTree(): JsonResponse
    {
        $tree = $this->hierarchyService->buildOrgTree();
        
        return response()->json([
            'success' => true,
            'data' => $tree,
            'stats' => $this->hierarchyService->getHierarchyStats(),
        ]);
    }
    
    /**
     * Get team members for current user (for managers/team leads)
     */
    public function myTeam(): JsonResponse
    {
        $user = auth()->user();
        $employee = $user->employee;
        
        if (!$employee) {
            return response()->json([
                'success' => true,
                'data' => [],
                'stats' => [
                    'total' => 0,
                    'active' => 0,
                    'on_leave' => 0,
                ],
                'message' => 'No employee record found'
            ], 200);
        }
        
        $team = $this->hierarchyService->getTeamMembers($employee->id);
        
        return response()->json([
            'success' => true,
            'data' => $team,
            'stats' => [
                'total' => $team->count(),
                'active' => $team->where('status', 'active')->count(),
                'on_leave' => $team->where('status', 'inactive')->count(),
            ]
        ]);
    }
    
    /**
     * Get my reporting chain (who I report to)
     */
    public function myReportingChain(): JsonResponse
    {
        $user = auth()->user();
        $employee = $user->employee;
        
        if (!$employee) {
            return response()->json([
                'success' => true,
                'data' => [],
                'current' => null,
                'message' => 'No employee record found'
            ], 200);
        }
        
        $chain = $this->hierarchyService->getReportingChain($employee->id);
        
        return response()->json([
            'success' => true,
            'data' => $chain,
            'current' => [
                'id' => $employee->id,
                'name' => $employee->full_name,
                'designation' => $employee->designation?->name,
                'position_level' => $employee->position_level ?? 'staff',
            ]
        ]);
    }
    
    /**
     * Get direct reports for a specific employee
     */
    public function directReports(Request $request, int $employeeId): JsonResponse
    {
        $employee = Employee::with(['subordinates.designation', 'subordinates.department'])->findOrFail($employeeId);
        
        return response()->json([
            'success' => true,
            'data' => $employee->subordinates,
        ]);
    }
    
    /**
     * Update reporting structure
     */
    public function updateReporting(Request $request, int $employeeId): JsonResponse
    {
        $user = auth()->user();
        
        if (!$this->hasHierarchyAccess($user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $request->validate([
            'reporting_to' => ['nullable', 'exists:employees,id'],
            'position_level' => ['required', 'in:intern,staff,team_lead,manager,senior_manager,director,vp,c_level'],
            'hierarchy_level' => ['required', 'integer', 'min:1', 'max:10'],
        ]);
        
        $employee = Employee::findOrFail($employeeId);
        
        // Prevent circular reference (can't report to yourself)
        if ($request->reporting_to == $employee->id) {
            return response()->json(['message' => 'Employee cannot report to themselves'], 422);
        }
        
        $employee->update([
            'reporting_to' => $request->reporting_to,
            'position_level' => $request->position_level,
            'hierarchy_level' => $request->hierarchy_level,
        ]);
        
        // Update hierarchy path
        $employee->updateHierarchyPath();
        
        return response()->json([
            'success' => true,
            'message' => 'Reporting structure updated successfully',
            'data' => $employee->load('manager'),
        ]);
    }
    
    /**
     * Get potential managers (for dropdown)
     */
    public function potentialManagers(): JsonResponse
    {
        $user = auth()->user();
        
        if (!$this->hasHierarchyAccess($user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $managers = $this->hierarchyService->getPotentialManagers();
        
        return response()->json([
            'success' => true,
            'data' => $managers->map(fn($m) => [
                'id' => $m->id,
                'name' => $m->full_name,
                'employee_code' => $m->employee_code,
                'designation' => $m->designation?->name,
                'position_level' => $m->position_level,
            ])
        ]);
    }

    /**
     * Check if a user has administrative hierarchy access
     */
    private function hasHierarchyAccess($user): bool
    {
        if (!$user) {
            return false;
        }

        // 1. Check Spatie roles
        $privilegedRoles = ['super_admin', 'super admin', 'superadmin', 'admin', 'hr_admin', 'hr', 'hr_manager'];
        foreach ($privilegedRoles as $role) {
            if ($user->hasRole($role)) {
                return true;
            }
        }

        // 2. Check role column fallback
        if (in_array($user->role, $privilegedRoles)) {
            return true;
        }

        return false;
    }
}
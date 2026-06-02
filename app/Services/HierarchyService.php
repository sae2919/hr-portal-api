<?php

namespace App\Services;

use App\Models\Employee;
use Illuminate\Support\Collection;

class HierarchyService
{
    /**
     * Build complete organization tree
     */
    public function buildOrgTree(): array
    {
        // Load ALL employees with designation & department in one query
        $employees = Employee::with(['designation', 'department'])->get();

        // Find top-level employees (no manager)
        $topLevel = $employees->filter(fn($e) => !$e->reporting_to);

        $tree = [];
        foreach ($topLevel as $employee) {
            $tree[] = $this->buildNode($employee, $employees);
        }

        return $tree;
    }

    /**
     * Build a single node — uses already-loaded $allEmployees collection
     * so no extra queries fire per node.
     */
    private function buildNode(Employee $employee, Collection $allEmployees): array
    {
        $node = [
            'id'             => $employee->id,
            'name'           => $employee->full_name,
            'employee_code'  => $employee->employee_code,
            'designation'    => $employee->designation?->title,   // ✅ uses 'title' column
            'department'     => $employee->department?->name,
            'position_level' => $employee->position_level ?? 'staff',
            'position_label' => $employee->getPositionLabel(),
            'avatar'         => $employee->photo_url,
            'email'          => $employee->email,
            'phone'          => $employee->phone,
            'status'         => $employee->status,
            'team_size'      => $allEmployees->where('reporting_to', $employee->id)->count(),
            'children'       => [],
        ];

        // Filter children from the already-loaded collection (no DB hit)
        $subordinates = $allEmployees->where('reporting_to', $employee->id);

        foreach ($subordinates as $subordinate) {
            $node['children'][] = $this->buildNode($subordinate, $allEmployees);
        }

        return $node;
    }

    /**
     * Get team members for a manager (direct reports only)
     */
    public function getTeamMembers(int $managerId): Collection
    {
        return Employee::with(['designation', 'department'])
            ->where('reporting_to', $managerId)
            ->get()
            ->map(fn($e) => $this->formatTeamMember($e));
    }

    /**
     * Format a single team member for API response
     */
    private function formatTeamMember(Employee $employee): array
    {
        return [
            'id'             => $employee->id,
            'first_name'     => $employee->first_name,
            'last_name'      => $employee->last_name,
            'name'           => $employee->full_name,
            'employee_code'  => $employee->employee_code,
            'email'          => $employee->email,
            'phone'          => $employee->phone,
            'designation'    => $employee->designation?->title,   // ✅ uses 'title' column
            'department'     => $employee->department?->name,
            'joining_date'   => $employee->joining_date,
            'status'         => $employee->status,
            'position_level' => $employee->position_level ?? 'staff',
            'position_label' => $employee->getPositionLabel(),
            'avatar'         => $employee->photo_url,
        ];
    }

    /**
     * Get reporting chain for an employee (upward chain)
     */
    public function getReportingChain(int $employeeId): array
    {
        $employee = Employee::findOrFail($employeeId);
        $chain    = [];
        $visited  = [$employeeId]; // prevent infinite loops

        $current = $employee;
        while ($current->reporting_to) {
            $current = Employee::with(['designation'])->find($current->reporting_to);
            if (!$current || in_array($current->id, $visited)) {
                break;
            }
            $visited[] = $current->id;
            $chain[] = [
                'id'             => $current->id,
                'name'           => $current->full_name,
                'designation'    => $current->designation?->title,  // ✅ uses 'title' column
                'position_level' => $current->position_level ?? 'staff',
                'position_label' => $current->getPositionLabel(),
                'level'          => $current->hierarchy_level ?? 5,
            ];
        }

        return $chain;
    }

    /**
     * Get all employees that can be managers (for dropdown)
     */
    public function getPotentialManagers(): Collection
    {
        return Employee::with(['designation'])
            ->where(function ($q) {
                $q->whereIn('position_level', ['manager', 'senior_manager', 'director', 'vp', 'c_level'])
                  ->orWhere('id', 1);
            })
            ->orderBy('hierarchy_level')
            ->get();
    }

    /**
     * Get hierarchy statistics
     */
    public function getHierarchyStats(): array
    {
        return [
            'total_employees'  => Employee::count(),
            'total_managers'   => Employee::whereIn('position_level', ['manager', 'senior_manager', 'director', 'vp', 'c_level'])->count(),
            'total_team_leads' => Employee::where('position_level', 'team_lead')->count(),
            'avg_team_size'    => round(
                Employee::has('subordinates')->withCount('subordinates')->get()->avg('subordinates_count') ?? 0,
                1
            ),
            'orphan_employees' => Employee::whereNull('reporting_to')->count(),
        ];
    }
}
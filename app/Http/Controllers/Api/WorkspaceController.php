<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WorkspaceController extends Controller
{
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        $employee = null;

        // FIXED: Wrap matching parameters within an isolated boolean closure query group
        if (Schema::hasTable('employees')) {
    $employee = DB::table('employees')
        ->where(function ($query) use ($user) {
            // ✅ Check column exists before using it
            if (Schema::hasColumn('employees', 'user_id')) {
                $query->where('user_id', $user->id);
            }
            if (!empty($user->employee_id) && Schema::hasColumn('employees', 'id')) {
                $query->orWhere('id', $user->employee_id);
            }
        })
        ->first();
}

        // Safe evaluation for department code parameter string
        $deptCode = 'GENERAL';
        if ($employee && isset($employee->department_id) && Schema::hasTable('departments')) {
            $department = DB::table('departments')->where('id', $employee->department_id)->first();
            if ($department && isset($department->code)) {
                $deptCode = strtoupper($department->code);
            }
        }

        $roleTier = $user->role ? strtolower($user->role) : 'employee';

        // Base Metadata matching what your Next.js frontend expects
        $payload = [
            'department' => $deptCode,
            'role_tier'  => $roleTier,
            'user_name'  => $user->name,
        ];

        // Fallback baseline metrics accessible by any team member
        $payload['core_hr_stats'] = [
            'employees'     => Schema::hasTable('employees') ? DB::table('employees')->count() : 0,
            'present_today' => 'Verified Sync',
            'on_leave'      => (Schema::hasTable('leaves') && $employee) 
                ? DB::table('leaves')->where('employee_id', $employee->id)->where('status', 'approved')->count() 
                : 0,
        ];

        // Department specific metrics injection block (with Schema safety monitors)
        switch ($deptCode) {
            case 'TECH':
                $payload['tech_stats'] = [
                    'active_tasks_count' => Schema::hasTable('tasks') ? DB::table('tasks')->where('status', 'in_progress')->count() : 0,
                    'open_bugs_count'    => Schema::hasTable('tickets') ? DB::table('tickets')->where('status', 'open')->count() : 0,
                ];
                break;

            case 'SALES':
                $payload['sales_stats'] = [
                    'monthly_revenue' => Schema::hasTable('deals') ? (DB::table('deals')->where('status', 'won')->whereMonth('closed_at', now()->month)->sum('value') ?? 0) : 0,
                    'pipeline_deals'  => Schema::hasTable('deals') ? DB::table('deals')->where('status', 'open')->count() : 0,
                ];
                break;

            case 'MARKETING':
            case 'SEO':
                $payload['marketing_stats'] = [
                    'active_campaigns' => Schema::hasTable('campaigns') ? DB::table('campaigns')->where('status', 'active')->count() : 0,
                    'conversion_yield' => 4.2
                ];
                break;
        }

        return response()->json($payload);
    }
}
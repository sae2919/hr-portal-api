<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\Auth\ResetPasswordController;

use App\Http\Controllers\Api\RecruitmentController;
use App\Http\Controllers\Api\PayrollController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\WorkspaceController;
use App\Http\Controllers\Api\Department\DepartmentController;
use App\Http\Controllers\Api\Designation\DesignationController;
use App\Http\Controllers\Api\Employee\EmployeeController;
use App\Http\Controllers\Api\Attendance\AttendanceController;

use App\Http\Controllers\Api\Leave\LeaveTypeController;
use App\Http\Controllers\Api\Leave\LeaveController;
use App\Http\Controllers\Api\Leave\LeaveBalanceController;

Route::prefix('v1')->group(function () {

    // ─────────────────────────────────────────────
    // Public Auth Routes
    // ─────────────────────────────────────────────
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink']);
    Route::post('/reset-password', [ResetPasswordController::class, 'reset']);

    // ─────────────────────────────────────────────
    // Public/Custom Authenticated Routes
    // ─────────────────────────────────────────────
    // FIXED: Placed outside Sanctum middleware to support native query parameter token pass-throughs
    Route::get('/payrolls/{payroll}/payslip', [PayrollController::class, 'downloadPayslip']);
Route::put('/v1/profile', [ProfileController::class, 'update']);
Route::put('/v1/profile/password', [ProfileController::class, 'changePassword']);

    // ─────────────────────────────────────────────
    // Protected Routes (Requires Sanctum Authentication)
    // ─────────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        // ── Auth User Context Profile (FIX FOR NEXT.JS 404 ERROR) ──
        Route::get('/user', function (Illuminate\Http\Request $request) {
            return $request->user();
        });

        // ── Dashboard & Workspace Core Metrics ──
        Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
        Route::get('/workspace/stats', [WorkspaceController::class, 'stats']);

        // ── Recruitment Routes ──
        Route::get('/recruitment/dashboard', [RecruitmentController::class, 'dashboard']);
        Route::patch('/candidates/{candidateId}/status', [RecruitmentController::class, 'updateStatus']);

        // ── Structural Resource Routes ──
        Route::apiResource('departments', DepartmentController::class);
        Route::apiResource('designations', DesignationController::class);
        Route::apiResource('employees', EmployeeController::class);

        // ── Attendance Routes ──
        Route::get('attendance/worksheet', [AttendanceController::class, 'worksheet']); 
        Route::post('attendance/bulk-store', [AttendanceController::class, 'bulkStore']); 
        Route::post('attendance/checkin', [AttendanceController::class, 'checkIn']);
        Route::post('attendance/checkout', [AttendanceController::class, 'checkOut']);
        Route::get('attendance/report/monthly', [AttendanceController::class, 'monthlyReport']);
        Route::get('attendance/my-calendar', [AttendanceController::class, 'myCalendar']);
        Route::apiResource('attendance', AttendanceController::class);
        

        // ── Payroll & Request Management Routes ──
        Route::get('/payrolls', [PayrollController::class, 'index']);
        Route::post('/payrolls/{payroll}/mark-paid', [PayrollController::class, 'markPaid']);
        Route::post('/payrolls/{payroll}/send-email', [PayrollController::class, 'sendEmail']);
        Route::post('/payrolls/{payroll}/request-payslip', [PayrollController::class, 'requestPayslip']);
        
        // Registered Admin workflow routes for handling employee payslip requests
        Route::get('/payroll-requests', [PayrollController::class, 'indexRequests']);
        Route::patch('/payroll-requests/{id}/fulfill', [PayrollController::class, 'fulfillRequest']);

        // ── Leave Management Routes ──
        Route::patch('leaves/{leave}/approve', [LeaveController::class, 'approve']); 
        Route::patch('leaves/{leave}/reject',  [LeaveController::class, 'reject']);
        Route::apiResource('leave-types', LeaveTypeController::class);
        Route::apiResource('leave-balances', LeaveBalanceController::class);
        Route::apiResource('leaves', LeaveController::class);
        
    });
});
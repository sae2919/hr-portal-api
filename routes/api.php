<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\Auth\ResetPasswordController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // ── Public Auth Routes ────────────────────────────────────────
    Route::post('/login',          [AuthController::class, 'login']);
    Route::post('/forgot-password',[ForgotPasswordController::class, 'sendResetLink']);
    Route::post('/reset-password', [ResetPasswordController::class, 'reset']);
Route::apiResource('departments', \App\Http\Controllers\Api\Department\DepartmentController::class);
Route::apiResource('designations', \App\Http\Controllers\Api\Designation\DesignationController::class);
Route::apiResource('employees', \App\Http\Controllers\Api\Employee\EmployeeController::class);
// Attendance
Route::post('attendance/checkin',  [\App\Http\Controllers\Api\Attendance\AttendanceController::class, 'checkIn']);
Route::post('attendance/checkout', [\App\Http\Controllers\Api\Attendance\AttendanceController::class, 'checkOut']);
Route::get('attendance/report/monthly', [\App\Http\Controllers\Api\Attendance\AttendanceController::class, 'monthlyReport']);
Route::apiResource('attendance', \App\Http\Controllers\Api\Attendance\AttendanceController::class);
    // ── Protected Routes ──────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {
        // Leave Types
Route::apiResource('leave-types', \App\Http\Controllers\Api\Leave\LeaveTypeController::class);

// Leaves
Route::get('leaves',    [\App\Http\Controllers\Api\Leave\LeaveController::class, 'index']);
Route::post('leaves',   [\App\Http\Controllers\Api\Leave\LeaveController::class, 'store']);
Route::get('leaves/{leave}',    [\App\Http\Controllers\Api\Leave\LeaveController::class, 'show']);
Route::delete('leaves/{leave}', [\App\Http\Controllers\Api\Leave\LeaveController::class, 'destroy']);
Route::patch('leaves/{leave}/approve', [\App\Http\Controllers\Api\Leave\LeaveController::class, 'approve']);
Route::patch('leaves/{leave}/reject',  [\App\Http\Controllers\Api\Leave\LeaveController::class, 'reject']);

// Leave Balances
Route::get('leave-balances',         [\App\Http\Controllers\Api\Leave\LeaveBalanceController::class, 'index']);
Route::post('leave-balances/initialize', [\App\Http\Controllers\Api\Leave\LeaveBalanceController::class, 'initialize']);

        // Auth
        Route::get('/me',          [AuthController::class, 'me']);
        Route::post('/logout',     [AuthController::class, 'logout']);
        Route::post('/logout-all', [AuthController::class, 'logoutAll']);

        // All other module routes go here as we build them
    });
});
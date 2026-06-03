<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\Auth\ResetPasswordController;
use App\Http\Controllers\Api\RecruitmentController;
use App\Http\Controllers\Api\PayrollController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\WorkspaceController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\Department\DepartmentController;
use App\Http\Controllers\Api\Designation\DesignationController;
use App\Http\Controllers\Api\Employee\EmployeeController;
use App\Http\Controllers\Api\Attendance\AttendanceController;
use App\Http\Controllers\Api\Attendance\HolidayController;
use App\Http\Controllers\Api\Leave\LeaveTypeController;
use App\Http\Controllers\Api\Leave\LeaveController;
use App\Http\Controllers\Api\Leave\LeaveBalanceController;
use App\Http\Controllers\Api\HierarchyController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\EventWishController;
use App\Http\Controllers\Api\QuoteController;
use App\Http\Controllers\Api\SalaryRevisionController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OnboardingController;
use App\Http\Controllers\Api\OffboardingController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\AssetController;
use App\Http\Controllers\Api\OfferLetterController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\MailTemplateController;

// ============================================
// HEALTH CHECK ROUTE (NO AUTH REQUIRED)
// ============================================
Route::get('/health', function() {
    return response()->json([
        'status' => 'ok',
        'message' => 'API is working',
        'timestamp' => now()->toISOString(),
    ]);
});

// ============================================
// API V1 ROUTES
// ============================================
Route::prefix('v1')->group(function () {

    // ============================================
    // PUBLIC AUTH ROUTES
    // ============================================
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink']);
    Route::post('/reset-password', [ResetPasswordController::class, 'reset']);

    // ============================================
    // PUBLIC PAYSLIP DOWNLOAD (TOKEN AUTH VIA QUERY)
    // ============================================
    Route::get('/payrolls/{payroll}/payslip', [PayrollController::class, 'downloadPayslip']);

    // ============================================
    // PROTECTED ROUTES (REQUIRES AUTH)
    // ============================================
    Route::middleware('auth:sanctum')->group(function () {

        // ──────────────────────────────────────
        // USER AUTH
        // ──────────────────────────────────────
        Route::get('/user', function (Illuminate\Http\Request $request) {
            return $request->user();
        });
        Route::put('/profile', [ProfileController::class, 'update']);
        Route::put('/profile/password', [ProfileController::class, 'changePassword']);
        Route::post('/logout', [AuthController::class, 'logout']);

        // ──────────────────────────────────────
        // DASHBOARD & WORKSPACE
        // ──────────────────────────────────────
        Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
        Route::get('/workspace/stats', [WorkspaceController::class, 'stats']);
        Route::get('/notifications', [NotificationController::class, 'index']);

        // ──────────────────────────────────────
        // RECRUITMENT
        // ──────────────────────────────────────
        Route::get('/recruitment/dashboard', [RecruitmentController::class, 'dashboard']);
        Route::patch('/candidates/{candidateId}/status', [RecruitmentController::class, 'updateStatus']);

        // ──────────────────────────────────────
        // DEPARTMENTS & DESIGNATIONS
        // ──────────────────────────────────────
        Route::apiResource('departments', DepartmentController::class);
        Route::apiResource('designations', DesignationController::class);
        Route::apiResource('mail-templates', MailTemplateController::class);

        // ──────────────────────────────────────
        // EMPLOYEES
        // ──────────────────────────────────────
        Route::get('employees/managers', [EmployeeController::class, 'managers']);
        Route::apiResource('employees', EmployeeController::class);

        // ──────────────────────────────────────
        // ATTENDANCE
        // ──────────────────────────────────────
        Route::get('attendance/worksheet',      [AttendanceController::class, 'worksheet']);
        Route::post('attendance/bulk-store',    [AttendanceController::class, 'bulkStore']);
        Route::post('attendance/checkin',       [AttendanceController::class, 'checkIn']);
        Route::post('attendance/checkout',      [AttendanceController::class, 'checkOut']);
        Route::get('attendance/report/monthly', [AttendanceController::class, 'monthlyReport']);
        Route::get('attendance/my-calendar',    [AttendanceController::class, 'myCalendar']);
        Route::get('attendance/month-leaves',   [AttendanceController::class, 'monthLeaves']);
        Route::apiResource('attendance',        AttendanceController::class);

        // ──────────────────────────────────────
        // HOLIDAYS
        // ──────────────────────────────────────
        Route::apiResource('holidays', HolidayController::class);

        // ──────────────────────────────────────
        // PAYROLL ROUTES (MUST BE BEFORE WILDCARD)
        // ──────────────────────────────────────
        Route::get('/payrolls/summary',               [PayrollController::class, 'summary']);
        Route::post('/payrolls/bulk-mark-paid',       [PayrollController::class, 'bulkMarkPaid']);
        Route::post('/payrolls/bulk-send-email',      [PayrollController::class, 'bulkSendEmail']);
        Route::get('/payrolls/export/csv',            [PayrollController::class, 'exportCSV']);
        Route::get('/payrolls/department-breakdown',  [PayrollController::class, 'departmentBreakdown']);
        Route::post('/payrolls/generate',             [PayrollController::class, 'generate']);
        Route::get('/payrolls',                       [PayrollController::class, 'index']);
        Route::get('/payrolls/{payroll}/items',       [PayrollController::class, 'items']);
        Route::post('/payrolls/{payroll}/mark-paid',  [PayrollController::class, 'markPaid']);
        Route::post('/payrolls/{payroll}/send-email', [PayrollController::class, 'sendEmail']);
        Route::post('/payrolls/{payroll}/request-payslip', [PayrollController::class, 'requestPayslip']);

        // ──────────────────────────────────────
        // PAYROLL REQUESTS
        // ──────────────────────────────────────
        Route::get('/payroll-requests',                    [PayrollController::class, 'indexRequests']);
        Route::patch('/payroll-requests/{id}/fulfill',     [PayrollController::class, 'fulfillRequest']);

        // ──────────────────────────────────────
        // SALARY REVISIONS
        // ──────────────────────────────────────
        Route::prefix('salary-revisions')->group(function () {
            Route::get('/',                 [SalaryRevisionController::class, 'index']);
            Route::post('/',                [SalaryRevisionController::class, 'store']);
            Route::get('/{id}/download',    [SalaryRevisionController::class, 'download']);
        });

        // ──────────────────────────────────────
        // LEAVE MANAGEMENT
        // ──────────────────────────────────────
        Route::get('/my-leave-balances',                          [LeaveBalanceController::class, 'myBalances']);
        Route::get('/my-leaves',                                  [LeaveController::class, 'myLeaves']);
        Route::patch('leaves/{leave}/team-lead-approve',          [LeaveController::class, 'teamLeadApprove']);
        Route::patch('leaves/{leave}/team-lead-reject',           [LeaveController::class, 'teamLeadReject']);
        Route::patch('leaves/{leave}/approve',                    [LeaveController::class, 'approve']);
        Route::patch('leaves/{leave}/reject',                     [LeaveController::class, 'reject']);
        Route::apiResource('leave-types',    LeaveTypeController::class);
        Route::apiResource('leave-balances', LeaveBalanceController::class);
        Route::apiResource('leaves',         LeaveController::class);

        // ──────────────────────────────────────
        // HIERARCHY ROUTES
        // ──────────────────────────────────────
        Route::prefix('hierarchy')->group(function () {
            Route::get('/org-tree',                                    [HierarchyController::class, 'orgTree']);
            Route::get('/my-team',                                     [HierarchyController::class, 'myTeam']);
            Route::get('/my-chain',                                    [HierarchyController::class, 'myReportingChain']);
            Route::get('/employees/{employee}/direct-reports',         [HierarchyController::class, 'directReports']);
            Route::put('/employees/{employee}/reporting',              [HierarchyController::class, 'updateReporting']);
            Route::get('/potential-managers',                          [HierarchyController::class, 'potentialManagers']);
        });

        // ──────────────────────────────────────
        // EVENT ROUTES
        // ──────────────────────────────────────
        Route::prefix('events')->group(function () {
            // Core event CRUD
            Route::get('/',                      [EventController::class, 'index']);
            Route::get('/upcoming',              [EventController::class, 'upcoming']);
            Route::get('/today-special',         [EventController::class, 'todaySpecial']);
            Route::get('/upcoming-birthdays',    [EventController::class, 'upcomingBirthdays']);
            Route::post('/',                     [EventController::class, 'store']);
            Route::put('/{event}',               [EventController::class, 'update']);
            Route::delete('/{event}',            [EventController::class, 'destroy']);

            // ── Wishes / Comments ──────────────────────────────────────────
            // GET    /api/v1/events/wishes/{employeeId}?wish_type=birthday|anniversary
            // POST   /api/v1/events/wishes
            // DELETE /api/v1/events/wishes/{wishId}
            Route::get('/wishes/{employeeId}',   [EventWishController::class, 'index']);
            Route::post('/wishes',               [EventWishController::class, 'store']);
            Route::delete('/wishes/{wishId}',    [EventWishController::class, 'destroy']);
        });

        // ──────────────────────────────────────
        // QUOTE ROUTES
        // ──────────────────────────────────────
        Route::prefix('quotes')->group(function () {
            Route::get('/',               [QuoteController::class, 'index']);
            Route::get('/random',         [QuoteController::class, 'random']);
            Route::get('/daily',          [QuoteController::class, 'quoteOfTheDay']);
            Route::post('/',              [QuoteController::class, 'store']);
            Route::put('/{quote}',        [QuoteController::class, 'update']);
            Route::delete('/{quote}',     [QuoteController::class, 'destroy']);
            Route::patch('/{quote}/toggle', [QuoteController::class, 'toggleStatus']);
        });

        // ──────────────────────────────────────
        // ONBOARDING ROUTES
        // ──────────────────────────────────────
        Route::prefix('onboarding')->group(function () {
            Route::get('/',                                            [OnboardingController::class, 'index']);
            Route::post('/',                                           [OnboardingController::class, 'store']);
            Route::get('/{onboardingRequest}',                         [OnboardingController::class, 'show']);
            Route::put('/{onboardingRequest}',                         [OnboardingController::class, 'update']);
            Route::post('/{onboardingRequest}/approve',                [OnboardingController::class, 'approve']);
            Route::post('/{onboardingRequest}/reject',                 [OnboardingController::class, 'reject']);
            Route::post('/{onboardingRequest}/complete',               [OnboardingController::class, 'complete']);
            Route::delete('/{onboardingRequest}',                      [OnboardingController::class, 'destroy']);
            Route::post('/{onboardingRequest}/documents',              [DocumentController::class, 'upload']);
            Route::patch('/documents/{document}/verify',               [DocumentController::class, 'verify']);
            Route::delete('/documents/{document}',                     [DocumentController::class, 'destroy']);
            Route::get('/documents/{document}/download',               [DocumentController::class, 'download']);
            Route::post('/{onboardingRequest}/offer-letter',           [OfferLetterController::class, 'generate']);
            Route::post('/offer-letters/{offerLetter}/send',           [OfferLetterController::class, 'send']);
            Route::get('/offer-letters/{offerLetter}/download',        [OfferLetterController::class, 'download']);
        });

        // ──────────────────────────────────────
        // OFFBOARDING ROUTES
        // ──────────────────────────────────────
        Route::prefix('offboarding')->group(function () {
            Route::get('/',                 [OffboardingController::class, 'index']);
            Route::post('/',                [OffboardingController::class, 'store']);
            Route::post('/{id}/approve',    [OffboardingController::class, 'approve']);
            Route::post('/{id}/reject',     [OffboardingController::class, 'reject']);
            Route::post('/{id}/complete',   [OffboardingController::class, 'complete']);
            Route::get('/{id}/download',    [OffboardingController::class, 'download']);
            Route::patch('/{id}/tasks',     [OffboardingController::class, 'updateTasks']);
        });

        // ──────────────────────────────────────
        // ASSET ROUTES
        // ──────────────────────────────────────
        Route::prefix('assets')->group(function () {
            Route::get('/',                                            [AssetController::class, 'index']);
            Route::get('/available',                                   [AssetController::class, 'available']);
            Route::post('/',                                           [AssetController::class, 'store']);
            Route::get('/{asset}',                                     [AssetController::class, 'show']);
            Route::put('/{asset}',                                     [AssetController::class, 'update']);
            Route::post('/{asset}/allocate',                           [AssetController::class, 'allocate']);
            Route::post('/allocations/{allocation}/return',            [AssetController::class, 'returnAsset']);
            Route::delete('/{asset}',                                  [AssetController::class, 'destroy']);
        });

        // ──────────────────────────────────────
        // COMPANY SETTINGS
        // ──────────────────────────────────────
        Route::get('/settings',  [SettingsController::class, 'index']);
        Route::put('/settings',  [SettingsController::class, 'update']);

    });
    
});
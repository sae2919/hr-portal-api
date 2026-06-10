<?php

use App\Models\Employee;
use App\Models\SalaryRevision;
use App\Models\OnboardingRequest;
use App\Models\OfferLetter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

// Boot Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Fake the queue so that base64 PDF mail attachments don't hit the local database queue_jobs table
\Illuminate\Support\Facades\Queue::fake();

// Log in as super admin or user ID 1
$admin = \App\Models\User::whereIn('role', ['admin', 'super_admin', 'super admin'])->first();
if ($admin) {
    auth()->login($admin);
    echo "Logged in as Admin: {$admin->name}\n";
} else {
    echo "No admin user found to log in.\n";
    exit(1);
}

// ----------------------------------------------------
// TEST 1: Free Intern -> Paid Intern
// Ryagati Venkatesh (ID 16, basic_salary = 0.00, employment_type = 'intern')
// ----------------------------------------------------
echo "\n--- TEST 1: Free Intern -> Paid Intern ---\n";
$employee16 = Employee::find(16);
if ($employee16) {
    // Reset status to intern with 0 salary for testing
    $employee16->update([
        'employment_type' => 'intern',
        'basic_salary' => 0,
        'hra' => 0,
        'bonus' => 0,
    ]);
    
    // Clear any previous offer letters forRyagati Venkatesh to make testing clean
    $onb = OnboardingRequest::where('email', $employee16->email)->first();
    if ($onb) {
        OfferLetter::where('onboarding_request_id', $onb->id)->delete();
    }

    echo "Initial Employee 16: {$employee16->full_name}, type: {$employee16->employment_type}, salary: {$employee16->basic_salary}\n";

    // Simulate POST /api/v1/salary-revisions
    $request = new \Illuminate\Http\Request();
    $request->replace([
        'employee_id' => 16,
        'new_basic_salary' => 15000,
        'new_hra' => 3000,
        'new_allowances' => 2000,
        'new_bonus' => 0,
        'effective_date' => Carbon::now()->addDays(5)->toDateString(),
        'reason' => 'Promotion to Paid Intern',
        'new_employment_type' => 'intern',
    ]);

    $controller = new \App\Http\Controllers\Api\SalaryRevisionController();
    $response = $controller->store($request);
    
    echo "Controller Response Code: " . $response->getStatusCode() . "\n";
    
    // Verify offer letter generated
    $onbAfter = OnboardingRequest::where('email', $employee16->email)->first();
    if ($onbAfter) {
        $offerLetters = OfferLetter::where('onboarding_request_id', $onbAfter->id)->get();
        echo "Offer Letters Found for Employee 16: " . $offerLetters->count() . "\n";
        foreach ($offerLetters as $ol) {
            echo "  OL ID: {$ol->id}, Path: {$ol->file_path}, Status: {$ol->status}, Date: {$ol->letter_date}\n";
            $absolutePath = storage_path("app/public/{$ol->file_path}");
            echo "  File Exists: " . (file_exists($absolutePath) ? "YES" : "NO") . "\n";
        }
    } else {
        echo "Onboarding Request not found after revision.\n";
    }
} else {
    echo "Employee 16 not found.\n";
}

// ----------------------------------------------------
// TEST 2: Paid Intern -> Full-Time Employee
// Ryagati Venkatesh is now a Paid Intern (basic_salary = 15000.00)
// Let's transition him to Full-Time Employee
// ----------------------------------------------------
echo "\n--- TEST 2: Paid Intern -> Full-Time Employee ---\n";
if ($employee16) {
    // Reload employee state
    $employee16 = Employee::find(16);
    echo "Before Test 2 Employee 16: {$employee16->full_name}, type: {$employee16->employment_type}, salary: {$employee16->basic_salary}\n";

    // Simulate POST /api/v1/salary-revisions to Full-Time
    $request = new \Illuminate\Http\Request();
    $request->replace([
        'employee_id' => 16,
        'new_basic_salary' => 25000,
        'new_hra' => 5000,
        'new_allowances' => 3000,
        'new_bonus' => 1000,
        'effective_date' => Carbon::now()->addDays(10)->toDateString(),
        'reason' => 'Conversion to Full Time',
        'new_employment_type' => 'full_time',
    ]);

    $controller = new \App\Http\Controllers\Api\SalaryRevisionController();
    $response = $controller->store($request);
    
    echo "Controller Response Code: " . $response->getStatusCode() . "\n";
    
    // Verify offer letter generated
    $onbAfter = OnboardingRequest::where('email', $employee16->email)->first();
    if ($onbAfter) {
        $offerLetters = OfferLetter::where('onboarding_request_id', $onbAfter->id)->get();
        echo "Offer Letters Found for Employee 16: " . $offerLetters->count() . "\n";
        foreach ($offerLetters as $ol) {
            echo "  OL ID: {$ol->id}, Path: {$ol->file_path}, Status: {$ol->status}, Date: {$ol->letter_date}\n";
            $absolutePath = storage_path("app/public/{$ol->file_path}");
            echo "  File Exists: " . (file_exists($absolutePath) ? "YES" : "NO") . "\n";
        }
    } else {
        echo "Onboarding Request not found after revision.\n";
    }
}

// ----------------------------------------------------
// TEST 3: Full-Time -> Full-Time Employee
// Ryagati Venkatesh is now a Full-Time Employee
// Let's revise his salary but keep him as Full-Time
// ----------------------------------------------------
echo "\n--- TEST 3: Full-Time -> Full-Time Employee ---\n";
if ($employee16) {
    // Reload employee state
    $employee16 = Employee::find(16);
    echo "Before Test 3 Employee 16: {$employee16->full_name}, type: {$employee16->employment_type}, salary: {$employee16->basic_salary}\n";

    // Simulate POST /api/v1/salary-revisions (simple hike)
    $request = new \Illuminate\Http\Request();
    $request->replace([
        'employee_id' => 16,
        'new_basic_salary' => 30000,
        'new_hra' => 6000,
        'new_allowances' => 4000,
        'new_bonus' => 1500,
        'effective_date' => Carbon::now()->addDays(15)->toDateString(),
        'reason' => 'Annual Salary Hike',
        'new_employment_type' => 'full_time',
    ]);

    $controller = new \App\Http\Controllers\Api\SalaryRevisionController();
    $response = $controller->store($request);
    
    echo "Controller Response Code: " . $response->getStatusCode() . "\n";
    
    // Verify offer letter NOT generated (should still remain at 2 total offer letters from previous tests)
    $onbAfter = OnboardingRequest::where('email', $employee16->email)->first();
    if ($onbAfter) {
        $offerLetters = OfferLetter::where('onboarding_request_id', $onbAfter->id)->get();
        echo "Offer Letters Found for Employee 16: " . $offerLetters->count() . "\n";
    } else {
        echo "Onboarding Request not found after revision.\n";
    }
}


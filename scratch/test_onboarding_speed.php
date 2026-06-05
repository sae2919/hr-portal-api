<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Http\Controllers\Api\OnboardingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// 1. Get Admin User
$admin = User::where('email', 'admin@hrportal.com')->first();
if (!$admin) {
    echo "Admin user not found!\n";
    exit(1);
}
auth()->login($admin);

// Generate random unique email
$email = "test_" . uniqid() . "@example.com";

echo "Creating onboarding request for {$email}...\n";

// 2. Measure store method execution time
$startTime = microtime(true);

$request = Request::create('/api/v1/onboarding', 'POST', [
    'candidate_name' => 'Speed Test Candidate',
    'email' => $email,
    'phone' => '1234567890',
    'position' => 'Software Engineer',
    'department' => 'Engineering',
    'joining_date' => '2026-07-01',
    'ctc' => 800000
]);

$controller = app(OnboardingController::class);
$response = $controller->store($request);

$endTime = microtime(true);
$durationMs = ($endTime - $startTime) * 1000;

echo "Response Status (Expected 201): " . $response->getStatusCode() . "\n";
echo "Execution Time: " . round($durationMs, 2) . " ms\n";

// Verify that a job was queued
$jobCount = DB::table('queue_jobs')->count();
echo "Total Jobs in Queue: " . $jobCount . "\n";

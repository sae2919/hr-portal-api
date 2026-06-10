<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\OnboardingRequest;

$candidate = OnboardingRequest::where('candidate_name', 'like', '%Ryagati%')
    ->orWhere('candidate_name', 'like', '%Venkatesh%')
    ->first();

if ($candidate) {
    echo "Found Candidate ID: {$candidate->id}\n";
    echo "Name: {$candidate->candidate_name}\n";
    echo "Position: {$candidate->position}\n";
    echo "Onboarding Type: {$candidate->onboarding_type}\n";
    echo "Stipend/CTC: {$candidate->ctc}\n";
    echo "Joining Date: {$candidate->joining_date}\n";
    echo "Duration: {$candidate->duration}\n";
} else {
    echo "Candidate not found.\n";
}

<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\OnboardingRequest;
use App\Models\OfferLetter;

$candidates = OnboardingRequest::where('candidate_name', 'like', '%Venkatesh%')->get();
echo "Candidates found: " . count($candidates) . "\n";
foreach ($candidates as $c) {
    echo "ID: {$c->id} | Name: {$c->candidate_name} | Type: {$c->onboarding_type} | CTC: {$c->ctc}\n";
    $letters = OfferLetter::where('onboarding_request_id', $c->id)->get();
    echo "  Offer Letters: " . count($letters) . "\n";
    foreach ($letters as $l) {
        echo "    ID: {$l->id} | Date: {$l->letter_date} | File: {$l->file_path} | Status: {$l->status}\n";
    }
}

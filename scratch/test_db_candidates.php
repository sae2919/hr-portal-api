<?php

// Require autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\OnboardingRequest;

$candidates = OnboardingRequest::all();
echo "Total candidates in DB: " . $candidates->count() . "\n";
foreach ($candidates as $c) {
    echo "ID: {$c->id} | Name: {$c->candidate_name} | Token: {$c->access_token} | Expires: {$c->link_expires_at}\n";
}

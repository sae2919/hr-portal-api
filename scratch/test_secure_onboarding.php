<?php

// Require autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\OnboardingRequest;

$candidate = OnboardingRequest::find(7);
if (!$candidate) {
    echo "Candidate 7 not found in database!\n";
    exit(1);
}

$originalExpires = $candidate->link_expires_at;
$token = $candidate->access_token;
echo "Candidate 7 Token: {$token} | Current Expiry: {$originalExpires}\n";

// 1. Test GET request when valid
echo "1. Requesting using valid token...\n";
$ch = curl_init("http://127.0.0.1:8000/api/v1/public/onboarding/{$token}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code (Expected 200): {$httpCode}\n";
$resObj = json_decode($response, true);
echo "Response Success (Expected true): " . ($resObj['success'] ? 'true' : 'false') . "\n";

// 2. Set expiration date in the past
echo "2. Setting link_expires_at to past (expired status)...\n";
$candidate->link_expires_at = now()->subDays(5);
$candidate->save();

// Request again
echo "Requesting using expired token...\n";
$ch = curl_init("http://127.0.0.1:8000/api/v1/public/onboarding/{$token}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code (Expected 403): {$httpCode}\n";
$resObj = json_decode($response, true);
echo "Response Message: " . ($resObj['message'] ?? 'None') . "\n";

// Restore original expiration
$candidate->link_expires_at = $originalExpires;
$candidate->save();
echo "Restored original expiration date.\n";

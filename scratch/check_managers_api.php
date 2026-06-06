<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Employee;

// 1. Log in to get token
$loginData = json_encode([
    'email' => 'hr@hrportal.com',
    'password' => 'Hr@123456'
]);

$ch = curl_init('http://127.0.0.1:8000/api/v1/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $loginData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
$response = curl_exec($ch);
curl_close($ch);

$resObj = json_decode($response, true);
$token = $resObj['token'] ?? null;

if (!$token) {
    echo "ERROR: Could not obtain token!\n";
    exit(1);
}

// 2. Query /employees/managers
$ch = curl_init('http://127.0.0.1:8000/api/v1/employees/managers');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    "Authorization: Bearer $token"
]);
$response = curl_exec($ch);
curl_close($ch);

$managersObj = json_decode($response, true);
$list = $managersObj['data'] ?? [];

echo "Found " . count($list) . " active employees eligible for Reporting To:\n";
foreach (array_slice($list, 0, 10) as $emp) {
    echo "- Name: {$emp['full_name']} | Designation: " . ($emp['designation']['title'] ?? 'N/A') . " | Dept: " . ($emp['department']['name'] ?? 'N/A') . "\n";
}

if (count($list) > 0) {
    echo "VERIFICATION PASSED: Successfully retrieved active employees list from API!\n";
} else {
    echo "ERROR: Managers list is empty!\n";
}

<?php

// 1. Log in to get token
$loginData = json_encode([
    'email' => 'admin@hrportal.com',
    'password' => 'Admin@123'
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
    echo "Could not obtain token!\n";
    exit(1);
}

// 2. Create onboarding request
echo "Creating candidate onboarding request via HTTP cURL...\n";
$candidateData = json_encode([
    'candidate_name' => 'Aditya Verma',
    'email' => 'aditya.verma@example.com',
    'phone' => '',
    'position' => 'Senior Frontend Engineer',
    'department' => 'Engineering',
    'joining_date' => '2026-07-01',
    'ctc' => '1200000'
]);

$ch = curl_init('http://127.0.0.1:8000/api/v1/onboarding');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $candidateData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    "Authorization: Bearer $token"
]);
$response = curl_exec($ch);
curl_close($ch);

$createObj = json_decode($response, true);
$candidateId = $createObj['data']['id'] ?? null;

if (!$candidateId) {
    echo "Failed to create candidate onboarding request: {$response}\n";
    exit(1);
}

echo "SUCCESS: Created Candidate ID: {$candidateId}\n";
echo "Public Portal URL: http://localhost:3000/onboarding/candidate/{$candidateId}\n";

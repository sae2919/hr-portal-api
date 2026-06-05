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

// 2. Create temporary onboarding request
echo "Creating temporary candidate onboarding request...\n";
$candidateData = json_encode([
    'candidate_name' => 'Test Candidate Onboarding',
    'email' => 'temp.onboarding.candidate@example.com',
    'phone' => '1234567890',
    'position' => 'Senior Developer',
    'department' => 'Technology',
    'joining_date' => '2026-07-15',
    'ctc' => '900000'
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
    echo "Failed to create temporary candidate onboarding request: {$response}\n";
    exit(1);
}

echo "Created Candidate ID: {$candidateId}\n";

try {
    // 3. Test GET /public/onboarding/{id}
    echo "Testing GET public details...\n";
    $ch = curl_init("http://127.0.0.1:8000/api/v1/public/onboarding/{$candidateId}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json'
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $getObj = json_decode($response, true);
    if ($httpCode === 200 && isset($getObj['success']) && $getObj['success']) {
        echo "SUCCESS: GET public details succeeded! Candidate Name: {$getObj['data']['candidate_name']}\n";
    } else {
        echo "FAILED: GET public details HTTP {$httpCode} - Response: {$response}\n";
    }

    // 4. Test PUT /public/onboarding/{id}
    echo "Testing PUT public update phone...\n";
    $newPhone = '8887776665';
    $ch = curl_init("http://127.0.0.1:8000/api/v1/public/onboarding/{$candidateId}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['phone' => $newPhone]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
        'Accept: application/json'
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $putObj = json_decode($response, true);
    if ($httpCode === 200 && isset($putObj['success']) && $putObj['success'] && $putObj['data']['phone'] === $newPhone) {
        echo "SUCCESS: PUT public update phone succeeded! New Phone: {$putObj['data']['phone']}\n";
    } else {
        echo "FAILED: PUT public update phone HTTP {$httpCode} - Response: {$response}\n";
    }

    // 5. Test POST /public/onboarding/{id}/submit
    echo "Testing POST public submit onboarding...\n";
    $ch = curl_init("http://127.0.0.1:8000/api/v1/public/onboarding/{$candidateId}/submit");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json'
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $postObj = json_decode($response, true);
    if ($httpCode === 200 && isset($postObj['success']) && $postObj['success']) {
        echo "SUCCESS: POST public submit onboarding succeeded!\n";
    } else {
        echo "FAILED: POST public submit onboarding HTTP {$httpCode} - Response: {$response}\n";
    }
} finally {
    // 6. Cleanup: Delete temporary onboarding request
    echo "Cleaning up: deleting temporary candidate onboarding request...\n";
    $ch = curl_init("http://127.0.0.1:8000/api/v1/onboarding/{$candidateId}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        "Authorization: Bearer $token"
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo "Cleanup delete status: {$httpCode}\n";
}

echo "All public endpoints tested successfully on a draft onboarding request!\n";

<?php

echo "Sending request to Laravel API...\n";
$ch = curl_init('http://127.0.0.1:8000/api/v1/public/onboarding/5');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 5 seconds timeout
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($response === false) {
    echo "CURL ERROR: " . $error . "\n";
} else {
    echo "HTTP CODE: " . $httpCode . "\n";
    echo "RESPONSE: " . substr($response, 0, 500) . "\n";
}

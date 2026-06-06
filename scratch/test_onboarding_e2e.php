<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\OnboardingRequest;
use App\Models\Employee;
use App\Models\User;

// Clean up existing test candidate if any
OnboardingRequest::where('email', 'janedoe@testexample.com')->delete();
Employee::where('email', 'janedoe@testexample.com')->delete();
User::where('email', 'janedoe@testexample.com')->delete();

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
echo "LOGGED IN: Got admin/HR token successfully.\n";

// 2. Create onboarding request with custom heading and documents
$candidateData = json_encode([
    'candidate_name' => 'Jane Doe Test',
    'email' => 'janedoe@testexample.com',
    'phone' => '1234567890',
    'position' => 'Software Engineering Intern',
    'department' => 'Engineering',
    'joining_date' => '2026-07-01',
    'ctc' => '50000',
    'onboarding_type' => 'intern',
    'custom_heading' => 'Welcome to Techsprout, Jane!',
    'required_documents' => ['resume', 'id_proof', 'address_proof', 'degree', 'bank_details', 'pan_card', 'aadhaar_card', 'custom_noc_cert'],
    'optional_documents' => ['passport'],
    'custom_document_labels' => ['custom_noc_cert' => 'NOC Certificate']
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
if (!isset($createObj['success']) || !$createObj['success']) {
    echo "ERROR: Failed to create onboarding request: {$response}\n";
    exit(1);
}

$reqData = $createObj['data'];
$candidateId = $reqData['id'];
$accessToken = $reqData['access_token'];

echo "SUCCESS: Onboarding Request Created (ID: $candidateId, Token: $accessToken)\n";
echo "onboarding_type: " . $reqData['onboarding_type'] . "\n";
echo "custom_heading: " . $reqData['custom_heading'] . "\n";
echo "required_documents: " . json_encode($reqData['required_documents']) . "\n";
echo "optional_documents: " . json_encode($reqData['optional_documents']) . "\n";
echo "custom_document_labels: " . json_encode($reqData['custom_document_labels']) . "\n";

// 3. Test Public GET Details Endpoint
$ch = curl_init("http://127.0.0.1:8000/api/v1/public/onboarding/{$accessToken}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json'
]);
$response = curl_exec($ch);
curl_close($ch);

$publicObj = json_decode($response, true);
if (!isset($publicObj['success']) || !$publicObj['success']) {
    echo "ERROR: Public show API failed: {$response}\n";
    exit(1);
}
echo "SUCCESS: Public show API returned data.\n";

// Create temp files to upload
$tempFileResume = tempnam(sys_get_temp_dir(), 'resume') . '.pdf';
file_put_contents($tempFileResume, '%PDF-1.4 Mock Resume File Contents');

$tempFileNoc = tempnam(sys_get_temp_dir(), 'noc') . '.pdf';
file_put_contents($tempFileNoc, '%PDF-1.4 Mock NOC File Contents');

// 4. Upload resume (standard required doc)
$cFileResume = new CURLFile($tempFileResume, 'application/pdf', 'resume_test.pdf');
$uploadData = [
    'document' => $cFileResume,
    'document_type' => 'resume'
];
$ch = curl_init("http://127.0.0.1:8000/api/v1/public/onboarding/{$accessToken}/documents");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $uploadData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json'
]);
$response = curl_exec($ch);
curl_close($ch);

$uploadObj = json_decode($response, true);
if (!isset($uploadObj['success']) || !$uploadObj['success']) {
    echo "ERROR: Failed to upload Resume: {$response}\n";
    unlink($tempFileResume);
    unlink($tempFileNoc);
    exit(1);
}
echo "SUCCESS: Uploaded Resume.\n";

// 5. Upload custom_noc_cert (custom required doc)
$cFileNoc = new CURLFile($tempFileNoc, 'application/pdf', 'noc_test.pdf');
$uploadData = [
    'document' => $cFileNoc,
    'document_type' => 'custom_noc_cert'
];
$ch = curl_init("http://127.0.0.1:8000/api/v1/public/onboarding/{$accessToken}/documents");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $uploadData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json'
]);
$response = curl_exec($ch);
curl_close($ch);

$uploadObj = json_decode($response, true);
if (!isset($uploadObj['success']) || !$uploadObj['success']) {
    echo "ERROR: Failed to upload custom document (custom_noc_cert): {$response}\n";
    unlink($tempFileResume);
    unlink($tempFileNoc);
    exit(1);
}
echo "SUCCESS: Uploaded Custom Document (custom_noc_cert).\n";

// Clean up temp files
unlink($tempFileResume);
unlink($tempFileNoc);

// 6. Submit Public Details (Form Submission)
$submitData = json_encode([
    'phone' => '1234567890',
    'dob' => '2000-01-01',
    'gender' => 'female',
    'address' => '123 Test Street, New Delhi',
    'bank_name' => 'State Bank of India',
    'bank_account_number' => '9876543210',
    'bank_ifsc' => 'SBIN0001234',
    'bank_branch' => 'Connaught Place',
    'pan_number' => 'ABCDE1234F',
    'aadhaar_number' => '123456789012'
]);

$ch = curl_init("http://127.0.0.1:8000/api/v1/public/onboarding/{$accessToken}/submit");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $submitData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
$response = curl_exec($ch);
curl_close($ch);

$submitObj = json_decode($response, true);
if (!isset($submitObj['success']) || !$submitObj['success']) {
    echo "ERROR: Public submit failed: {$response}\n";
    exit(1);
}
echo "SUCCESS: Public details submitted.\n";

// 7. Approve Onboarding Request (Admin)
$ch = curl_init("http://127.0.0.1:8000/api/v1/onboarding/{$candidateId}/approve");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    "Authorization: Bearer $token"
]);
$response = curl_exec($ch);
curl_close($ch);

$approveObj = json_decode($response, true);
if (!isset($approveObj['success']) || !$approveObj['success']) {
    echo "ERROR: Admin approval failed: {$response}\n";
    exit(1);
}
echo "SUCCESS: Onboarding Request Approved.\n";

// 8. Complete Onboarding Request (Admin) - Creates Employee
$ch = curl_init("http://127.0.0.1:8000/api/v1/onboarding/{$candidateId}/complete");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    "Authorization: Bearer $token"
]);
$response = curl_exec($ch);
curl_close($ch);

$completeObj = json_decode($response, true);
if (!isset($completeObj['success']) || !$completeObj['success']) {
    echo "ERROR: Admin complete failed: {$response}\n";
    exit(1);
}
echo "SUCCESS: Onboarding Request Completed.\n";

// 9. Verify resulting Employee employment_type
$employee = Employee::where('email', 'janedoe@testexample.com')->first();
if (!$employee) {
    echo "ERROR: Employee record was not created!\n";
    exit(1);
}

echo "SUCCESS: Employee record created (ID: {$employee->id})\n";
echo "employee employment_type: {$employee->employment_type}\n";

if ($employee->employment_type === 'intern') {
    echo "VERIFICATION PASSED: Onboarding type 'intern' successfully mapped to employee employment_type 'intern'!\n";
} else {
    echo "ERROR: employment_type mapping failed! Expected 'intern', got '{$employee->employment_type}'\n";
    exit(1);
}

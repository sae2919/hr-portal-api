<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\MailTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

echo "=== STARTING MAIL TEMPLATES API FLOW VERIFICATION ===\n\n";

// 1. Authenticate as Admin
$admin = User::where('email', 'admin@hrportal.com')->first();
if (!$admin) {
    echo "ERROR: Admin user not found. Seeding first...\n";
    $seeder = new \Database\Seeders\AdminUserSeeder();
    $seeder->run();
    $admin = User::where('email', 'admin@hrportal.com')->first();
}

$token = $admin->createToken('test_admin_token')->plainTextToken;
echo "1. Authenticated as Admin (token generated: $token)\n";

// Helper function to send requests
$sendRequest = function($uri, $method = 'GET', $parameters = [], $files = []) use ($app, $token) {
    $request = Request::create($uri, $method, $parameters, [], $files, [
        'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        'HTTP_ACCEPT' => 'application/json',
    ]);
    
    // Set content type for PUT/POST JSON if no files
    if (in_array($method, ['POST', 'PUT', 'PATCH']) && empty($files)) {
        $request->headers->set('Content-Type', 'application/json');
        $request->setJson(new \Symfony\Component\HttpFoundation\ParameterBag($parameters));
    }
    
    return $app->handle($request);
};

// 2. Fetch all templates
$response = $sendRequest('/api/v1/mail-templates');
echo "2. GET /api/v1/mail-templates returned status " . $response->getStatusCode() . "\n";
$templates = json_decode($response->getContent(), true);

$freeTmpl = null;
foreach ($templates as $t) {
    if ($t['template_name'] === 'free_internship_offer_letter') {
        $freeTmpl = $t;
    }
}

if ($freeTmpl) {
    echo "SUCCESS: Found template 'free_internship_offer_letter'\n";
    echo "  - pdf_path: " . ($freeTmpl['pdf_path'] ?? 'NULL') . "\n";
    echo "  - pdf_fields: " . (isset($freeTmpl['pdf_fields']) ? json_encode($freeTmpl['pdf_fields']) : 'NULL') . "\n";
} else {
    echo "FAILED: Template 'free_internship_offer_letter' not found in response.\n";
}

// 3. Test Preview PDF endpoint
if ($freeTmpl) {
    $previewUrl = "/api/v1/mail-templates/{$freeTmpl['id']}/preview-pdf";
    $response = $sendRequest($previewUrl);
    echo "3. GET {$previewUrl} returned status " . $response->getStatusCode() . "\n";
    echo "  - Content-Type: " . $response->headers->get('Content-Type') . "\n";
    $pdfLength = strlen($response->getContent());
    echo "  - Rendered PDF size: {$pdfLength} bytes\n";
    if ($response->getStatusCode() === 200 && $response->headers->get('Content-Type') === 'application/pdf' && $pdfLength > 1000) {
        echo "SUCCESS: Dynamic PDF preview rendered perfectly!\n";
    } else {
        echo "FAILED: PDF preview is invalid.\n";
    }
}

// 4. Test storing a new template via API (with PDF file upload)
echo "\n4. Creating temporary PDF file to upload...\n";
$tmpFilePath = tempnam(sys_get_temp_dir(), 'test_upload_') . '.pdf';
// Copy the free_internship_offer_letter.pdf to tmp file
copy(__DIR__ . '/free_internship_offer_letter.pdf', $tmpFilePath);

$uploadedFile = new UploadedFile(
    $tmpFilePath,
    'dummy_offer_letter_template.pdf',
    'application/pdf',
    null,
    true
);

$fieldsConfig = [
    [
        'variable' => '{{candidate_name}}',
        'page' => 1,
        'x' => 50,
        'y' => 50,
        'width' => 100,
        'height' => 10,
        'font_size' => 12,
        'font_style' => 'B',
        'color' => '#0000FF',
        'align' => 'L',
        'mask' => true
    ]
];

$storePayload = [
    'template_name' => 'api_temp_test_pdf_template',
    'subject' => 'Temporary API Test Template',
    'type' => 'offer_joining',
    'pdf_fields' => json_encode($fieldsConfig), // React client sends json_encoded string or array
];

$response = $sendRequest('/api/v1/mail-templates', 'POST', $storePayload, ['pdf_file' => $uploadedFile]);
echo "POST /api/v1/mail-templates returned status " . $response->getStatusCode() . "\n";
$createdData = json_decode($response->getContent(), true);

if ($response->getStatusCode() === 201 && isset($createdData['data']['pdf_path'])) {
    $newTemplateId = $createdData['data']['id'];
    $savedPdfPath = $createdData['data']['pdf_path'];
    echo "SUCCESS: Temporary template created!\n";
    echo "  - ID: {$newTemplateId}\n";
    echo "  - Saved PDF Path: {$savedPdfPath}\n";
    echo "  - Fields: " . json_encode($createdData['data']['pdf_fields']) . "\n";
    
    // Verify file actually exists on disk
    $diskPath = str_replace('storage/', '', $savedPdfPath);
    if (Storage::disk('public')->exists($diskPath)) {
        echo "SUCCESS: Uploaded PDF template exists in public storage.\n";
    } else {
        echo "FAILED: Uploaded PDF template NOT found in public storage.\n";
    }

    // 5. Test updating the template (PUT method with JSON payload for coordinates)
    $updatePayload = [
        'subject' => 'Updated Temp Template',
        'pdf_fields' => array_merge($fieldsConfig, [
            [
                'variable' => '{{position}}',
                'page' => 1,
                'x' => 50,
                'y' => 70,
                'width' => 100,
                'height' => 10,
                'font_size' => 11,
                'font_style' => '',
                'color' => '#000000',
                'align' => 'L',
                'mask' => false
            ]
        ])
    ];
    
    $response = $sendRequest("/api/v1/mail-templates/{$newTemplateId}", 'PUT', $updatePayload);
    echo "\n5. PUT /api/v1/mail-templates/{$newTemplateId} returned status " . $response->getStatusCode() . "\n";
    $updatedData = json_decode($response->getContent(), true);
    if ($response->getStatusCode() === 200) {
        echo "SUCCESS: Coordinates updated! Fields count: " . count($updatedData['data']['pdf_fields']) . "\n";
    } else {
        echo "FAILED to update template coordinates.\n";
    }

    // 6. Test deleting the template (Verify cleanup of DB and file system)
    $response = $sendRequest("/api/v1/mail-templates/{$newTemplateId}", 'DELETE');
    echo "\n6. DELETE /api/v1/mail-templates/{$newTemplateId} returned status " . $response->getStatusCode() . "\n";
    
    if ($response->getStatusCode() === 200) {
        echo "SUCCESS: Template deleted from database.\n";
        // Check if file has been deleted
        if (!Storage::disk('public')->exists($diskPath)) {
            echo "SUCCESS: Uploaded PDF file successfully deleted from public storage disk.\n";
        } else {
            echo "FAILED: Uploaded PDF file STILL exists after template deletion.\n";
        }
    } else {
        echo "FAILED to delete template.\n";
    }

} else {
    echo "FAILED: Could not create test template. Response:\n";
    print_r($createdData);
}

// Clean up temp local file if still exists
if (file_exists($tmpFilePath)) {
    @unlink($tmpFilePath);
}

// Clean up token
$admin->tokens()->where('name', 'test_admin_token')->delete();

echo "\n=== END OF VERIFICATION ===\n";

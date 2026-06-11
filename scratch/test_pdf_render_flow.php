<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Models\MailTemplate;
use App\Models\OnboardingRequest;
use App\Services\DocumentService;

// Ensure templates folder exists
$templatesDir = storage_path('app/public/templates');
if (!file_exists($templatesDir)) {
    mkdir($templatesDir, 0777, true);
}

// Copy free_internship_offer_letter.pdf as the template background
$srcPdf = __DIR__ . '/free_internship_offer_letter.pdf';
$destPdf = $templatesDir . '/free_internship_offer_letter_bg.pdf';

if (file_exists($srcPdf)) {
    copy($srcPdf, $destPdf);
    echo "Copied template background PDF to public templates folder.\n";
} else {
    // If not found in scratch, look for other PDFs or handle error
    echo "Source PDF not found at {$srcPdf}. Searching for other source...\n";
    $files = glob(__DIR__ . '/*.pdf');
    if (!empty($files)) {
        $srcPdf = $files[0];
        copy($srcPdf, $destPdf);
        echo "Copied alternative PDF: " . basename($srcPdf) . "\n";
    } else {
        echo "No PDF files found in scratch directory to copy.\n";
        exit(1);
    }
}

// Update database template
$template = MailTemplate::where('template_name', 'free_internship_offer_letter')->first();
if (!$template) {
    echo "Template free_internship_offer_letter not found in DB.\n";
    exit(1);
}

$template->pdf_path = 'storage/templates/free_internship_offer_letter_bg.pdf';
$template->save();
echo "Updated DB template pdf_path to 'storage/templates/free_internship_offer_letter_bg.pdf'\n";

// Render offer letter
$onboardingRequest = OnboardingRequest::where('onboarding_type', 'free_intern')->first() ?? OnboardingRequest::first();
if (!$onboardingRequest) {
    echo "No onboarding request found to test.\n";
    exit(1);
}

$letterDate = \Carbon\Carbon::now();
$joiningDate = \Carbon\Carbon::parse($onboardingRequest->joining_date);

$variables = [
    'candidate' => $onboardingRequest,
    'candidate_name' => $onboardingRequest->candidate_name,
    'position' => $onboardingRequest->position,
    'duration' => $onboardingRequest->duration ?? '3 months',
    'joining_date' => $joiningDate->format('d/m/Y'),
    'letter_date' => $letterDate->format('d-F Y'),
    'stipend' => number_format((float)($onboardingRequest->ctc ?? 0)),
    'acceptance_date' => $letterDate->copy()->addDays(2)->format('d-m-Y'),
];

try {
    $pdf = DocumentService::render('free_internship_offer_letter', $variables);
    $destPath = __DIR__ . "/test_overlay_output.pdf";
    file_put_contents($destPath, $pdf->output());
    echo "SUCCESS: Rendered overlay PDF saved to {$destPath}\n";
} catch (\Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
}

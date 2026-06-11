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

// Copy background PDF
$srcPdf = __DIR__ . '/paid_internship_offer_letter.pdf';
$destPdf = $templatesDir . '/paid_internship_offer_letter_bg.pdf';

if (file_exists($srcPdf)) {
    copy($srcPdf, $destPdf);
    echo "Copied template background PDF to public templates folder.\n";
} else {
    echo "ERROR: Source PDF not found at {$srcPdf}\n";
    exit(1);
}

$paidFields = [
    [
        'variable' => '{{letter_date}}',
        'page' => 1,
        'x' => 169.94,
        'y' => 41.5,
        'width' => 27.01,
        'height' => 5.5,
        'font_size' => 11,
        'font_style' => 'B',
        'color' => '#0F172A',
        'align' => 'R',
        'mask' => true
    ],
    [
        'variable' => 'DEAR {{candidate_name}}',
        'page' => 1,
        'x' => 17.64,
        'y' => 48.8,
        'width' => 50.0,
        'height' => 5.5,
        'font_size' => 11,
        'font_style' => 'B',
        'color' => '#1E3A8A',
        'align' => 'L',
        'mask' => true
    ],
    [
        'variable' => '{{position}}',
        'page' => 1,
        'x' => 69.85,
        'y' => 105.4,
        'width' => 20.2, // Avoid covering the comma
        'height' => 5.5,
        'font_size' => 11,
        'font_style' => 'B',
        'color' => '#0F172A',
        'align' => 'L',
        'mask' => true
    ],
    [
        'variable' => '{{joining_date}}',
        'page' => 1,
        'x' => 168.05,
        'y' => 105.4,
        'width' => 22.5,
        'height' => 5.5,
        'font_size' => 11,
        'font_style' => 'B',
        'color' => '#0F172A',
        'align' => 'L',
        'mask' => true
    ],
    [
        'variable' => '{{duration}}',
        'page' => 1,
        'x' => 38.59,
        'y' => 112.1,
        'width' => 18.0, // Avoid covering the period
        'height' => 5.5,
        'font_size' => 11,
        'font_style' => 'B',
        'color' => '#0F172A',
        'align' => 'L',
        'mask' => true
    ],
    [
        'variable' => 'You will receive a stipend of Rs. {{stipend}} per month during the internship period.',
        'page' => 1,
        'x' => 17.64,
        'y' => 152.1,
        'width' => 141.0, // Mask the entire sentence but stop before "Applicable"
        'height' => 5.5,
        'font_size' => 9.5, // Slightly smaller font size to fit within the line width
        'font_style' => '',
        'color' => '#0F172A',
        'align' => 'L',
        'mask' => true
    ]
];

$tmpl = MailTemplate::where('template_name', 'paid_internship_offer_letter')->first();
if ($tmpl) {
    $tmpl->pdf_path = 'storage/templates/paid_internship_offer_letter_bg.pdf';
    $tmpl->pdf_fields = $paidFields;
    $tmpl->save();
    echo "SUCCESS: Updated exact coordinates and path for paid_internship_offer_letter.\n";
} else {
    echo "ERROR: Template paid_internship_offer_letter not found in DB.\n";
    exit(1);
}

// Generate test PDF
$onboardingRequest = OnboardingRequest::where('onboarding_type', 'intern')->first() ?? OnboardingRequest::first();
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
    'stipend' => '15,000', // Set a realistic non-zero stipend for the test
    'acceptance_date' => $letterDate->copy()->addDays(2)->format('d-m-Y'),
];

try {
    $pdf = DocumentService::render('paid_internship_offer_letter', $variables);
    $destPath = __DIR__ . "/test_overlay_paid_output.pdf";
    file_put_contents($destPath, $pdf->output());
    echo "SUCCESS: Rendered overlay paid PDF saved to {$destPath}\n";
} catch (\Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
}

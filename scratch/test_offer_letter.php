<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    Illuminate\Http\Request::capture()
);

use App\Models\OnboardingRequest;
use PDF;

$onboardingRequest = OnboardingRequest::first();
if ($onboardingRequest) {
    echo "Found Onboarding Request ID: {$onboardingRequest->id}, Candidate: {$onboardingRequest->candidate_name}\n";
    try {
        echo "Generating PDF...\n";
        $pdf = PDF::loadView('pdf.offer-letter', [
            'candidate' => $onboardingRequest,
            'letter_date' => date('Y-m-d'),
            'content' => 'Test special terms content here.',
        ]);
        
        $output = $pdf->output();
        echo "PDF output size: " . strlen($output) . " bytes\n";
        
        $destPath = __DIR__ . '/test_offer_letter.pdf';
        file_put_contents($destPath, $output);
        echo "Successfully generated PDF: $destPath\n";
    } catch (\Exception $e) {
        echo "Error generating PDF: " . $e->getMessage() . "\n";
        echo $e->getTraceAsString() . "\n";
    }
} else {
    echo "No OnboardingRequest record found\n";
}

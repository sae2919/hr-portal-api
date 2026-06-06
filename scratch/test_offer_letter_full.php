<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    Illuminate\Http\Request::capture()
);

use App\Models\OnboardingRequest;
use App\Models\OfferLetter;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

// Authenticate a user
$user = User::first();
if ($user) {
    auth()->login($user);
    echo "Authenticated as User ID: {$user->id}\n";
} else {
    echo "No user found in database!\n";
}

$onboardingRequest = OnboardingRequest::find(5);
if ($onboardingRequest) {
    echo "Found Onboarding Request ID: {$onboardingRequest->id}, Candidate: {$onboardingRequest->candidate_name}\n";
    try {
        echo "Generating PDF...\n";
        $pdf = Pdf::loadView('pdf.offer-letter', [
            'candidate' => $onboardingRequest,
            'letter_date' => date('Y-m-d'),
            'content' => 'Test special terms content here.',
        ]);
        
        $output = $pdf->output();
        echo "PDF output size: " . strlen($output) . " bytes\n";
        
        $fileName = "offer_letter_{$onboardingRequest->id}_{$onboardingRequest->candidate_name}.pdf";
        $filePath = "offer_letters/{$fileName}";
        
        echo "Writing to Storage...\n";
        Storage::disk('public')->put($filePath, $output);
        echo "Successfully wrote to storage at: " . Storage::disk('public')->path($filePath) . "\n";
        
        echo "Inserting into database...\n";
        $offerLetter = OfferLetter::create([
            'onboarding_request_id' => $onboardingRequest->id,
            'letter_date' => date('Y-m-d'),
            'file_path' => $filePath,
            'status' => 'draft',
            'created_by' => auth()->id(),
        ]);
        echo "Successfully created OfferLetter record ID: {$offerLetter->id}\n";
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        echo $e->getTraceAsString() . "\n";
    }
} else {
    echo "No OnboardingRequest record found\n";
}

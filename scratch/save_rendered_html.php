<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Models\OnboardingRequest;
use App\Models\MailTemplate;
use Illuminate\Support\Facades\Blade;

$onboardingRequest = OnboardingRequest::where('onboarding_type', 'intern')->first() ?? OnboardingRequest::first();
$templateName = 'paid_internship_offer_letter';

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

$template = MailTemplate::where('template_name', $templateName)->first();
$compiledBody = Blade::render($template->body, $variables);
$style = $template->style ?? '';
$html = "<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <style>
        {$style}
    </style>
</head>
<body>
    {$compiledBody}
</body>
</html>";

file_put_contents(__DIR__ . '/rendered_intern.html', $html);
echo "Saved compiled HTML to scratch/rendered_intern.html\n";

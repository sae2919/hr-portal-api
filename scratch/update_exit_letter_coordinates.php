<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Models\MailTemplate;
use App\Models\OffboardingRequest;
use App\Services\DocumentService;

// Ensure templates folder exists
$templatesDir = storage_path('app/public/templates');
if (!file_exists($templatesDir)) {
    mkdir($templatesDir, 0777, true);
}

// Copy background PDF
$srcPdf = __DIR__ . '/test_exit_dynamic.pdf';
$destPdf = $templatesDir . '/exit_relieving_letter_bg.pdf';

if (file_exists($srcPdf)) {
    copy($srcPdf, $destPdf);
    echo "Copied template background PDF to public templates folder.\n";
} else {
    echo "ERROR: Source PDF not found at {$srcPdf}\n";
    exit(1);
}

$exitFields = [
    [
        'variable' => '{{date}}',
        'page' => 1,
        'x' => 171.53,
        'y' => 59.0,
        'width' => 26.0,
        'height' => 5.5,
        'font_size' => 11,
        'font_style' => '',
        'color' => '#000000',
        'align' => 'R',
        'mask' => true
    ],
    [
        'variable' => '{{salutation}} {{employee_name}},',
        'page' => 1,
        'x' => 17.64,
        'y' => 66.0,
        'width' => 75.0,
        'height' => 5.5,
        'font_size' => 11,
        'font_style' => '',
        'color' => '#000000',
        'align' => 'L',
        'mask' => true
    ],
    [
        'variable' => '{{salutation}} {{employee_name}}',
        'page' => 1,
        'x' => 62.0,
        'y' => 87.0,
        'width' => 50.0,
        'height' => 5.5,
        'font_size' => 11,
        'font_style' => 'B',
        'color' => '#000000',
        'align' => 'L',
        'mask' => true
    ],
    [
        'variable' => '{{designation}}',
        'page' => 1,
        'x' => 38.31,
        'y' => 94.5,
        'width' => 38.0,
        'height' => 5.5,
        'font_size' => 11,
        'font_style' => 'B',
        'color' => '#000000',
        'align' => 'L',
        'mask' => true
    ],
    [
        'variable' => '{{joining_date}}',
        'page' => 1,
        'x' => 79.37,
        'y' => 94.5,
        'width' => 28.0,
        'height' => 5.5,
        'font_size' => 11,
        'font_style' => 'B',
        'color' => '#000000',
        'align' => 'L',
        'mask' => true
    ],
    [
        'variable' => '{{last_working_day}}',
        'page' => 1,
        'x' => 112.39,
        'y' => 94.5,
        'width' => 28.0,
        'height' => 5.5,
        'font_size' => 11,
        'font_style' => 'B',
        'color' => '#000000',
        'align' => 'L',
        'mask' => true
    ],
    [
        'variable' => '{{salutation}} {{employee_name}}',
        'page' => 1,
        'x' => 67.5,
        'y' => 125.5,
        'width' => 50.0,
        'height' => 5.5,
        'font_size' => 11,
        'font_style' => 'B',
        'color' => '#000000',
        'align' => 'L',
        'mask' => true
    ],
    [
        'variable' => '{{last_working_day}}',
        'page' => 1,
        'x' => 128.19,
        'y' => 132.5,
        'width' => 28.0,
        'height' => 5.5,
        'font_size' => 11,
        'font_style' => 'B',
        'color' => '#000000',
        'align' => 'L',
        'mask' => true
    ],
    [
        'variable' => '{{employee_code}}',
        'page' => 1,
        'x' => 65.82,
        'y' => 163.0,
        'width' => 30.0,
        'height' => 5.5,
        'font_size' => 11,
        'font_style' => '',
        'color' => '#000000',
        'align' => 'L',
        'mask' => true
    ],
    [
        'variable' => '{{employee_name}}',
        'page' => 1,
        'x' => 65.82,
        'y' => 171.5,
        'width' => 60.0,
        'height' => 5.5,
        'font_size' => 11,
        'font_style' => '',
        'color' => '#000000',
        'align' => 'L',
        'mask' => true
    ],
    [
        'variable' => '{{designation}}',
        'page' => 1,
        'x' => 65.82,
        'y' => 180.5,
        'width' => 60.0,
        'height' => 5.5,
        'font_size' => 11,
        'font_style' => '',
        'color' => '#000000',
        'align' => 'L',
        'mask' => true
    ],
    [
        'variable' => '{{designation}}',
        'page' => 1,
        'x' => 65.82,
        'y' => 189.0,
        'width' => 60.0,
        'height' => 5.5,
        'font_size' => 11,
        'font_style' => '',
        'color' => '#000000',
        'align' => 'L',
        'mask' => true
    ],
    [
        'variable' => '{{joining_date}}',
        'page' => 1,
        'x' => 65.82,
        'y' => 197.5,
        'width' => 40.0,
        'height' => 5.5,
        'font_size' => 11,
        'font_style' => '',
        'color' => '#000000',
        'align' => 'L',
        'mask' => true
    ],
    [
        'variable' => '{{last_working_day}}',
        'page' => 1,
        'x' => 65.82,
        'y' => 206.5,
        'width' => 40.0,
        'height' => 5.5,
        'font_size' => 11,
        'font_style' => '',
        'color' => '#000000',
        'align' => 'L',
        'mask' => true
    ]
];

$tmpl = MailTemplate::where('template_name', 'exit_relieving_letter')->first();
if ($tmpl) {
    $tmpl->pdf_path = 'storage/templates/exit_relieving_letter_bg.pdf';
    $tmpl->pdf_fields = $exitFields;
    $tmpl->save();
    echo "SUCCESS: Updated coordinates and path for exit_relieving_letter.\n";
} else {
    echo "ERROR: Template exit_relieving_letter not found in DB.\n";
    exit(1);
}

$variables = [
    'salutation' => 'Mr.',
    'employee_name' => 'John Doe',
    'designation' => 'Lead Engineer',
    'joining_date' => '01-Jan-2022',
    'last_working_day' => '30-Jun-2026',
    'employee_code' => 'TS9999',
    'date' => '25-Jun-2026',
];

try {
    $pdf = DocumentService::render('exit_relieving_letter', $variables);
    $destPath = __DIR__ . "/test_overlay_exit_output.pdf";
    file_put_contents($destPath, $pdf->output());
    echo "SUCCESS: Rendered overlay exit PDF saved to {$destPath}\n";
} catch (\Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
}

<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Payroll;
use App\Models\MailTemplate;
use App\Services\DocumentService;

// Ensure templates folder exists
$templatesDir = storage_path('app/public/templates');
if (!file_exists($templatesDir)) {
    mkdir($templatesDir, 0777, true);
}

$payroll = Payroll::find(14);
if (!$payroll) {
    echo "ERROR: Payroll record ID=14 not found. Please verify payroll exists in DB.\n";
    exit(1);
}

echo "Step 1: Fetching original template and rendering to PDF as background...\n";
$tmpl = MailTemplate::where('template_name', 'monthly_payslip_template')->first();
if (!$tmpl) {
    echo "ERROR: monthly_payslip_template not found in DB.\n";
    exit(1);
}

// Store original settings to restore later
$origPdfPath = $tmpl->pdf_path;
$origPdfFields = $tmpl->pdf_fields;
$origBody = $tmpl->body;
$origStyle = $tmpl->style;

// Temporarily ensure pdf_path is null so it renders from HTML to make a background template
$tmpl->pdf_path = null;
$tmpl->save();

$vars = DocumentService::getPayslipVariables($payroll);
$htmlPdf = DocumentService::render('monthly_payslip_template', $vars);
$bgPdfPath = __DIR__ . '/test_payslip_bg_temp.pdf';
file_put_contents($bgPdfPath, $htmlPdf->output());
echo "Saved temporary background PDF to: {$bgPdfPath}\n";

$destPdf = $templatesDir . '/monthly_payslip_template_bg.pdf';
copy($bgPdfPath, $destPdf);
echo "Copied to template storage: {$destPdf}\n";

echo "Step 2: Configuring database to use background PDF and coordinate fields...\n";
$tmpl->pdf_path = 'storage/templates/monthly_payslip_template_bg.pdf';
// We place the employee_name and net_salary overlay at custom locations
$tmpl->pdf_fields = [
    [
        'variable' => '{{employee_name}}',
        'page' => 1,
        'x' => 45.0,
        'y' => 50.0,
        'width' => 60.0,
        'height' => 5.5,
        'font_size' => 11,
        'font_style' => 'B',
        'color' => '#ff0000', // Red color for overlay
        'align' => 'L',
        'mask' => true
    ],
    [
        'variable' => '{{net_salary}}',
        'page' => 1,
        'x' => 150.0,
        'y' => 50.0,
        'width' => 40.0,
        'height' => 5.5,
        'font_size' => 11,
        'font_style' => 'B',
        'color' => '#0000ff', // Blue color for overlay
        'align' => 'R',
        'mask' => true
    ]
];
$tmpl->save();

echo "Step 3: Rendering payslip PDF in PDF background template mode...\n";
// Let's modify the variables array to check that the replacements are flat and working
$testVars = DocumentService::getPayslipVariables($payroll);
$testVars['employee_name'] = 'OVERLAID TEST NAME';
$testVars['net_salary'] = '99,999.00';

try {
    $pdf = DocumentService::render('monthly_payslip_template', $testVars);
    $finalPdfPath = __DIR__ . '/test_payslip_overlaid_output.pdf';
    file_put_contents($finalPdfPath, $pdf->output());
    echo "SUCCESS: Rendered overlaid payslip PDF saved to {$finalPdfPath}\n";
} catch (\Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
} finally {
    echo "Step 4: Restoring original monthly_payslip_template configurations...\n";
    $tmpl->pdf_path = $origPdfPath;
    $tmpl->pdf_fields = $origPdfFields;
    $tmpl->body = $origBody;
    $tmpl->style = $origStyle;
    $tmpl->save();
    echo "Restored original template state in database.\n";
    @unlink($bgPdfPath);
}

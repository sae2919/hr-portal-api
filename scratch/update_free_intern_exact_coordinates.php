<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MailTemplate;

$freeFields = [
    [
        'variable' => '{{letter_date}}',
        'page' => 1,
        'x' => 169.94,
        'y' => 41.5, // Slightly adjusted for FPDF cell baseline offset
        'width' => 27.01,
        'height' => 5.5,
        'font_size' => 11,
        'font_style' => 'B', // Keep date bold in the template
        'color' => '#0F172A', // Using brand dark blue color
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
        'color' => '#1E3A8A', // Blue
        'align' => 'L',
        'mask' => true
    ],
    [
        'variable' => '{{position}}', // First occurrence (in paragraph 1)
        'page' => 1,
        'x' => 38.10,
        'y' => 65.5,
        'width' => 20.5,
        'height' => 5.5,
        'font_size' => 11,
        'font_style' => 'B',
        'color' => '#0F172A',
        'align' => 'L',
        'mask' => true
    ],
    [
        'variable' => '{{position}}', // Second occurrence (in section 1)
        'page' => 1,
        'x' => 70.16,
        'y' => 105.4,
        'width' => 20.5,
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
        'x' => 164.77,
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
        'x' => 78.44,
        'y' => 112.1,
        'width' => 18.0,
        'height' => 5.5,
        'font_size' => 11,
        'font_style' => 'B',
        'color' => '#0F172A',
        'align' => 'L',
        'mask' => true
    ]
];

$tmpl = MailTemplate::where('template_name', 'free_internship_offer_letter')->first();
if ($tmpl) {
    $tmpl->pdf_fields = $freeFields;
    $tmpl->save();
    echo "SUCCESS: Updated exact coordinates for free_internship_offer_letter.\n";
} else {
    echo "ERROR: Template not found.\n";
}

<?php
use App\Models\MailTemplate;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Paid Internship coordinates
$paidFields = [
    [
        'variable' => '{{letter_date}}',
        'page' => 1,
        'x' => 172.8,
        'y' => 47.6,
        'width' => 25.0,
        'height' => 6.0,
        'font_size' => 11,
        'font_style' => '',
        'color' => '#000000',
        'align' => 'R',
        'mask' => true
    ],
    [
        'variable' => 'DEAR {{candidate_name}}',
        'page' => 1,
        'x' => 17.6,
        'y' => 53.3,
        'width' => 80.0,
        'height' => 6.0,
        'font_size' => 11,
        'font_style' => 'B',
        'color' => '#000000',
        'align' => 'L',
        'mask' => true
    ],
    [
        'variable' => '{{position}}',
        'page' => 1,
        'x' => 70.2,
        'y' => 119.1,
        'width' => 30.0,
        'height' => 6.0,
        'font_size' => 11,
        'font_style' => 'B',
        'color' => '#000000',
        'align' => 'L',
        'mask' => true
    ],
    [
        'variable' => '{{joining_date}}',
        'page' => 1,
        'x' => 163.9,
        'y' => 119.1,
        'width' => 20.0,
        'height' => 6.0,
        'font_size' => 11,
        'font_style' => 'B',
        'color' => '#000000',
        'align' => 'L',
        'mask' => true
    ],
    [
        'variable' => '{{duration}}',
        'page' => 1,
        'x' => 22.4,
        'y' => 125.1,
        'width' => 25.0,
        'height' => 6.0,
        'font_size' => 11,
        'font_style' => 'B',
        'color' => '#000000',
        'align' => 'L',
        'mask' => true
    ],
    [
        'variable' => 'stipend of Rs. {{stipend}}',
        'page' => 1,
        'x' => 73.9,
        'y' => 173.8,
        'width' => 50.0,
        'height' => 6.0,
        'font_size' => 11,
        'font_style' => 'B',
        'color' => '#000000',
        'align' => 'L',
        'mask' => true
    ]
];

// Seed them
$templates = [
    'paid_internship_offer_letter' => $paidFields,
    'free_internship_offer_letter' => [], 
    'full_time_offer_letter' => $paidFields,
];

// Adjust free intern stipend (remove it)
$freeFields = array_filter($paidFields, function($f) {
    return !str_contains($f['variable'], 'stipend');
});

$templates['free_internship_offer_letter'] = array_values($freeFields);

foreach ($templates as $name => $fields) {
    $tmpl = MailTemplate::where('template_name', $name)->first();
    if ($tmpl) {
        $tmpl->pdf_fields = $fields;
        $tmpl->save();
        echo "Seeded fields for {$name}\n";
    }
}
echo "Done seeding fields coordinates!\n";

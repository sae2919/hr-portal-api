<?php

define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Test render free internship offer letter
$vars = [
    'candidate_name' => 'TEST CANDIDATE',
    'position'       => 'SAP CAPM Developer Intern',
    'joining_date'   => '27/04/2026',
    'duration'       => '3 months',
    'letter_date'    => '2026-04-23',
    'stipend'        => '0',
    'acceptance_date'=> '25-04-2026',
];

$pdf = App\Services\DocumentService::render('free_internship_offer_letter', $vars);
file_put_contents(__DIR__.'/../storage/app/public/test_free_offer.pdf', $pdf->output());
echo "Free offer letter PDF generated: storage/app/public/test_free_offer.pdf" . PHP_EOL;

$pdf2 = App\Services\DocumentService::render('paid_internship_offer_letter', $vars);
file_put_contents(__DIR__.'/../storage/app/public/test_paid_offer.pdf', $pdf2->output());
echo "Paid offer letter PDF generated: storage/app/public/test_paid_offer.pdf" . PHP_EOL;

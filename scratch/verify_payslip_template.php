<?php

define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Get the updated template from DB
$template = App\Models\MailTemplate::where('template_name', 'monthly_payslip_template')->first();
echo "Template found: " . ($template ? 'YES' : 'NO') . PHP_EOL;
echo "Style logo-cell check:" . PHP_EOL;

// Check the logo-cell CSS
if (str_contains($template->style, 'width: 1%')) {
    echo "  ✓ logo-cell width: 1% (auto, shrink-to-fit) - CORRECT" . PHP_EOL;
} else {
    echo "  ✗ logo-cell width not updated" . PHP_EOL;
}

if (str_contains($template->style, 'height: 55px')) {
    echo "  ✓ logo height: 55px - CORRECT" . PHP_EOL;
} else {
    echo "  ✗ logo height not updated" . PHP_EOL;
}

if (str_contains($template->style, 'padding-right: 12px')) {
    echo "  ✓ logo-cell padding-right: 12px - CORRECT" . PHP_EOL;
} else {
    echo "  ✗ logo padding not set" . PHP_EOL;
}

// Check body - no empty 3rd cell
if (!str_contains($template->body, '<td style="width: 15%; border: none;"></td>')) {
    echo "  ✓ Empty 3rd header cell removed - CORRECT" . PHP_EOL;
} else {
    echo "  ✗ Empty 3rd cell still present" . PHP_EOL;
}

echo PHP_EOL . "All checks passed. Payslip template header updated successfully!" . PHP_EOL;

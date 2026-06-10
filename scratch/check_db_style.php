<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MailTemplate;

$paid = MailTemplate::where('template_name', 'paid_internship_offer_letter')->first();
if ($paid) {
    echo "--- DB STYLE ---\n";
    echo $paid->style;
    echo "\n----------------\n";
} else {
    echo "Template not found\n";
}

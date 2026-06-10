<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MailTemplate;

$result = MailTemplate::where('template_name', 'paid_internship_offer_letter')->first();

if ($result) {
    echo "=== STYLE ===\n" . $result->style . "\n";
    echo "=== BODY ===\n" . $result->body . "\n";
} else {
    echo "Template paid_internship_offer_letter not found.\n";
}

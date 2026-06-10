<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MailTemplate;

$paid = MailTemplate::where('template_name', 'paid_internship_offer_letter')->first();
if ($paid) {
    file_put_contents(__DIR__ . '/paid_style.css', $paid->style);
    file_put_contents(__DIR__ . '/paid_body.html', $paid->body);
    echo "Saved paid template style and body.\n";
} else {
    echo "Paid template not found.\n";
}

$free = MailTemplate::where('template_name', 'free_internship_offer_letter')->first();
if ($free) {
    file_put_contents(__DIR__ . '/free_style.css', $free->style);
    file_put_contents(__DIR__ . '/free_body.html', $free->body);
    echo "Saved free template style and body.\n";
} else {
    echo "Free template not found.\n";
}

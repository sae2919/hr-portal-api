<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MailTemplate;

$templates = [
    'free_internship_offer_letter',
    'paid_internship_offer_letter',
    'full_time_offer_letter'
];

foreach ($templates as $tname) {
    $t = MailTemplate::where('template_name', $tname)->first();
    if ($t) {
        file_put_contents(__DIR__ . "/{$tname}_body.html", $t->body);
        file_put_contents(__DIR__ . "/{$tname}_style.css", $t->style);
        echo "Saved {$tname} style and body.\n";
    } else {
        echo "Template {$tname} not found.\n";
    }
}

<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MailTemplate;

foreach (['free_internship_offer_letter', 'paid_internship_offer_letter', 'full_time_offer_letter'] as $name) {
    $t = MailTemplate::where('template_name', $name)->first();
    if (!$t) {
        echo "Template {$name} not found.\n";
        continue;
    }
    echo "========================================\n";
    echo "Template: {$name}\n";
    echo "========================================\n";
    preg_match_all('/src="([^"]+)"/', $t->body, $matches);
    foreach ($matches[1] as $idx => $src) {
        $prefix = substr($src, 0, 40);
        echo "Img #{$idx}: prefix='{$prefix}', length=" . strlen($src) . "\n";
    }
}

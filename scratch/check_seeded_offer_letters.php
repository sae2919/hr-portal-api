<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MailTemplate;

$templates = MailTemplate::where('type', 'offer_joining')->get();

foreach ($templates as $t) {
    echo "ID: {$t->id} | Name: {$t->template_name} | Length of Body: " . strlen($t->body) . "\n";
    if (strlen($t->body) > 0) {
        echo "First 150 chars of body:\n" . substr(strip_tags($t->body), 0, 150) . "\n";
    }
    echo str_repeat("-", 40) . "\n";
}

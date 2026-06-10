<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MailTemplate;

$templates = MailTemplate::all();
echo "Templates count: " . count($templates) . "\n";
foreach ($templates as $t) {
    echo "ID: {$t->id} | Name: {$t->template_name} | Active: {$t->active_status}\n";
}

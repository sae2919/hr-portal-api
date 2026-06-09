<?php

define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$free = App\Models\MailTemplate::where('template_name', 'free_internship_offer_letter')->first();
$paid = App\Models\MailTemplate::where('template_name', 'paid_internship_offer_letter')->first();

echo "=== FREE TEMPLATE ===" . PHP_EOL;
if ($free) {
    echo "Style (first 400 chars):" . PHP_EOL;
    echo substr($free->style, 0, 400) . PHP_EOL . PHP_EOL;
    echo "Body (first 300 chars):" . PHP_EOL;
    echo substr($free->body, 0, 300) . PHP_EOL;
} else {
    echo "NOT FOUND!" . PHP_EOL;
}

echo PHP_EOL . "=== PAID TEMPLATE ===" . PHP_EOL;
if ($paid) {
    echo "Style (first 400 chars):" . PHP_EOL;
    echo substr($paid->style, 0, 400) . PHP_EOL . PHP_EOL;
    echo "Body (first 300 chars):" . PHP_EOL;
    echo substr($paid->body, 0, 300) . PHP_EOL;
} else {
    echo "NOT FOUND!" . PHP_EOL;
}

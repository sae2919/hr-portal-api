<?php

define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\CompanySetting;

$logo = CompanySetting::where('key', 'company_logo')->first();
if ($logo) {
    echo "company_logo value in DB: '" . $logo->value . "'\n";
} else {
    echo "company_logo not found in DB!\n";
}

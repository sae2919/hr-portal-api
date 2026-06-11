<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "company_name: " . \App\Models\CompanySetting::where('key', 'company_name')->value('value') . "\n";
echo "company_logo: " . \App\Models\CompanySetting::where('key', 'company_logo')->value('value') . "\n";

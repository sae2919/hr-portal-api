<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    Illuminate\Http\Request::capture()
);

use App\Models\CompanySetting;

$settings = CompanySetting::all();
foreach ($settings as $setting) {
    echo "{$setting->key} => {$setting->value}\n";
}

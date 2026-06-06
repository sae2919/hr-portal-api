<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    Illuminate\Http\Request::capture()
);

use App\Models\OnboardingRequest;

$requests = OnboardingRequest::all();
foreach ($requests as $r) {
    echo "ID: {$r->id}, Name: {$r->candidate_name}, Position: {$r->position}, Email: {$r->email}\n";
}

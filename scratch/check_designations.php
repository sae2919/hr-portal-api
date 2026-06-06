<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Designation;
use App\Models\Department;

echo "--- DESIGNATIONS ---\n";
foreach (Designation::all() as $d) {
    echo "ID: {$d->id} | Title: {$d->title} | Department: " . ($d->department?->name ?? 'N/A') . "\n";
}

echo "\n--- DEPARTMENTS ---\n";
foreach (Department::all() as $dept) {
    echo "ID: {$dept->id} | Name: {$dept->name}\n";
}

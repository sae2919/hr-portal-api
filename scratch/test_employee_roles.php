<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    Illuminate\Http\Request::capture()
);

use App\Models\Employee;

$employees = Employee::with(['designation', 'user', 'manager'])->get();
echo str_pad("ID", 5) . str_pad("Name", 30) . str_pad("Designation", 30) . str_pad("User Role", 15) . "Reports To\n";
echo str_repeat("-", 90) . "\n";
foreach ($employees as $emp) {
    $designation = $emp->designation?->title ?? 'None';
    $userRole = $emp->user ? implode(', ', $emp->user->getRoleNames()->toArray()) : 'No User';
    $reportsTo = $emp->manager ? $emp->manager->full_name . " (ID: {$emp->manager->id})" : 'None';
    
    echo str_pad($emp->id, 5) . 
         str_pad($emp->full_name, 30) . 
         str_pad($designation, 30) . 
         str_pad($userRole, 15) . 
         $reportsTo . "\n";
}

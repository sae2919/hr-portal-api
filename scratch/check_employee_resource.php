<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Employee;
use App\Http\Resources\EmployeeResource;

$employee = Employee::find(16);
$employee->load(['department', 'designation', 'manager', 'previousDesignation', 'assetAllocations.asset']);

$resource = new EmployeeResource($employee);
$json = json_encode($resource->toArray(request()), JSON_PRETTY_PRINT);
echo $json . "\n";

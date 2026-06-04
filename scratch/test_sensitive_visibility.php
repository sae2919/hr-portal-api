<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    Illuminate\Http\Request::capture()
);

use App\Models\User;
use App\Models\Employee;
use App\Http\Resources\EmployeeResource;

// Let's select:
// Employee being viewed: Chaitanya Kumar Jinka (ID: 22)
$targetEmployee = Employee::find(22);

$testUsers = [
    'admin@hrportal.com' => 'Admin (Should see sensitive details)',
    'jckumar99@gmail.com' => 'Himself (Should see sensitive details)',
    'santoshasole9@gmail.com' => 'Tech Lead (Should NOT see sensitive details)',
];

foreach ($testUsers as $email => $label) {
    echo "==================================================\n";
    echo "Testing viewer: $label ($email)\n";
    echo "==================================================\n";
    
    $user = User::where('email', $email)->first();
    if (!$user) {
        echo "User not found!\n";
        continue;
    }
    
    auth()->login($user);
    
    $resource = new EmployeeResource($targetEmployee);
    $data = $resource->toArray(request());
    
    // Check fields
    $basicSalary = $data['basic_salary'] ?? 'MISSING';
    $bankAccount = $data['bank_account_number'] ?? 'MISSING';
    $panNumber = $data['pan_number'] ?? 'MISSING';
    
    echo "  basic_salary: " . var_export($basicSalary, true) . "\n";
    echo "  bank_account_number: " . var_export($bankAccount, true) . "\n";
    echo "  pan_number: " . var_export($panNumber, true) . "\n";
    
    $isVisible = ($basicSalary !== 'MISSING');
    echo "  Sensitive details visible: " . ($isVisible ? 'YES' : 'NO') . "\n\n";
}

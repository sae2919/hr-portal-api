<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    Illuminate\Http\Request::capture()
);

use App\Models\User;
use App\Http\Controllers\Api\Employee\EmployeeController;
use Illuminate\Http\Request;

$user = User::where('email', 'vasa.raviteja@gmail.com')->first();
if ($user) {
    // Authenticate the user
    auth()->login($user);
    
    echo "Logged in user: {$user->name} (Role: " . $user->getRoleNames()->first() . ")\n";
    
    // Call isManager method
    $controller = new EmployeeController();
    $reflection = new \ReflectionClass($controller);
    
    $isManager = $reflection->getMethod('isManager');
    $isManager->setAccessible(true);
    echo "isManager() returns: " . ($isManager->invoke($controller) ? 'TRUE' : 'FALSE') . "\n";
    
    $isAdminOrHR = $reflection->getMethod('isAdminOrHR');
    $isAdminOrHR->setAccessible(true);
    echo "isAdminOrHR() returns: " . ($isAdminOrHR->invoke($controller) ? 'TRUE' : 'FALSE') . "\n";
    
    $managerDeptId = $reflection->getMethod('managerDeptId');
    $managerDeptId->setAccessible(true);
    echo "managerDeptId() returns: " . var_export($managerDeptId->invoke($controller), true) . "\n";

    // Call index method
    $request = new Request();
    $res = $controller->index($request);
    
    // Check type of response
    if ($res instanceof \Illuminate\Http\Resources\Json\AnonymousResourceCollection) {
        $data = $res->resolve();
        echo "Response returned AnonymousResourceCollection with " . count($data) . " employees:\n";
        foreach ($data as $emp) {
            echo "  - Employee: {$emp['full_name']} (ID: {$emp['id']}), Dept ID: {$emp['department_id']}\n";
        }
    } else {
        echo "Response returned standard JSON response:\n";
        print_r($res->getData(true));
    }
} else {
    echo "User not found\n";
}

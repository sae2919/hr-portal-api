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

$emails = [
    'santoshasole9@gmail.com' => 'Santosh Asole (Tech Lead)',
    'sshakthi507@gmail.com' => 'Shakti Sharma (Sales head)',
    'vasa.raviteja@gmail.com' => 'Vasa Raviteja (SEO lead)',
];

$controller = new EmployeeController();

foreach ($emails as $email => $label) {
    echo "==================================================\n";
    echo "Testing User: $label ($email)\n";
    echo "==================================================\n";
    
    $user = User::where('email', $email)->first();
    if (!$user) {
        echo "User not found!\n";
        continue;
    }
    
    // Log in
    auth()->login($user);
    
    // Check hasRole
    $isTL = $user->hasRole('team_lead') ? 'TRUE' : 'FALSE';
    $isMgr = $user->hasRole('manager') ? 'TRUE' : 'FALSE';
    
    echo "Role checks: hasRole('team_lead') = $isTL, hasRole('manager') = $isMgr\n";
    
    // Call controller index
    $request = new Request();
    $res = $controller->index($request);
    
    if ($res instanceof \Illuminate\Http\Resources\Json\AnonymousResourceCollection) {
        $data = $res->resolve();
        echo "Response: success (returned " . count($data) . " employees):\n";
        foreach ($data as $emp) {
            $reportsTo = $emp['reporting_to'] ? "Reports to: " . $emp['reporting_to'] : "Reports to: None";
            echo "  - {$emp['full_name']} (ID: {$emp['id']}, Dept ID: {$emp['department_id']}, $reportsTo)\n";
        }
    } else {
        echo "Response: standard JSON\n";
        print_r($res->getData(true));
    }
    echo "\n";
}

<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    Illuminate\Http\Request::capture()
);

use App\Models\User;
use App\Models\Employee;

echo "--- User List ---\n";
$users = User::with('employee')->get();
foreach ($users as $user) {
    $roles = $user->getRoleNames()->toArray();
    echo "ID: {$user->id}, Name: {$user->name}, Email: {$user->email}, Employee ID: {$user->employee_id}, Roles: " . implode(', ', $roles) . "\n";
}

echo "\n--- Team Lead Subordinates ---\n";
// Let's find any team_lead
$teamLeadUser = User::whereHas('roles', function($q) { $q->where('name', 'team_lead'); })->first();
if ($teamLeadUser) {
    echo "Found Team Lead User: {$teamLeadUser->name}\n";
    $employee = $teamLeadUser->employee;
    if ($employee) {
        echo "Linked Employee: {$employee->first_name} {$employee->last_name} (ID: {$employee->id}), Dept ID: {$employee->department_id}\n";
        $subordinates = Employee::where('reporting_to', $employee->id)->get();
        echo "Subordinates count: " . $subordinates->count() . "\n";
        foreach ($subordinates as $sub) {
            echo "  - Subordinate: {$sub->first_name} {$sub->last_name} (ID: {$sub->id}), Dept ID: {$sub->department_id}\n";
        }
    } else {
        echo "No employee linked to Team Lead user.\n";
    }
} else {
    echo "No user with 'team_lead' role found.\n";
}

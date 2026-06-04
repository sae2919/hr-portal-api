<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    Illuminate\Http\Request::capture()
);

use App\Models\User;

$user = User::where('email', 'vasa.raviteja@gmail.com')->first();
if ($user) {
    echo "User ID: {$user->id}\n";
    echo "hasRole('team_lead'): " . ($user->hasRole('team_lead') ? 'TRUE' : 'FALSE') . "\n";
    echo "hasRole('team_lead', 'api'): " . ($user->hasRole('team_lead', 'api') ? 'TRUE' : 'FALSE') . "\n";
    echo "hasRole('team_lead', 'web'): " . ($user->hasRole('team_lead', 'web') ? 'TRUE' : 'FALSE') . "\n";
    
    echo "Roles with guard names:\n";
    foreach ($user->roles as $role) {
        echo "  - Role: {$role->name}, Guard: {$role->guard_name}\n";
    }
} else {
    echo "User not found\n";
}

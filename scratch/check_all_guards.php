<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    Illuminate\Http\Request::capture()
);

use App\Models\User;

foreach ([1 => 'Admin', 2 => 'HR', 18 => 'Team Lead'] as $id => $label) {
    $user = User::find($id);
    if ($user) {
        echo "--- {$label} (User ID: {$user->id}, Email: {$user->email}) ---\n";
        echo "  hasRole('super_admin'): " . ($user->hasRole('super_admin') ? 'TRUE' : 'FALSE') . "\n";
        echo "  hasRole('admin'): " . ($user->hasRole('admin') ? 'TRUE' : 'FALSE') . "\n";
        echo "  hasRole('hr'): " . ($user->hasRole('hr') ? 'TRUE' : 'FALSE') . "\n";
        echo "  hasRole('team_lead'): " . ($user->hasRole('team_lead') ? 'TRUE' : 'FALSE') . "\n";
        echo "  Roles:\n";
        foreach ($user->roles as $role) {
            echo "    - Role: {$role->name}, Guard: {$role->guard_name}\n";
        }
    }
}

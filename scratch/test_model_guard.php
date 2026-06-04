<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    Illuminate\Http\Request::capture()
);

use App\Models\User;

// Define a test user subclass with $guard_name = 'web'
class TestUser extends User {
    protected $guard_name = 'web';
    protected $table = 'users';
}

$user = User::where('email', 'vasa.raviteja@gmail.com')->first();
$testUser = TestUser::where('email', 'vasa.raviteja@gmail.com')->first();

// Simulate authenticated guard = 'sanctum'
auth()->shouldUse('sanctum');

echo "With regular User model (no guard_name set):\n";
echo "  Active guard: " . auth()->getDefaultDriver() . "\n";
try {
    echo "  hasRole('team_lead'): " . ($user->hasRole('team_lead') ? 'TRUE' : 'FALSE') . "\n";
} catch (\Exception $e) {
    echo "  Error: " . $e->getMessage() . "\n";
}

echo "\nWith TestUser model (guard_name = 'web'):\n";
try {
    echo "  hasRole('team_lead'): " . ($testUser->hasRole('team_lead') ? 'TRUE' : 'FALSE') . "\n";
} catch (\Exception $e) {
    echo "  Error: " . $e->getMessage() . "\n";
}

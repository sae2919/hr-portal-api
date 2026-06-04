<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    Illuminate\Http\Request::capture()
);

use App\Models\User;

$user = User::where('email', 'vasa.raviteja@gmail.com')->first();
if (!$user) {
    die("User not found\n");
}

// Generate a token for testing
$token = $user->createToken('test_token')->plainTextToken;
echo "Generated token: $token\n";

// Perform an internal request to /api/v1/employees
$request = Illuminate\Http\Request::create('/api/v1/employees', 'GET');
$request->headers->set('Authorization', 'Bearer ' . $token);
$request->headers->set('Accept', 'application/json');

$response = $app->handle($request);
echo "Response status: " . $response->getStatusCode() . "\n";
echo "Response body:\n";
$content = json_decode($response->getContent(), true);
if (isset($content['data'])) {
    echo "Found " . count($content['data']) . " employees in response:\n";
    foreach ($content['data'] as $emp) {
        echo "  - {$emp['full_name']} (ID: {$emp['id']}), Email: {$emp['email']}\n";
    }
} else {
    print_r($content);
}

// Clean up token
$user->tokens()->where('name', 'test_token')->delete();

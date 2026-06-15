<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$email = $argv[1] ?? 'sshakthi507@gmail.com';
echo "=== Testing for $email ===\n";

$user = User::where('email', $email)->first();
if (!$user) {
    die("User not found\n");
}

// Generate token
$token = $user->createToken('test_token')->plainTextToken;

// Request workspace stats
$request = Illuminate\Http\Request::create('/api/v1/workspace/stats', 'GET');
$request->headers->set('Authorization', 'Bearer ' . $token);
$request->headers->set('Accept', 'application/json');

$response = $app->handle($request);
echo "Response status: " . $response->getStatusCode() . "\n";
echo "Response body:\n";
$content = json_decode($response->getContent(), true);
print_r($content);

// Clean up token
$user->tokens()->where('name', 'test_token')->delete();

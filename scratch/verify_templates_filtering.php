<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Boot the kernel
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\MailTemplate;

// Find an admin/super admin user
$user = User::where('role', 'super admin')->orWhere('role', 'admin')->first();
if (!$user) {
    die("Admin/Super Admin user not found in the DB. Cannot verify.\n");
}

// Generate token
$token = $user->createToken('test_token')->plainTextToken;
echo "Generated token: $token\n";

$types = ['mail', 'payslip', 'offer_joining', 'exit_relieving'];

foreach ($types as $type) {
    echo "\nTesting type: '$type'\n";
    $request = Illuminate\Http\Request::create('/api/v1/mail-templates', 'GET', ['type' => $type]);
    $request->headers->set('Authorization', 'Bearer ' . $token);
    $request->headers->set('Accept', 'application/json');

    $response = $app->handle($request);
    echo "Response status: " . $response->getStatusCode() . "\n";
    
    $content = json_decode($response->getContent(), true);
    if (is_array($content)) {
        echo "Found " . count($content) . " templates:\n";
        foreach ($content as $tmpl) {
            echo "  - Name: '{$tmpl['template_name']}', Type: '{$tmpl['type']}', Subject: '{$tmpl['subject']}'\n";
            if ($tmpl['type'] !== $type) {
                echo "    WARNING: Template type mismatch! Expected '$type', got '{$tmpl['type']}'\n";
            }
        }
    } else {
        echo "Response was not a valid JSON array.\n";
        print_r($content);
    }
}

// Clean up
$user->tokens()->where('name', 'test_token')->delete();
echo "\nCleaned up test token.\n";

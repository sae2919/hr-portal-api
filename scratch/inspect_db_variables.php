<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MailTemplate;

$templates = MailTemplate::whereIn('template_name', [
    'free_internship_offer_letter',
    'paid_internship_offer_letter',
    'full_time_offer_letter'
])->get();

foreach ($templates as $t) {
    echo "========================================\n";
    echo "Template Name: {$t->template_name}\n";
    echo "========================================\n";
    
    // Search for any curly brace patterns
    preg_match_all('/\{+[^{}]+\}+/', $t->body, $matches);
    if (!empty($matches[0])) {
        echo "Found placeholders:\n";
        foreach (array_unique($matches[0]) as $match) {
            echo "  - {$match}\n";
        }
    } else {
        echo "No placeholders found.\n";
    }
}

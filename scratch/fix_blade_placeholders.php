<?php
$file = __DIR__ . '/../database/seeders/MailTemplateSeeder.php';
if (!file_exists($file)) {
    die("Seeder file not found.\n");
}
$code = file_get_contents($file);

// Replace in seeder file
$placeholders = ['candidate_name', 'position', 'joining_date', 'duration', 'stipend'];
$replaced = 0;

foreach ($placeholders as $ph) {
    // Search for {{placeholder}} and replace with {{$placeholder}}
    $search = '{{' . $ph . '}}';
    $replace = '{{$' . $ph . '}}';
    
    // Also handle cases with spaces: {{ placeholder }} -> {{ $placeholder }}
    $searchSpaced = '{{\s*' . $ph . '\s*}}';
    
    // We can use preg_replace to handle spaces safely
    $pattern = '/{{\s*' . preg_quote($ph, '/') . '\s*}}/';
    $code = preg_replace($pattern, '{{$' . $ph . '}}', $code, -1, $count);
    $replaced += $count;
    echo "Replaced pattern for '$ph' $count times.\n";
}

if ($replaced > 0) {
    file_put_contents($file, $code);
    echo "Successfully updated MailTemplateSeeder.php with Blade variable prefix ($)!\n";
    
    // Re-seed the database
    require __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    echo "Running db:seed...\n";
    \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => 'MailTemplateSeeder']);
    echo "Database seeded successfully with fixed placeholders!\n";
} else {
    echo "No placeholders found to update.\n";
}

<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Boot the kernel
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$imgPath = 'C:/Users/91756/.gemini/antigravity-ide/brain/fd53d1b2-91c3-4306-a86e-f9a927dbfcf4/media__1780998570064.jpg';
if (!file_exists($imgPath)) {
    die("Image file not found at $imgPath\n");
}
$data = file_get_contents($imgPath);
$base64 = base64_encode($data);

$seederPath = __DIR__ . '/../database/seeders/MailTemplateSeeder.php';
if (!file_exists($seederPath)) {
    die("Seeder file not found at $seederPath\n");
}
$code = file_get_contents($seederPath);

// Locate all base64 tags
if (preg_match_all('/data:image\/png;base64,([a-zA-Z0-9+\/=\s\r\n]+)/i', $code, $matches)) {
    echo "Found " . count($matches[0]) . " base64 image tags.\n";
    $replacedCount = 0;
    foreach ($matches[1] as $index => $oldBase64Raw) {
        $oldBase64 = trim(preg_replace('/\s+/', '', $oldBase64Raw));
        if (str_starts_with($oldBase64, 'iVBORw0KGgoAAAANSUhEUgAAAeAAAACVCAIAAAAPLr4E')) {
            echo "-> Replacing occurrence $index...\n";
            $oldTag = "data:image/png;base64," . $oldBase64Raw;
            $newTag = "data:image/jpeg;base64," . $base64;
            $code = str_replace($oldTag, $newTag, $code);
            $replacedCount++;
        }
    }
    if ($replacedCount > 0) {
        file_put_contents($seederPath, $code);
        echo "Successfully updated $replacedCount templates in MailTemplateSeeder.php!\n";
        
        // Seed the database
        echo "Running db:seed...\n";
        \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => 'MailTemplateSeeder']);
        echo "Database seeded successfully!\n";
    } else {
        echo "No matching signature base64 found to replace.\n";
    }
} else {
    echo "No base64 image tags found.\n";
}

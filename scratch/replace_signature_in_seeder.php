<?php
$seederPath = __DIR__ . '/../database/seeders/MailTemplateSeeder.php';
if (!file_exists($seederPath)) {
    die("Seeder file not found at $seederPath\n");
}
$code = file_get_contents($seederPath);

$sigFile = __DIR__ . '/signature_base64.txt';
if (!file_exists($sigFile)) {
    die("signature_base64.txt not found!\n");
}
$newSig = file_get_contents($sigFile);

// Match the base64 signatures
if (preg_match_all('/src="data:image\/png;base64,([a-zA-Z0-9+\/=\s\r\n]+)"/i', $code, $matches)) {
    echo "Found " . count($matches[0]) . " base64 image tags in the file.\n";
    $replacedCount = 0;
    foreach ($matches[1] as $index => $oldBase64Raw) {
        $oldBase64 = trim(preg_replace('/\s+/', '', $oldBase64Raw));
        echo "Match $index: Length: " . strlen($oldBase64) . ", Start: " . substr($oldBase64, 0, 30) . "\n";
        
        // Check if it starts with the signature prefix
        if (str_starts_with($oldBase64, 'iVBORw0KGgoAAAANSUhEUgAAAeAAAACVCAIAAAAPLr4E')) {
            echo "-> Found signature matching pattern. Replacing...\n";
            $code = str_replace($oldBase64Raw, $newSig, $code);
            $replacedCount++;
        }
    }
    if ($replacedCount > 0) {
        file_put_contents($seederPath, $code);
        echo "Successfully replaced $replacedCount occurrence(s) of the signature in MailTemplateSeeder.php!\n";
    } else {
        echo "No matching old signature base64 found to replace.\n";
    }
} else {
    echo "No base64 image tags found.\n";
}

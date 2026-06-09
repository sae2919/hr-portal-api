<?php
$imgPath = 'C:/Users/91756/.gemini/antigravity-ide/brain/fd53d1b2-91c3-4306-a86e-f9a927dbfcf4/media__1780998279597.png';
if (!file_exists($imgPath)) {
    die("Image file not found at $imgPath\n");
}
$data = file_get_contents($imgPath);
$base64 = base64_encode($data);
echo "Length: " . strlen($base64) . "\n";
echo "Base64 snippet: " . substr($base64, 0, 100) . "\n";
file_put_contents(__DIR__ . '/signature_base64.txt', $base64);
echo "Saved base64 to signature_base64.txt\n";

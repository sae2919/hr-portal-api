<?php
$file = __DIR__ . '/../database/seeders/MailTemplateSeeder.php';
$code = file_get_contents($file);

// Find positions
$posFull = strpos($code, "'template_name' => 'full_time_offer_letter'");
if ($posFull === false) {
    die("Could not find full_time_offer_letter\n");
}

$posBodyStart = strpos($code, "'body' => '", $posFull);
$posBodyEnd = strpos($code, "',\n                'style' => '", $posBodyStart);
$bodyContent = substr($code, $posBodyStart + 11, $posBodyEnd - ($posBodyStart + 11));

$posStyleStart = strpos($code, "'style' => '", $posBodyEnd);
$posStyleEnd = strpos($code, "',\n                'active_status' => 1,", $posStyleStart);
$styleContent = substr($code, $posStyleStart + 12, $posStyleEnd - ($posStyleStart + 12));

echo "Extracted Body Length: " . strlen($bodyContent) . "\n";
echo "Extracted Style Length: " . strlen($styleContent) . "\n";

// Let's look at the target string
$target = "            [
                'template_name' => 'paid_internship_offer_letter',
                'type' => 'offer_joining',
                'subject' => 'Internship Offer Letter – {{candidate_name}}',
                'body' => '',
                'style' => '',
                'active_status' => 1,
            ],";

$replacement = "            [
                'template_name' => 'paid_internship_offer_letter',
                'type' => 'offer_joining',
                'subject' => 'Internship Offer Letter – {{candidate_name}}',
                'body' => '" . $bodyContent . "',
                'style' => '" . $styleContent . "',
                'active_status' => 1,
            ],";

$newCode = str_replace($target, $replacement, $code);
if ($newCode === $code) {
    echo "Warning: Target string not found or replacement failed!\n";
    
    // Let's try matching with different line endings (\r\n)
    $target2 = str_replace("\n", "\r\n", $target);
    $newCode = str_replace($target2, $replacement, $code);
    if ($newCode === $code) {
        echo "Failed with \\r\\n as well.\n";
    } else {
        echo "Success using \\r\\n!\n";
        file_put_contents($file, $newCode);
    }
} else {
    echo "Success!\n";
    file_put_contents($file, $newCode);
}

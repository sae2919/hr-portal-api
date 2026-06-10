<?php

$seederFile = __DIR__ . '/../database/seeders/MailTemplateSeeder.php';
$headerFile = __DIR__ . '/header_base64.txt';
$footerFile = __DIR__ . '/footer_base64.txt';

if (!file_exists($seederFile)) {
    die("Seeder file not found: $seederFile\n");
}
if (!file_exists($headerFile) || !file_exists($footerFile)) {
    die("Base64 asset files not found.\n");
}

$headerBase64 = trim(file_get_contents($headerFile));
$footerBase64 = trim(file_get_contents($footerFile));

$content = file_get_contents($seederFile);

$templates = [
    'free_internship_offer_letter',
    'paid_internship_offer_letter',
    'full_time_offer_letter'
];

foreach ($templates as $templateName) {
    // Locate the template name
    $pos = strpos($content, "'template_name' => '{$templateName}'");
    if ($pos === false) {
        die("Template {$templateName} not found in seeder.\n");
    }
    
    // 1. Update Body (Insert header-bg and footer-bg at the start of the body string)
    $bodyPos = strpos($content, "'body' => '", $pos);
    if ($bodyPos === false) {
        die("Body key not found for {$templateName}.\n");
    }
    
    $insertPos = $bodyPos + strlen("'body' => '");
    
    // Check if already updated
    if (strpos($content, "class=\"header-bg\"", $insertPos) !== false && strpos($content, "class=\"header-bg\"", $insertPos) < $insertPos + 300) {
        echo "Template {$templateName} body already has background containers.\n";
    } else {
        $headerDiv = '<div class="header-bg"><img src="data:image/png;base64,' . $headerBase64 . '" style="width: 100%; height: 100%; display: block;" /></div>' . "\n";
        $footerDiv = '    <div class="footer-bg"><img src="data:image/png;base64,' . $footerBase64 . '" style="width: 100%; height: 100%; display: block;" /></div>' . "\n" . '    ';
        $content = substr_replace($content, $headerDiv . $footerDiv, $insertPos, 0);
        echo "Template {$templateName} body updated successfully.\n";
    }
    
    // 2. Update Style (Insert CSS rules after the first } after style start)
    // Find style key from the template position (since position shifted, search from $pos again)
    $stylePos = strpos($content, "'style' => '", $pos);
    if ($stylePos === false) {
        die("Style key not found for {$templateName}.\n");
    }
    
    $styleInsertPos = $stylePos + strlen("'style' => '");
    
    // Check if already updated
    if (strpos($content, ".header-bg {", $styleInsertPos) !== false && strpos($content, ".header-bg {", $styleInsertPos) < $styleInsertPos + 800) {
        echo "Template {$templateName} style already has background CSS.\n";
    } else {
        $bracePos = strpos($content, "}", $styleInsertPos);
        if ($bracePos === false) {
            die("Closing brace not found in style for {$templateName}.\n");
        }
        
        $styleCss = "\n        .header-bg {
            position: fixed;
            top: -125px;
            left: -37pt;
            right: -37pt;
            height: 22px;
            z-index: -100;
        }
        .footer-bg {
            position: fixed;
            bottom: -60px;
            left: -37pt;
            right: -37pt;
            height: 28.4px;
            z-index: -100;
        }";
        
        $content = substr_replace($content, $styleCss, $bracePos + 1, 0);
        echo "Template {$templateName} style updated successfully.\n";
    }
}

file_put_contents($seederFile, $content);
echo "All updates written to $seederFile successfully.\n";

<?php

$bladeFile = __DIR__ . '/../resources/views/pdf/offer-letter.blade.php';
$headerFile = __DIR__ . '/header_base64.txt';
$footerFile = __DIR__ . '/footer_base64.txt';

if (!file_exists($bladeFile)) {
    die("Blade file not found: $bladeFile\n");
}
if (!file_exists($headerFile) || !file_exists($footerFile)) {
    die("Base64 asset files not found.\n");
}

$headerBase64 = trim(file_get_contents($headerFile));
$footerBase64 = trim(file_get_contents($footerFile));

$content = file_get_contents($bladeFile);

// Check if already updated
if (strpos($content, "class=\"header-bg\"") !== false) {
    die("Blade file already contains header-bg. Skipping update.\n");
}

// 1. Update all 3 <body> tags
$headerDiv = '<div class="header-bg"><img src="data:image/png;base64,' . $headerBase64 . '" style="width: 100%; height: 100%; display: block;" /></div>' . "\n";
$footerDiv = '    <div class="footer-bg"><img src="data:image/png;base64,' . $footerBase64 . '" style="width: 100%; height: 100%; display: block;" /></div>' . "\n" . '    ';
$bodyReplacement = "<body>\n\n    " . $headerDiv . $footerDiv;

$content = str_replace("<body>", $bodyReplacement, $content);

// 2. Add style rules for the first template (free_intern)
$pos1 = strpos($content, "@if(\$candidate->onboarding_type === 'free_intern')");
$styleStart1 = strpos($content, "<style>", $pos1);
$bracePos1 = strpos($content, "}", $styleStart1);
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
$content = substr_replace($content, $styleCss, $bracePos1 + 1, 0);

// 3. Add style rules for the second template (intern)
$pos2 = strpos($content, "@elseif(\$candidate->onboarding_type === 'intern')");
$styleStart2 = strpos($content, "<style>", $pos2);
$bracePos2 = strpos($content, "}", $styleStart2);
$content = substr_replace($content, $styleCss, $bracePos2 + 1, 0);

// 4. Add @page and style rules for the third template (full time)
$pos3 = strpos($content, "{{-- ═══════════════════════════════════════════════════════════════\n     FULL-TIME EMPLOYMENT OFFER LETTER");
if ($pos3 === false) {
    $pos3 = strpos($content, "@else");
}
$styleStart3 = strpos($content, "<style>", $pos3);
$styleInsertPos3 = $styleStart3 + strlen("<style>");

$fullTimeStyle = "\n        @page {
            margin: 125px 37pt 60px 37pt;
        }
        .header-bg {
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

$content = substr_replace($content, $fullTimeStyle, $styleInsertPos3, 0);

file_put_contents($bladeFile, $content);
echo "Successfully updated offer-letter.blade.php.\n";

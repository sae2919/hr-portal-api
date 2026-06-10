<?php

$seederFile = __DIR__ . '/../database/seeders/MailTemplateSeeder.php';
$bladeFile = __DIR__ . '/../resources/views/pdf/offer-letter.blade.php';

// 1. Process Seeder File
if (file_exists($seederFile)) {
    $content = file_get_contents($seederFile);
    
    // Replace font family names
    $content = str_replace("font-family: \\'DejaVu Sans\\';", "font-family: \\'Inter\\', sans-serif;", $content);
    $content = str_replace("font-family: \\'DejaVu Sans\\', sans-serif;", "font-family: \\'Inter\\', sans-serif;", $content);
    
    // Inject Montserrat to section title
    $content = str_replace(".section-title {", ".section-title {\n            font-family: \\'Montserrat\\', sans-serif;", $content);
    
    // Inject @import url for Google Fonts into the style of our three templates
    $templates = [
        'free_internship_offer_letter',
        'paid_internship_offer_letter',
        'full_time_offer_letter'
    ];
    
    foreach ($templates as $templateName) {
        $pos = strpos($content, "'template_name' => '{$templateName}'");
        if ($pos !== false) {
            $stylePos = strpos($content, "'style' => '", $pos);
            if ($stylePos !== false) {
                $styleInsertPos = $stylePos + strlen("'style' => '");
                
                // Only insert if not already imported
                if (strpos($content, "fonts.googleapis.com", $styleInsertPos) === false || strpos($content, "fonts.googleapis.com", $styleInsertPos) > $styleInsertPos + 200) {
                    $importUrl = "@import url(\\'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@700&display=swap\\');\n        ";
                    $content = substr_replace($content, $importUrl, $styleInsertPos, 0);
                    echo "Injected Google Fonts import into seeder template: {$templateName}\n";
                }
            }
        }
    }
    
    file_put_contents($seederFile, $content);
    echo "Updated seeder file fonts.\n";
}

// 2. Process Blade File
if (file_exists($bladeFile)) {
    $content = file_get_contents($bladeFile);
    
    $content = str_replace("font-family: 'DejaVu Sans';", "font-family: 'Inter', sans-serif;", $content);
    $content = str_replace("font-family: 'DejaVu Sans', sans-serif;", "font-family: 'Inter', sans-serif;", $content);
    $content = str_replace("font-family: DejaVu Sans, sans-serif;", "font-family: 'Inter', sans-serif;", $content);
    
    $content = str_replace(".section-title {", ".section-title {\n            font-family: 'Montserrat', sans-serif;", $content);
    
    // Inject @import url into each of the three style blocks
    $styleStarts = [];
    $offset = 0;
    while (($pos = strpos($content, "<style>", $offset)) !== false) {
        $styleStarts[] = $pos;
        $offset = $pos + 1;
    }
    
    // Reverse order so insertion offsets don't break subsequent replacements
    $styleStarts = array_reverse($styleStarts);
    foreach ($styleStarts as $styleStart) {
        $insertPos = $styleStart + strlen("<style>");
        if (strpos($content, "fonts.googleapis.com", $insertPos) === false || strpos($content, "fonts.googleapis.com", $insertPos) > $insertPos + 100) {
            $importUrl = "\n        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@700&display=swap');";
            $content = substr_replace($content, $importUrl, $insertPos, 0);
        }
    }
    
    file_put_contents($bladeFile, $content);
    echo "Updated blade file fonts.\n";
}

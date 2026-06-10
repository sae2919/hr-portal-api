<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Barryvdh\DomPDF\Facade\Pdf;

$html = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <style>
        @page {
            margin: 125px 37pt 60px 37pt;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11pt;
            color: #000000;
            line-height: 1.45;
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
        }
        .header {
            position: fixed;
            top: -100px;
            left: 0;
            right: 0;
            height: 80px;
            border-bottom: 1.5pt solid #28326e;
            padding-bottom: 8px;
        }
        .header table { width: 100%; border-collapse: collapse; }
        .header td   { padding: 0; vertical-align: top; }
        .company-address {
            font-size: 10pt;
            color: #28326e;
            text-align: right;
            line-height: 1.35;
        }
        .company-address a { color: #28326e; text-decoration: none; font-weight: normal; }
    </style>
</head>
<body>

    <div class='header-bg'>
        <img src='data:image/png;base64,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' style='width: 100%; height: 100%; display: block;' />
    </div>

    <div class='footer-bg'>
        <img src='data:image/png;base64,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' style='width: 100%; height: 100%; display: block;' />
    </div>

    <div class='header'>
        <table>
            <tr>
                <td style='text-align: left;'>
                    <div style='font-size: 16pt; font-weight: bold; color: #28326e;'>Techsprout Logo</div>
                </td>
                <td class='company-address'>
                    <span style='font-weight: bold; display: block; margin-bottom: 3px;'>Techsprout AI Labs Pvt. Ltd</span>
                    501, Manjeera Majestic Commercial,<br>
                    JNTU Road, KPHB, Hyderabad.<br>
                    <a href='https://www.techsprout.ai'>www.techsprout.ai</a>
                </td>
            </tr>
        </table>
    </div>

    <div style='margin-top: 20px;'>
        <h3>DEAR TEST CANDIDATE</h3>
        <p>This is a test document to verify the SVG header and footer rendering in Dompdf.</p>
        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
    </div>

</body>
</html>
";

$pdf = PDF::loadHTML($html);
$destPath = __DIR__ . '/test_svg_dompdf.pdf';
file_put_contents($destPath, $pdf->output());
echo "PDF generated: $destPath\n";

// Render to PNG using Python script
shell_exec("python -c \"import fitz; doc=fitz.open(r'$destPath'); page=doc[0]; pix=page.get_pixmap(dpi=150); pix.save(r'" . __DIR__ . "/test_svg_dompdf.png')\"");
echo "PNG generated: " . __DIR__ . "/test_svg_dompdf.png\n";

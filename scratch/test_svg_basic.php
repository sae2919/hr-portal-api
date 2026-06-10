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
    <style>
        body { font-family: sans-serif; }
    </style>
</head>
<body>
    <h1>Basic SVG Render Test</h1>
    <svg width='200' height='200'>
        <rect x='10' y='10' width='180' height='180' fill='#0496ff' />
        <circle cx='100' cy='100' r='50' fill='#28326e' />
    </svg>
</body>
</html>
";

$pdf = PDF::loadHTML($html);
$destPath = __DIR__ . '/test_svg_basic.pdf';
file_put_contents($destPath, $pdf->output());
echo "PDF generated: $destPath\n";

shell_exec("python -c \"import fitz; doc=fitz.open(r'$destPath'); page=doc[0]; pix=page.get_pixmap(dpi=150); pix.save(r'" . __DIR__ . "/test_svg_basic.png')\"");
echo "PNG generated: " . __DIR__ . "/test_svg_basic.png\n";

<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$templates = \App\Models\MailTemplate::all();
foreach ($templates as $t) {
    echo "ID: {$t->id}, Name: {$t->template_name}, PDF Path: {$t->pdf_path}\n";
}

<?php
$logFile = __DIR__ . '/../storage/logs/laravel.log';
if (!file_exists($logFile)) {
    echo "Log file does not exist.\n";
    exit;
}

$lines = file($logFile);
$lastLines = array_slice($lines, -50);
echo implode("", $lastLines);

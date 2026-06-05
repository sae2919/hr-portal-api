<?php

$ports = [3000, 8000, 3306];

foreach ($ports as $port) {
    $connection = @fsockopen('127.0.0.1', $port, $errno, $errstr, 2);
    if (is_resource($connection)) {
        echo "Port {$port} is OPEN\n";
        fclose($connection);
    } else {
        echo "Port {$port} is CLOSED ({$errstr})\n";
    }
}

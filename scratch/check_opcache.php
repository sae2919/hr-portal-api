<?php
echo "opcache.enable: " . ini_get('opcache.enable') . "\n";
echo "opcache.enable_cli: " . ini_get('opcache.enable_cli') . "\n";
echo "opcache.validate_timestamps: " . ini_get('opcache.validate_timestamps') . "\n";
if (function_exists('opcache_get_status')) {
    $status = opcache_get_status();
    echo "OPcache is active: " . ($status ? 'YES' : 'NO') . "\n";
} else {
    echo "opcache_get_status function does not exist.\n";
}

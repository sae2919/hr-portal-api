<?php
$ch = curl_init('http://localhost:3000/logo.png');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, false);
$response = curl_exec($ch);
$info = curl_getinfo($ch);
curl_close($ch);

echo "HTTP Code: " . $info['http_code'] . "\n";
echo "Content-Type: " . $info['content_type'] . "\n";
echo "Content-Length: " . $info['download_content_length'] . "\n";
echo "Headers:\n" . substr($response, 0, $info['header_size']) . "\n";

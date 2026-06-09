<?php
$ch = curl_init('http://localhost:3000/payroll');
// Send a dummy cookie to pass middleware auth if needed, or see if it redirects
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, 'hr_token=dummy_token_to_pass_middleware');
$response = curl_exec($ch);
curl_close($ch);

if (str_contains($response, 'logo-brand.png')) {
    echo "Page HTML contains 'logo-brand.png'!\n";
} else if (str_contains($response, 'logo.png')) {
    echo "Page HTML STILL contains 'logo.png'!\n";
} else {
    echo "Neither found. Page might be client-side rendered.\n";
}

<?php
/**
 * Test HTTP login against the local Laravel server for staff EPSS9790
 */

$baseUrl = 'http://127.0.0.1:8000';
$cookieFile = sys_get_temp_dir() . '/cookie_epss9790.txt';
if (file_exists($cookieFile)) unlink($cookieFile);

echo "=== 1. Fetching Login Page & CSRF Token ===\n";
$ch = curl_init("{$baseUrl}/login");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEJAR      => $cookieFile,
    CURLOPT_COOKIEFILE     => $cookieFile,
    CURLOPT_FOLLOWLOCATION => true,
]);
$html = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if (!preg_match('/name="_token" value="([^"]+)"/', $html, $m)) {
    echo "Failed to extract CSRF token! HTTP Code: $httpCode\n";
    exit(1);
}
$csrfToken = $m[1];
echo "Successfully fetched login page. HTTP $httpCode. CSRF token acquired.\n";

echo "\n=== 2. Submitting Credentials for EPSS9790 (Password: '15091998') ===\n";
$ch = curl_init("{$baseUrl}/login");
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query([
        '_token'   => $csrfToken,
        'email'    => 'EPSS9790',
        'password' => '15091998',
        'remember' => '1',
    ]),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEJAR      => $cookieFile,
    CURLOPT_COOKIEFILE     => $cookieFile,
    CURLOPT_FOLLOWLOCATION => false, // Do not follow immediately so we can inspect redirect
]);
$resp = curl_exec($ch);
$redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Login POST HTTP Response Code: $code\n";
echo "Redirect Target: $redirectUrl\n";

if ($code === 302) {
    if (str_contains($redirectUrl, 'force-change') || str_contains($redirectUrl, 'password')) {
        echo "SUCCESS: User EPSS9790 was authenticated and redirected to forced password change (as expected for first-time staff login with DOB temporary password)!\n";
    } elseif (str_contains($redirectUrl, 'dashboard')) {
        echo "SUCCESS: User EPSS9790 was authenticated and redirected to Dashboard!\n";
    } else {
        echo "Redirected to: $redirectUrl\n";
    }
} else {
    echo "Login failed! Response snippet:\n" . substr($resp, 0, 500) . "\n";
}

echo "\n=== 3. Following Redirect to Protected Route ===\n";
$target = $redirectUrl ?: "{$baseUrl}/dashboard";
$ch = curl_init($target);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEFILE     => $cookieFile,
    CURLOPT_FOLLOWLOCATION => true,
]);
$body = curl_exec($ch);
$finalCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
curl_close($ch);

echo "Followed to: $effectiveUrl (HTTP $finalCode)\n";
if (str_contains($body, 'Change Temporary Password') || str_contains($body, 'Temporary credentials detected')) {
    echo "PASS: Force password change screen loaded with session warning!\n";
} elseif (str_contains($body, 'Dashboard') || str_contains($body, 'Jeyawin')) {
    echo "PASS: User portal dashboard accessed!\n";
} else {
    echo "Body title / snippet: " . substr(strip_tags($body), 0, 300) . "\n";
}

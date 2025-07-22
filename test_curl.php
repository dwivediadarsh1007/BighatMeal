<?php
// Test cURL functionality
echo '<h1>Testing cURL</h1>';

if (function_exists('curl_version')) {
    echo '<p style="color: green;">✓ cURL is enabled</p>';
    
    // Test a simple cURL request
    $ch = curl_init('https://www.google.com');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Only for testing!
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($response !== false) {
        echo '<p style="color: green;">✓ Successfully made a request to Google. Status code: ' . $httpCode . '</p>';
    } else {
        echo '<p style="color: red;">✗ cURL request failed: ' . htmlspecialchars($error) . '</p>';
    }
} else {
    echo '<p style="color: red;">✗ cURL is not enabled in your PHP installation</p>';
}
?>

<h2>PHP Info</h2>
<?php phpinfo(); ?>

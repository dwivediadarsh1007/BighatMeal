<?php
// Display all errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if error log file exists
$error_log = __DIR__ . '/logs/php_errors.log';

if (file_exists($error_log)) {
    echo "<h3>Error Log Contents:</h3>";
    echo "<pre>" . htmlspecialchars(file_get_contents($error_log)) . "</pre>";
} else {
    echo "<p>No error log found at: " . htmlspecialchars($error_log) . "</p>";
}

// Test basic PHP functionality
echo "<h3>PHP Version: " . phpversion() . "</h3>";

// Test database connection
try {
    require_once 'config.php';
    echo "<p style='color:green;'>✓ Database connection successful!</p>";
} catch (PDOException $e) {
    echo "<p style='color:red;'>✗ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test PHPMailer
try {
    require_once 'includes/send_email.php';
    echo "<p style='color:green;'>✓ PHPMailer loaded successfully!</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>✗ PHPMailer error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test file permissions
$test_file = __DIR__ . '/logs/test_write.txt';
if (@file_put_contents($test_file, 'test')) {
    echo "<p style='color:green;'>✓ Write permissions are working in the logs directory</p>";
    unlink($test_file);
} else {
    echo "<p style='color:red;'>✗ Cannot write to logs directory. Please check permissions.</p>";
}

// Show loaded PHP modules
echo "<h3>Loaded PHP Modules:</h3>";
echo "<pre>" . shell_exec('php -m') . "</pre>";

// Show PHP info
echo "<h3>PHP Info:</h3>";
ob_start();
phpinfo();
$phpinfo = ob_get_clean();
echo $phpinfo;
?>

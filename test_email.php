<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set('Asia/Kolkata');

// Include required files
require_once 'config.php';
require_once 'includes/mail_config.php';
require_once 'includes/send_email.php';

// Test email settings
$test_email = 'singhsunita4820@gmail.com';
$test_name = 'Test User';
$subject = 'Test Email from BighatMeal';
$body = '<h1>Test Email</h1><p>This is a test email from BighatMeal.</p>';

try {
    echo "<h2>Testing Email Functionality</h2>";
    echo "<p>Sending test email to: " . htmlspecialchars($test_email) . "</p>";
    
    // Test PHPMailer directly
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = MAIL_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = MAIL_USERNAME;
    $mail->Password = MAIL_PASSWORD;
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = MAIL_PORT;
    
    // Recipients
    $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
    $mail->addAddress($test_email, $test_name);
    
    // Content
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $body;
    $mail->AltBody = strip_tags($body);
    
    $mail->send();
    echo "<p style='color:green;'>✓ Test email sent successfully!</p>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>✗ Email sending failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    
    // Show more detailed error information
    echo "<h3>Debug Info:</h3>";
    echo "<pre>Error: " . htmlspecialchars($e->getMessage()) . "\n";
    echo "File: " . $e->getFile() . " (Line: " . $e->getLine() . ")\n";
    echo "Code: " . $e->getCode() . "\n";
    echo "Trace: " . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

// Show PHP info
echo "<h3>PHP Info:</h3>";
ob_start();
phpinfo();
$phpinfo = ob_get_clean();
$phpinfo = preg_replace('%^.*<body>(.*)</body>.*$%ms', '$1', $phpinfo);
echo $phpinfo;
?>

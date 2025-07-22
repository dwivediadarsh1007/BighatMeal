<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Test database connection
try {
    require_once 'config.php';
    echo "<p>Database connection successful!</p>";
    
    // Test PHPMailer include
    require_once 'includes/send_email.php';
    echo "<p>PHPMailer included successfully!</p>";
    
    // Test email configuration
    require_once 'includes/mail_config.php';
    echo "<p>Email configuration loaded successfully!</p>";
    
    // Test PHPMailer instantiation
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    echo "<p>PHPMailer instantiated successfully!</p>";
    
} catch (PDOException $e) {
    echo "<p style='color:red;'>Database Error: " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}

// Show PHP info
phpinfo();
?>

<?php
// Email Configuration - Gmail SMTP
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);

// Gmail SMTP Configuration
define('MAIL_USERNAME', 'ad5397608@gmail.com');  // Your Gmail address
define('MAIL_PASSWORD', 'tsvu xeeh bpbx svzo');        // Your new App Password
define('MAIL_FROM_EMAIL', 'ad5397608@gmail.com'); // Sender email
define('MAIL_FROM_NAME', 'BighatMeal');                // Sender name

// For other SMTP servers (like Mailtrap for testing)
/*
define('MAIL_HOST', 'smtp.mailtrap.io');
define('MAIL_PORT', 2525);
define('MAIL_USERNAME', 'your-mailtrap-username');
define('MAIL_PASSWORD', 'your-mailtrap-password');
define('MAIL_FROM_EMAIL', 'noreply@bighatmeal.com');
define('MAIL_FROM_NAME', 'BighatMeal');
*/

// Enable verbose debug output (set to 0 in production)
define('MAIL_DEBUG', 0); // 0 = off, 1 = client messages, 2 = client and server messages

// Set character encoding
define('MAIL_CHARSET', 'UTF-8');

// Set email priority (1 = High, 3 = Normal, 5 = Low)
define('MAIL_PRIORITY', 3);

// Set word wrap
define('MAIL_WORD_WRAP', 78);

// Enable SMTP authentication
define('MAIL_SMTP_AUTH', true);

// Enable TLS encryption
define('MAIL_SMTP_SECURE', 'tls');

// Enable SMTP keep alive
define('MAIL_SMTP_KEEPALIVE', true);

// Set SMTP timeout (seconds)
define('MAIL_SMTP_TIMEOUT', 30);
?>

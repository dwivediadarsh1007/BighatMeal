<?php
// Include PHPMailer files directly
require_once __DIR__ . '/../lib/PHPMailer.php';
require_once __DIR__ . '/../lib/SMTP.php';
require_once __DIR__ . '/../lib/Exception.php';

// Use PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Send an email using PHPMailer
 * 
 * @param string $to_email Recipient email address
 * @param string $to_name Recipient name
 * @param string $subject Email subject
 * @param string $body Email body (HTML)
 * @param string $alt_body Plain text version of the email
 * @return array Array with 'status' (boolean) and 'message' (string)
 */
function sendEmail($to_email, $to_name, $subject, $body, $alt_body = '') {
    // Include mail configuration
    require_once 'mail_config.php';
    
    // Create a new PHPMailer instance
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->SMTPDebug = MAIL_DEBUG;                      // Enable verbose debug output
        $mail->isSMTP();                                    // Send using SMTP
        $mail->Host       = MAIL_HOST;                      // Set the SMTP server to send through
        $mail->SMTPAuth   = MAIL_SMTP_AUTH;                 // Enable SMTP authentication
        $mail->Username   = MAIL_USERNAME;                  // SMTP username
        $mail->Password   = MAIL_PASSWORD;                  // SMTP password
        $mail->SMTPSecure = MAIL_SMTP_SECURE;               // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` also accepted
        $mail->Port       = MAIL_PORT;                      // TCP port to connect to
        $mail->CharSet    = MAIL_CHARSET;                   // Set email charset
        $mail->Priority   = MAIL_PRIORITY;                  // Set email priority
        $mail->WordWrap   = MAIL_WORD_WRAP;                 // Set word wrap
        $mail->SMTPKeepAlive = MAIL_SMTP_KEEPALIVE;         // Enable SMTP keep alive
        $mail->Timeout    = MAIL_SMTP_TIMEOUT;              // Set SMTP timeout
        
        // Recipients
        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        $mail->addAddress($to_email, $to_name);             // Add a recipient
        
        // Content
        $mail->isHTML(true);                                // Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = !empty($alt_body) ? $alt_body : strip_tags($body);
        
        // Send the email
        $mail->send();
        
        return [
            'status' => true,
            'message' => 'Email has been sent successfully.'
        ];
    } catch (Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        return [
            'status' => false,
            'message' => "Email could not be sent. Error: " . $mail->ErrorInfo
        ];
    }
}

/**
 * Send a password reset email
 * 
 * @param string $email User's email address
 * @param string $reset_link Password reset link
 * @param string $user_name User's name (optional)
 * @return array Array with 'status' (boolean) and 'message' (string)
 */
function sendPasswordResetEmail($email, $reset_link, $user_name = 'User') {
    $subject = 'Password Reset Request - BighatMeal';
    
    // HTML version of the email
    $body = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Password Reset</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #2e7d32; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background-color: #f9f9f9; }
            .button {
                display: inline-block; 
                padding: 10px 20px; 
                background-color: #2e7d32; 
                color: white; 
                text-decoration: none; 
                border-radius: 5px;
                margin: 15px 0;
            }
            .footer { 
                margin-top: 20px; 
                font-size: 12px; 
                color: #777; 
                text-align: center;
                padding: 10px;
                border-top: 1px solid #eee;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Password Reset Request</h2>
            </div>
            <div class='content'>
                <p>Hello " . htmlspecialchars($user_name) . ",</p>
                <p>We received a request to reset your password. Click the button below to set a new password:</p>
                
                <p style='text-align: center;'>
                    <a href='" . htmlspecialchars($reset_link) . "' class='button'>Reset Password</a>
                </p>
                
                <p>If you didn't request this, you can safely ignore this email. The password reset link is valid for 1 hour.</p>
                
                <p>Best regards,<br>The BighatMeal Team</p>
            </div>
            <div class='footer'>
                <p>© " . date('Y') . " BighatMeal. All rights reserved.</p>
                <p>This is an automated message, please do not reply directly to this email.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Plain text version of the email
    $alt_body = "
    Password Reset Request - BighatMeal
    
    Hello " . $user_name . ",
    
    We received a request to reset your password. Use the link below to set a new password:
    
    " . $reset_link . "
    
    If you didn't request this, you can safely ignore this email. The password reset link is valid for 1 hour.
    
    Best regards,
    The BighatMeal Team
    
    © " . date('Y') . " BighatMeal. All rights reserved.
    This is an automated message, please do not reply directly to this email.
    ";
    
    // Send the email
    return sendEmail($email, $user_name, $subject, $body, $alt_body);
}
?>

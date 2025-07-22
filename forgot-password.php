<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

// Test user credentials (for development only)
const TEST_EMAIL = 'test@example.com';
const TEST_PASSWORD = 'test123';

// Set timezone to match your server's timezone
date_default_timezone_set('Asia/Kolkata');

$message = '';
$emailSent = false;

// Debug: Log the start of the script
error_log("=== Forgot Password Script Started ===");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    error_log("Processing password reset request for email: " . $email);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        error_log("Invalid email format: " . $email);
    } else {
        try {
            // Check if it's the test email
            if ($email === TEST_EMAIL) {
                // Generate a reset token
                $resetToken = bin2hex(random_bytes(32));
                $resetExpiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                try {
                    // Store the token in the database
                    $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
                    $stmt->execute([$resetToken, $resetExpiry, TEST_EMAIL]);
                    
                    // Create reset link
                    $resetLink = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/Food/reset-password.php?token=$resetToken";
                    
                    // For testing, show the reset link on the page
                    $message = "<div class='alert alert-info'>
                        <h5>Test Account Information</h5>
                        <p>Email: " . htmlspecialchars(TEST_EMAIL) . "</p>
                        <p>Password Reset Link: <a href='$resetLink' target='_blank'>Click here to reset password</a></p>
                        <p class='text-muted small mt-2'>For testing purposes, the reset link is shown here. In production, this would be sent via email.</p>
                        <div class='mt-3'>
                            <a href='login.php' class='btn btn-primary me-2'>Go to Login</a>
                            <a href='$resetLink' class='btn btn-warning'>Reset Password</a>
                        </div>
                    </div>";
                    $emailSent = true;
                    
                    error_log("Generated reset link for test account: $resetLink");
                } catch (PDOException $e) {
                    $message = '<div class="alert alert-danger">Error generating reset link. Please try again.</div>';
                    error_log("Database error: " . $e->getMessage());
                }
                
                // Check if test user exists, if not create it
                try {
                    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
                    $stmt->execute([TEST_EMAIL]);
                    if (!$stmt->fetch()) {
                        $hashedPassword = password_hash(TEST_PASSWORD, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("INSERT INTO users (username, email, password, created_at) VALUES (?, ?, ?, NOW())");
                        $stmt->execute(['testuser', TEST_EMAIL, $hashedPassword]);
                        error_log("Created test user with email: " . TEST_EMAIL);
                    }
                } catch (PDOException $e) {
                    $message = 'An error occurred while setting up test account. Please try again later.';
                    error_log("Database error: " . $e->getMessage());
                }
            } else {
                // Generate a reset token
                $resetToken = bin2hex(random_bytes(32));
                $resetExpiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                try {
                    // Check if email exists
                    $stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    $user = $stmt->fetch();
                    
                    if ($user) {
                        // Store the token in the database
                        $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
                        $stmt->execute([$resetToken, $resetExpiry, $email]);
                        
                        // Debug: Log the token and expiry being set
                        error_log("Setting reset token for email: $email");
                        error_log("Token: $resetToken");
                        error_log("Expires: $resetExpiry");
                        
                        // For online version - always use the domain name
                        $resetLink = "https://adarshdwivedi.free.nf/reset-password.php?token=$resetToken";
                        
                        // Include the email sending function
                        require_once 'includes/send_email.php';
                        
                        // Send password reset email
                        $emailResult = sendPasswordResetEmail(
                            $email,
                            $resetLink,
                            $user['username'] ?? 'User'
                        );
                        
                        if ($emailResult['status']) {
                            $message = '<div class="alert alert-success">
                                <h5><i class="fas fa-check-circle"></i> Password Reset Link Sent</h5>
                                <p>We have sent a password reset link to your email address. Please check your inbox and follow the instructions to reset your password.</p>
                                <p class="mb-0 small text-muted">If you don\'t see the email, please check your spam folder.</p>
                            </div>';
                            
                            error_log("Password reset email sent to: $email");
                        } else {
                            throw new Exception('Failed to send email: ' . $emailResult['message']);
                        }
                    } else {
                        // Don't reveal if the email exists
                        $message = 'If your email is registered, you will receive a password reset link.';
                    }
                } catch (PDOException $e) {
                    $message = 'An error occurred. Please try again later.';
                    error_log("Database error: " . $e->getMessage());
                }
                error_log("Email not found in database: $email");
            }
        } catch (PDOException $e) {
            $message = 'An error occurred. Please try again later.';
            error_log("Database error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - BighatMeal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body p-5">
                        <h2 class="text-center mb-4">Forgot Your Password?</h2>
                        
                        <?php if ($message): ?>
                            <div class="alert alert-info"><?php echo $message; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($emailSent): ?>
                            <div class="alert alert-success">
                                <?php echo $message; ?>
                                <p class="mt-3 mb-0">
                                    <a href="login.php" class="btn btn-primary">Go to Login Page</a>
                                </p>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info mb-4">
                                <h5><i class="fas fa-info-circle me-2"></i>Need to reset your password?</h5>
                                <p class="mb-0">Enter your email address and we'll send you a password reset link.</p>
                            </div>
                            <p class="text-muted mb-4">Enter your email address to recover your account information.</p>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Send Reset Link</button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-3">
                            <a href="login.php" class="text-decoration-none">Back to Login</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
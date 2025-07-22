<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once 'config.php';

// Set timezone to match your server's timezone
date_default_timezone_set('Asia/Kolkata');

$message = '';
$showForm = false;
$email = isset($_GET['email']) ? filter_var($_GET['email'], FILTER_SANITIZE_EMAIL) : '';

// Debug: Log the start of the script
error_log("=== Reset Password Script Started ===");
error_log("Email from URL: " . $email);

// Check if token is provided in the URL
$token = isset($_GET['token']) ? trim($_GET['token']) : '';

if (!empty($token)) {
    // Verify token is valid and not expired
    try {
        $stmt = $conn->prepare("SELECT email FROM users WHERE reset_token = ? AND reset_expires > NOW()");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $showForm = true;
            $email = $user['email'];
            $message = '<div class="alert alert-info">Please enter a new password for ' . htmlspecialchars($email) . '</div>';
            $_SESSION['reset_email'] = $email; // Store email in session for the form submission
            $_SESSION['reset_token'] = $token; // Store token in session for validation
        } else {
            $message = '<div class="alert alert-danger">Invalid or expired reset link. Please request a new one.</div>';
            error_log("Invalid or expired token: $token");
        }
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">An error occurred. Please try again.</div>';
        error_log("Database error: " . $e->getMessage());
    }
} else {
    $message = '<div class="alert alert-warning">No reset token provided. Please use the link from your email.</div>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $email = $_SESSION['reset_email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    error_log("POST - Processing password reset for token: $token");
    
    if (empty($token) || empty($email)) {
        $message = '<div class="alert alert-danger">Invalid reset request. Please use the link from your email.</div>';
        error_log("Missing token or email in session");
    } else if (empty($password) || $password !== $confirm_password) {
        $message = '<div class="alert alert-danger">Passwords do not match or are empty.</div>';
        error_log("Password validation failed for token: $token");
    } else {
        try {
            // Verify token is still valid
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND reset_token = ? AND reset_expires > NOW()");
            $stmt->execute([$email, $token]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Update password and clear reset token
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $updateStmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE email = ?");
                $updateStmt->execute([$hashedPassword, $email]);
                
                // Clear session variables
                unset($_SESSION['reset_email']);
                unset($_SESSION['reset_token']);
                
                $message = "<div class='alert alert-success'>
                    <h5>Password Updated Successfully</h5>
                    <p>Your password has been updated successfully.</p>
                    <a href='login.php' class='btn btn-primary'>Go to Login</a>
                </div>";
                $showForm = false;
                error_log("Password reset successful for email: $email");
            } else {
                $message = '<div class="alert alert-danger">Invalid or expired reset link. Please request a new one.</div>';
                error_log("Invalid token or expired link for email: $email");
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
    <title>Reset Password - BighatMeal</title>
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
                        <h2 class="text-center mb-4">Reset Your Password</h2>
                        
                        <?php if ($message): ?>
                            <?php echo $message; ?>
                        <?php endif; ?>
                        
                        <?php if ($showForm): ?>
                            <form method="POST" action="">
                                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                                
                                <div class="mb-3">
                                    <label for="password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">Reset Password</button>
                                    <a href="forgot-password.php" class="btn btn-outline-secondary">Request New Link</a>
                                </div>
                            </form>
                        <?php endif; ?>
                        
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

<?php
session_start();
require_once 'config.php';

$message = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
    } else {
        try {
            // Check if email exists
            $stmt = $conn->prepare("SELECT id, username, google_id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                if (!empty($user['google_id'])) {
                    // User has Google account
                    $message = 'This email is associated with a Google account. Please use the Google login option.';
                } else {
                    // Generate reset token
                    $token = bin2hex(random_bytes(32));
                    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    
                    // Delete any existing tokens
                    $conn->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
                    
                    // Insert new token
                    $stmt = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
                    if ($stmt->execute([$email, $token, $expires])) {
                        // Send reset email (in a real app, you would send an email)
                        $resetLink = "http://".$_SERVER['HTTP_HOST']."/Food/reset-password.php?token=$token";
                        
                        // For demo purposes, we'll just show the link
                        $message = "A password reset link has been generated. <a href='$resetLink'>Click here to reset your password</a>.";
                    } else {
                        $message = 'Failed to generate reset token. Please try again.';
                    }
                }
            } else {
                $message = 'No account found with that email address.';
            }
        } catch (PDOException $e) {
            error_log('Account help error: ' . $e->getMessage());
            $message = 'An error occurred. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Help - BighatMeal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 20px;
        }
        .help-container {
            max-width: 500px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .btn-primary {
            background-color: #2e7d32;
            border-color: #2e7d32;
        }
        .btn-primary:hover {
            background-color: #1b5e20;
            border-color: #1b5e20;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="help-container">
            <h2 class="text-center mb-4">Account Help</h2>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo strpos($message, 'successfully') !== false ? 'success' : 'info'; ?> mb-4">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Reset Your Password</h5>
                    <p class="card-text">Enter your email address and we'll send you a link to reset your password.</p>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
                    </form>
                </div>
            </div>
            
            <div class="text-center">
                <p>Remember your password? <a href="login.php">Log in here</a></p>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

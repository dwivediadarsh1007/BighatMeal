<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the config file
require_once 'config.php';

// Test credentials
$test_username = 'admin';  // Change this to test different users
$test_password = 'admin123'; // Change this to test different passwords

// Start output buffering
ob_start();

echo "<h2>Direct Login Test</h2>";

try {
    // 1. Test database connection
    echo "<h3>1. Testing Database Connection</h3>";
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<div style='color: green;'>✓ Database connection successful</div>";
    
    // 2. Find the user
    echo "<h3>2. Looking for user: " . htmlspecialchars($test_username) . "</h3>";
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$test_username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // Try with email
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$test_username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    if (!$user) {
        throw new Exception("User not found with username/email: " . htmlspecialchars($test_username));
    }
    
    echo "<div style='color: green;'>✓ User found in database:</div>";
    echo "<pre>";
    $user_display = $user;
    unset($user_display['password']); // Don't show the password hash
    print_r($user_display);
    echo "</pre>";
    
    // 3. Check password
    echo "<h3>3. Verifying Password</h3>";
    if (empty($user['password'])) {
        echo "<div style='color: orange;'>No password set for this user.</div>";
    } else {
        $passwordMatch = password_verify($test_password, $user['password']);
        
        if ($passwordMatch) {
            echo "<div style='color: green;'>✓ Password verification successful!</div>";
            
            // 4. Check if password needs rehashing
            if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                $newHash = password_hash($test_password, PASSWORD_DEFAULT);
                echo "<div style='color: blue;'>Password needs rehashing. New hash: " . 
                     htmlspecialchars(substr($newHash, 0, 30)) . "...</div>";
            }
            
            // 5. Test session
            echo "<h3>4. Testing Session</h3>";
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'] ?? 'user';
            
            // Write and close session
            session_write_close();
            
            // Start session again to verify
            session_start();
            if (isset($_SESSION['user_id'])) {
                echo "<div style='color: green;'>✓ Session test successful!</div>";
                echo "<pre>Session data: " . print_r($_SESSION, true) . "</pre>";
                
                // 6. Test redirect
                echo "<h3>5. Testing Redirect</h3>";
                echo "<div style='color: green;'>All tests passed! You should be able to log in now.</div>";
                echo "<p><a href='index.php' class='btn btn-success'>Go to Home Page</a></p>";
            } else {
                echo "<div style='color: red;'>Session test failed. Session variables not set.</div>";
            }
            
        } else {
            echo "<div style='color: red;'>✗ Password verification failed.</div>";
            
            // Show debug info
            echo "<h4>Debug Info:</h4>";
            echo "<p>Input password: " . htmlspecialchars($test_password) . "</p>";
            echo "<p>Stored hash: " . htmlspecialchars(substr($user['password'], 0, 30)) . "...</p>";
            
            // Try direct comparison (in case password is stored in plain text)
            if ($user['password'] === $test_password) {
                echo "<div style='color: orange;'>Note: Password matches directly (stored in plain text). You should update the password hashing.</div>";
            }
        }
    }
    
} catch (Exception $e) {
    echo "<div style='color: red;'>Error: " . $e->getMessage() . "</div>";
    
    // Show database error info if available
    if (isset($conn) && $conn instanceof PDO) {
        echo "<div>Error info: " . print_r($conn->errorInfo(), true) . "</div>";
    }
}

// Show any output that might have been buffered
$output = ob_get_clean();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Direct Login Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; font-family: Arial, sans-serif; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
        h3 { margin-top: 30px; border-bottom: 1px solid #eee; padding-bottom: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Direct Login Test</h1>
        <div class="card">
            <div class="card-body">
                <?php echo $output; ?>
            </div>
        </div>
        
        <div class="mt-4">
            <h3>Test Different Credentials</h3>
            <form method="get" action="" class="mb-4">
                <div class="mb-3">
                    <label class="form-label">Username or Email:</label>
                    <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($test_username); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Password:</label>
                    <input type="password" name="password" class="form-control" value="<?php echo htmlspecialchars($test_password); ?>">
                </div>
                <button type="submit" class="btn btn-primary">Test Login</button>
            </form>
        </div>
    </div>
</body>
</html>
?>

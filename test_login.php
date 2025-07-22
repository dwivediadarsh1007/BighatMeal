<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'config.php';

// Start output buffering
ob_start();

echo "<h2>Login Test</h2>";

// Test user credentials (replace with actual test credentials)
$test_username = 'admin';
$test_password = 'admin123'; // Replace with the actual password

try {
    echo "<h3>Attempting login for user: $test_username</h3>";
    
    // First, find the user
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . print_r($conn->errorInfo(), true));
    }
    
    $result = $stmt->execute([$test_username]);
    if ($result === false) {
        throw new Exception("Execute failed: " . print_r($stmt->errorInfo(), true));
    }
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "<div style='color: red;'>User not found: $test_username</div>";
    } else {
        echo "<h4>User found:</h4>";
        echo "<pre>";
        $user_display = $user;
        unset($user_display['password']); // Don't display the password hash
        print_r($user_display);
        echo "</pre>";
        
        // Check password
        if (empty($user['password'])) {
            echo "<div style='color: orange;'>No password set for this user. Please use Google login or reset password.</div>";
        } else {
            $passwordMatch = password_verify($test_password, $user['password']);
            echo "<div>Password verification: " . ($passwordMatch ? "<span style='color: green;'>SUCCESS</span>" : "<span style='color: red;'>FAILED</span>") . "</div>";
            
            if (!$passwordMatch) {
                // Check if password needs rehashing
                if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                    echo "<div style='color: blue;'>Password needs rehashing</div>";
                    $newHash = password_hash($test_password, PASSWORD_DEFAULT);
                    echo "<div>New hash: " . substr($newHash, 0, 20) . "...</div>";
                }
                
                // Show first 20 chars of stored hash for debugging
                echo "<div>Stored hash: " . substr($user['password'], 0, 20) . "...</div>";
                
                // Try to create a hash with the test password to see if it matches
                $testHash = password_hash($test_password, PASSWORD_BCRYPT, ['cost' => 10]);
                echo "<div>Test hash: " . substr($testHash, 0, 20) . "...</div>";
                
                // Try direct comparison (in case hashing isn't working as expected)
                $directMatch = ($user['password'] === $test_password);
                echo "<div>Direct comparison: " . ($directMatch ? "<span style='color: green;'>MATCH</span>" : "<span style='color: red;'>NO MATCH</span>") . "</div>";
            } else {
                echo "<div style='color: green;'>Login successful!</div>";
            }
        }
    }
    
} catch (Exception $e) {
    echo "<div style='color: red;'>Error: " . $e->getMessage() . "</div>";
}

// Show any output that might have been buffered
$output = ob_get_clean();
echo $output;
?>

<h3>Test Login Form</h3>
<form method="post" action="">
    <div>
        <label>Username: <input type="text" name="test_username" value="<?php echo htmlspecialchars($test_username); ?>"></label>
    </div>
    <div>
        <label>Password: <input type="password" name="test_password" value="<?php echo htmlspecialchars($test_password); ?>"></label>
    </div>
    <div>
        <input type="submit" value="Test Login">
    </div>
</form>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    pre { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; }
    div { margin: 10px 0; }
</style>

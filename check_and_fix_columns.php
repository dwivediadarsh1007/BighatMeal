<?php
require_once 'config.php';

try {
    // Check if columns exist
    $stmt = $conn->query("SHOW COLUMNS FROM users LIKE 'reset_token'");
    $resetTokenExists = $stmt->rowCount() > 0;
    
    $stmt = $conn->query("SHOW COLUMNS FROM users LIKE 'reset_expires'");
    $resetExpiresExists = $stmt->rowCount() > 0;
    
    echo "<h2>Database Columns Check</h2>";
    echo "reset_token exists: " . ($resetTokenExists ? 'Yes' : 'No') . "<br>";
    echo "reset_expires exists: " . ($resetExpiresExists ? 'Yes' : 'No') . "<br>";
    
    if (!$resetTokenExists || !$resetExpiresExists) {
        echo "<h3>Adding missing columns...</h3>";
        
        if (!$resetTokenExists) {
            $conn->exec("ALTER TABLE users ADD COLUMN reset_token VARCHAR(64) NULL");
            echo "Added reset_token column<br>";
        }
        
        if (!$resetExpiresExists) {
            $conn->exec("ALTER TABLE users ADD COLUMN reset_expires DATETIME NULL");
            echo "Added reset_expires column<br>";
        }
        
        // Add index
        $conn->exec("ALTER TABLE users ADD INDEX idx_reset_token (reset_token)");
        echo "Added index on reset_token<br>";
        
        echo "<p style='color: green;'>Database updated successfully!</p>";
    } else {
        echo "<p style='color: green;'>All required columns exist.</p>";
    }
    
} catch(PDOException $e) {
    echo "<div style='color: red;'>Error: " . $e->getMessage() . "</div>";
}

// Show current users table structure
echo "<h3>Current Users Table Structure:</h3>";
$stmt = $conn->query("DESCRIBE users");
echo "<pre>";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}
echo "</pre>";
?>

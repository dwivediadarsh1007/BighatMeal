<?php
require_once 'config.php';

echo "<pre>Testing Database Connection\n";
echo "===========================\n";

try {
    // Test database connection
    $stmt = $conn->query("SELECT 1");
    echo "✅ Database connection successful!\n\n";
    
    // Test users table
    echo "Checking users table...\n";
    $stmt = $conn->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Users table exists\n";
        
        // Show first user (for debugging)
        $user = $conn->query("SELECT * FROM users LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            echo "👤 First user found: " . ($user['email'] ?? 'No email') . "\n";
            echo "   User ID: " . ($user['id'] ?? 'N/A') . "\n";
            echo "   Username: " . ($user['username'] ?? 'N/A') . "\n";
        } else {
            echo "ℹ️ No users found in the database\n";
        }
    } else {
        echo "❌ Users table does NOT exist\n";
    }
    
    echo "\nChecking password_resets table...\n";
    // Test password_resets table
    $stmt = $conn->query("SHOW TABLES LIKE 'password_resets'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Password resets table exists\n";
    } else {
        echo "❌ Password resets table does NOT exist\n";
        // Try to create the table
        try {
            $conn->exec("CREATE TABLE IF NOT EXISTS password_resets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) NOT NULL,
                token VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                expires_at DATETIME NOT NULL,
                UNIQUE KEY (email, token)
            )");
            echo "✅ Created password_resets table\n";
        } catch (Exception $e) {
            echo "❌ Failed to create password_resets table: " . $e->getMessage() . "\n";
        }
    }
    
} catch(PDOException $e) {
    echo "\n❌ Database Error: " . $e->getMessage() . "\n";
}

echo "\nTest completed. Check above for any errors.\n";
echo "</pre>";
?>

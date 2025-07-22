<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the config file
require_once 'config.php';

try {
    // Test database connection
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Database Connection Test</h2>";
    echo "Connection successful!<br><br>";
    
    // Check users table structure
    echo "<h3>Users Table Structure:</h3>";
    $stmt = $conn->query("SHOW COLUMNS FROM users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
    
    // Check if required columns exist
    $required_columns = ['reset_token', 'reset_expires'];
    $missing_columns = array_diff($required_columns, $columns);
    
    if (!empty($missing_columns)) {
        echo "<div style='color: orange;'>Missing columns: " . implode(', ', $missing_columns) . "</div>";
        
        // Try to add missing columns
        echo "<h3>Attempting to add missing columns...</h3>";
        
        if (in_array('reset_token', $missing_columns)) {
            try {
                $conn->exec("ALTER TABLE users ADD COLUMN reset_token VARCHAR(64) NULL");
                echo "Added reset_token column<br>";
            } catch (PDOException $e) {
                echo "Error adding reset_token: " . $e->getMessage() . "<br>";
            }
        }
        
        if (in_array('reset_expires', $missing_columns)) {
            try {
                $conn->exec("ALTER TABLE users ADD COLUMN reset_expires DATETIME NULL");
                echo "Added reset_expires column<br>";
                
                // Add index
                $conn->exec("ALTER TABLE users ADD INDEX idx_reset_token (reset_token)");
                echo "Added index on reset_token<br>";
            } catch (PDOException $e) {
                echo "Error adding reset_expires: " . $e->getMessage() . "<br>";
            }
        }
    } else {
        echo "<div style='color: green;'>All required columns exist.</div>";
    }
    
    // Show sample data (first user)
    echo "<h3>Sample User Data (first user):</h3>";
    $stmt = $conn->query("SELECT id, username, email, reset_token, reset_expires FROM users LIMIT 1");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    print_r($user);
    echo "</pre>";
    
} catch(PDOException $e) {
    echo "<h3>Error:</h3>";
    echo "<pre>" . $e->getMessage() . "</pre>";
    echo "<p>Check your database configuration in config.php</p>";
}
?>

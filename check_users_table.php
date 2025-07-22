<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the config file
require_once 'config.php';

try {
    // Get table structure
    $stmt = $conn->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h3>Users Table Structure:</h3>";
    echo "<pre>";
    $stmt = $conn->query("DESCRIBE users");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
    echo "</pre>";
    
    // Check for required columns
    $required_columns = ['id', 'username', 'password', 'email', 'google_id'];
    $missing_columns = array_diff($required_columns, $columns);
    
    if (!empty($missing_columns)) {
        echo "<div style='color: red;'>Warning: Missing required columns: " . implode(', ', $missing_columns) . "</div>";
    } else {
        echo "<div style='color: green;'>All required columns are present.</div>";
    }
    
    // Show first user (without sensitive data)
    echo "<h3>Sample User Data (first user):</h3>";
    $stmt = $conn->query("SELECT id, username, email, role, created_at FROM users LIMIT 1");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($user);
    echo "</pre>";
    
} catch(PDOException $e) {
    echo "<h3>Error:</h3>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
?>

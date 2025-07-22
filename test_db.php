<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the config file
require_once 'config.php';

try {
    // Test database connection
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Test query
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Database connection successful!<br>";
    echo "Number of users in database: " . $result['count'] . "<br>";
    
    // Show PHP info for debugging
    echo "<h3>PHP Info:</h3>";
    echo "PHP Version: " . phpversion() . "<br>";
    echo "PDO Drivers: " . print_r(PDO::getAvailableDrivers(), true) . "<br>";
    
} catch(PDOException $e) {
    echo "<h3>Database Connection Error:</h3>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "Error Code: " . $e->getCode() . "<br>";
    echo "In file: " . $e->getFile() . " on line " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    
    // Show connection details (without password)
    echo "<h3>Connection Details:</h3>";
    echo "DB_HOST: " . (defined('DB_HOST') ? DB_HOST : 'Not defined') . "<br>";
    echo "DB_NAME: " . (defined('DB_NAME') ? DB_NAME : 'Not defined') . "<br>";
    echo "DB_USER: " . (defined('DB_USER') ? DB_USER : 'Not defined') . "<br>";
    echo "DB_PASS: " . (defined('DB_PASS') ? '*****' : 'Not defined') . "<br>";
}
?>

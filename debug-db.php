<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to print a section header
function printSection($title) {
    echo "\n\n" . str_repeat("=", 80) . "\n";
    echo "$title\n";
    echo str_repeat("=", 80) . "\n\n";
}

// Function to print a table
function printTable($data, $title = '') {
    if ($title) {
        echo "\n$title\n";
        echo str_repeat("-", 80) . "\n";
    }
    
    if (empty($data)) {
        echo "No data found\n";
        return;
    }
    
    // Get column headers
    $headers = array_keys($data[0]);
    
    // Calculate column widths
    $widths = [];
    foreach ($headers as $header) {
        $widths[$header] = strlen($header);
    }
    
    foreach ($data as $row) {
        foreach ($row as $key => $value) {
            $widths[$key] = max($widths[$key], strlen($value));
        }
    }
    
    // Print header
    foreach ($headers as $header) {
        printf("%-{$widths[$header]}s  ", $header);
    }
    echo "\n" . str_repeat("-", array_sum($widths) + (count($widths) * 2)) . "\n";
    
    // Print rows
    foreach ($data as $row) {
        foreach ($headers as $header) {
            printf("%-{$widths[$header]}s  ", $row[$header] ?? '');
        }
        echo "\n";
    }
}

// Database connection
try {
    require_once 'config.php';
    
    // Test connection
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    printSection("DATABASE CONNECTION");
    echo "Connected successfully to database: " . $conn->getAttribute(PDO::ATTR_CONNECTION_STATUS) . "\n";
    
    // Check cart table
    printSection("CART TABLE STRUCTURE");
    $stmt = $conn->query("SHOW CREATE TABLE cart");
    $cartTable = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($cartTable) {
        echo $cartTable['Create Table'] . "\n";
    } else {
        echo "Cart table does not exist.\n";
    }
    
    // Check products table
    printSection("PRODUCTS TABLE STRUCTURE");
    $stmt = $conn->query("SHOW CREATE TABLE products");
    $productsTable = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($productsTable) {
        echo $productsTable['Create Table'] . "\n";
    } else {
        echo "Products table does not exist.\n";
    }
    
    // Check users table
    printSection("USERS TABLE STRUCTURE");
    $stmt = $conn->query("SHOW CREATE TABLE users");
    $usersTable = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($usersTable) {
        echo $usersTable['Create Table'] . "\n";
    } else {
        echo "Users table does not exist.\n";
    }
    
    // Check cart data
    printSection("CART DATA");
    $stmt = $conn->query("SELECT * FROM cart LIMIT 10");
    $cartData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    printTable($cartData, "First 10 cart items:");
    
    // Check products data
    printSection("PRODUCTS DATA");
    $stmt = $conn->query("SELECT id, name, price, is_available FROM products LIMIT 10");
    $productsData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    printTable($productsData, "First 10 products:");
    
    // Check users data
    printSection("USERS DATA");
    $stmt = $conn->query("SELECT id, username, role FROM users LIMIT 5");
    $usersData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    printTable($usersData, "First 5 users:");
    
    // Check foreign key constraints
    printSection("FOREIGN KEY CHECKS");
    $tables = ['cart'];
    foreach ($tables as $table) {
        $stmt = $conn->query("SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME 
                             FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                             WHERE TABLE_SCHEMA = DATABASE() 
                             AND REFERENCED_TABLE_NAME IS NOT NULL 
                             AND TABLE_NAME = '$table'");
        $constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
        printTable($constraints, "Foreign key constraints for $table:");
    }
    
} catch(PDOException $e) {
    printSection("DATABASE ERROR");
    echo "Connection failed: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";
    echo "SQL State: " . $e->errorInfo[0] . "\n";
    echo "Driver Error: " . $e->errorInfo[2] . "\n";
}

// Check PHP configuration
printSection("PHP CONFIGURATION");
echo "PHP Version: " . phpversion() . "\n";
echo "PDO Drivers: " . implode(", ", PDO::getAvailableDrivers()) . "\n";
echo "Memory Limit: " . ini_get('memory_limit') . "\n";
echo "Max Execution Time: " . ini_get('max_execution_time') . "\n";

// Check session configuration
printSection("SESSION CONFIGURATION");
echo "Session Status: " . session_status() . "\n";
echo "Session ID: " . session_id() . "\n";
echo "Session Name: " . session_name() . "\n";
echo "Session Save Path: " . session_save_path() . "\n";

// Check file permissions
printSection("FILE PERMISSIONS");
$files = [
    'config.php' => 'config.php',
    'add-to-cart.php' => 'add-to-cart.php',
    'logs/' => 'logs directory',
    'logs/error.log' => 'error log',
    'logs/db_errors.log' => 'database error log'
];

echo str_pad("File/Directory", 30) . str_pad("Exists", 10) . "Permissions\n";
echo str_repeat("-", 60) . "\n";

foreach ($files as $file => $label) {
    $exists = file_exists($file) ? 'Yes' : 'No';
    $perms = file_exists($file) ? substr(sprintf('%o', fileperms($file)), -4) : 'N/A';
    echo str_pad($label, 30) . str_pad($exists, 10) . $perms . "\n";
}
?>

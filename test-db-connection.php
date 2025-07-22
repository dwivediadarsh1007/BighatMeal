<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once 'config.php';

// Function to log messages
function logMessage($message) {
    $logFile = __DIR__ . '/logs/db-test.log';
    if (!file_exists(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    echo "$timestamp - $message<br>";
}

// Test database connection
try {
    logMessage("Testing database connection...");
    
    // Test connection
    $conn->query("SELECT 1");
    logMessage("✓ Database connection successful");
    
    // Check if cart table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'cart'");
    if ($tableCheck->rowCount() > 0) {
        logMessage("✓ Cart table exists");
        
        // Check cart table structure
        $columns = $conn->query("DESCRIBE cart")->fetchAll(PDO::FETCH_ASSOC);
        logMessage("Cart table structure:");
        foreach ($columns as $column) {
            logMessage("  - {$column['Field']} ({$column['Type']})");
        }
        
        // Count items in cart
        $count = $conn->query("SELECT COUNT(*) as count FROM cart")->fetch();
        logMessage("Total items in cart: " . $count['count']);
    } else {
        logMessage("✗ Cart table does not exist");
        
        // Try to create cart table
        logMessage("Attempting to create cart table...");
        $sql = "CREATE TABLE IF NOT EXISTS `cart` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `product_id` int(11) NOT NULL,
            `quantity` int(11) NOT NULL DEFAULT '1',
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_cart_item` (`user_id`,`product_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $conn->exec($sql);
        logMessage("✓ Cart table created successfully");
    }
    
    // Check if products table exists and has data
    $productsTable = $conn->query("SHOW TABLES LIKE 'products'");
    if ($productsTable->rowCount() > 0) {
        $productCount = $conn->query("SELECT COUNT(*) as count FROM products")->fetch();
        logMessage("✓ Products table exists with {$productCount['count']} products");
    } else {
        logMessage("✗ Products table does not exist");
    }
    
} catch (PDOException $e) {
    logMessage("✗ Database error: " . $e->getMessage());
    logMessage("Error details: " . print_r($e->errorInfo, true));
}

// Test session
logMessage("\nTesting session...");
session_start();
if (!isset($_SESSION['test'])) {
    $_SESSION['test'] = time();
    logMessage("Session started with ID: " . session_id());
} else {
    logMessage("Session already exists with ID: " . session_id());
}

// Output log file location
$logFile = __DIR__ . '/logs/db-test.log';
echo "<br><br>Log file: <a href='file:///$logFile' target='_blank'>$logFile</a>";
?>

<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Set user ID for testing
$_SESSION['user_id'] = 1;

// Database configuration
$host = 'localhost';
$dbname = 'food_delivery';
$username = 'root';
$password = '';

try {
    // Create connection
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
    ]);
    
    // Test query
    $stmt = $conn->query("SELECT 1 as test");
    $result = $stmt->fetch();
    
    echo "<h1>Database Connection Test</h1>";
    echo "<p>Database connection successful! Test query result: " . $result['test'] . "</p>";
    
    // Check if cart table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'cart'");
    if ($tableCheck->rowCount() > 0) {
        echo "<p>✅ Cart table exists</p>";
        
        // Show cart table structure
        echo "<h3>Cart Table Structure:</h3>";
        $columns = $conn->query("DESCRIBE cart")->fetchAll();
        echo "<pre>";
        print_r($columns);
        echo "</pre>";
        
        // Try to insert test item
        try {
            $testProductId = 1;
            $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE quantity = quantity + 1");
            $stmt->execute([$_SESSION['user_id'], $testProductId]);
            
            // Get cart count
            $stmt = $conn->prepare("SELECT COALESCE(SUM(quantity), 0) as cart_count FROM cart WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $result = $stmt->fetch();
            
            echo "<p>✅ Successfully added test item to cart. Cart count: " . $result['cart_count'] . "</p>";
            
        } catch (PDOException $e) {
            echo "<p style='color: red;'>❌ Error adding to cart: " . $e->getMessage() . "</p>";
            echo "<pre>Error Code: " . $e->getCode() . "\n";
            echo "SQL State: " . $e->errorInfo[0] . "\n";
            echo "Error Info: " . print_r($e->errorInfo, true) . "</pre>";
        }
        
    } else {
        echo "<p style='color: orange;'>⚠️ Cart table does not exist. Attempting to create it...</p>";
        
        try {
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
            echo "<p>✅ Cart table created successfully!</p>";
            
        } catch (PDOException $e) {
            echo "<p style='color: red;'>❌ Error creating cart table: " . $e->getMessage() . "</p>";
        }
    }
    
} catch (PDOException $e) {
    echo "<h1>Database Connection Error</h1>";
    echo "<p style='color: red;'>❌ Connection failed: " . $e->getMessage() . "</p>";
    echo "<h3>Connection Details:</h3>";
    echo "<pre>";
    echo "Host: $host\n";
    echo "Database: $dbname\n";
    echo "Username: $username\n";
    echo "</pre>";
    
    // Check if MySQL is running
    echo "<h3>MySQL Status:</h3>";
    $mysqlRunning = false;
    if (function_exists('shell_exec')) {
        $output = shell_exec('net start | findstr /i "mysql"');
        $mysqlRunning = !empty($output);
    }
    
    if ($mysqlRunning) {
        echo "<p>✅ MySQL service is running</p>";
    } else {
        echo "<p style='color: red;'>❌ MySQL service is not running or cannot be checked</p>";
    }
}

// Show session info
echo "<h3>Session Information:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
?>

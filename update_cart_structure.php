<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once 'config.php';

try {
    // Connect to the database using config variables
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Disable foreign key checks temporarily
    $conn->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Create a backup of the cart table if it exists
    $tableExists = $conn->query("SHOW TABLES LIKE 'cart'")->rowCount() > 0;
    
    if ($tableExists) {
        echo "Backing up existing cart table...\n";
        $conn->exec("DROP TABLE IF EXISTS cart_backup");
        $conn->exec("CREATE TABLE cart_backup AS SELECT * FROM cart");
        echo "Backup created as 'cart_backup' table.\n";
        
        // Drop the existing cart table
        echo "Dropping existing cart table...\n";
        $conn->exec("DROP TABLE IF EXISTS cart");
    }
    
    echo "Creating new cart table...\n";
    
    // Create the new cart table with the updated structure
    $sql = "CREATE TABLE `cart` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `meal_type` ENUM('custom-meal', 'standard') DEFAULT 'standard',
        `items` JSON DEFAULT NULL,
        `total_calories` decimal(10,2) DEFAULT 0,
        `total_protein` decimal(10,2) DEFAULT 0,
        `total_carbs` decimal(10,2) DEFAULT 0,
        `total_fat` decimal(10,2) DEFAULT 0,
        `total_fiber` decimal(10,2) DEFAULT 0,
        `total_price` decimal(10,2) DEFAULT 0,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $conn->exec($sql);
    
    // Re-enable foreign key checks
    $conn->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "Cart table has been successfully updated with the new structure.\n";
    echo "The old cart data has been backed up to 'cart_backup' table.\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>

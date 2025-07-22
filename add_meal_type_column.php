<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once 'config.php';

try {
    // Connect to the database
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if the column exists
    $stmt = $conn->query("SHOW COLUMNS FROM `cart` LIKE 'meal_type'");
    $columnExists = $stmt->rowCount() > 0;
    
    if (!$columnExists) {
        // Add the meal_type column
        $sql = "ALTER TABLE `cart` ADD COLUMN `meal_type` VARCHAR(50) DEFAULT NULL AFTER `product_id`";
        $conn->exec($sql);
        echo "Successfully added 'meal_type' column to the cart table.\n";
        
        // Update the unique key to include meal_type if needed
        // This makes the combination of user_id, product_id, and meal_type unique
        $conn->exec("ALTER TABLE `cart` DROP INDEX `unique_cart_item`");
        $conn->exec("ALTER TABLE `cart` ADD UNIQUE `unique_cart_item` (`user_id`, `product_id`, `meal_type`)");
        echo "Updated unique constraint to include meal_type.\n";
    } else {
        echo "The 'meal_type' column already exists in the cart table.\n";
    }
    
    echo "Operation completed successfully.\n";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>

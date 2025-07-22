<?php
require_once 'config.php';

try {
    // Disable foreign key checks temporarily
    $conn->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Drop the cart table if it exists
    $conn->exec("DROP TABLE IF EXISTS cart");
    
    // Recreate the cart table with proper foreign key constraints
    $sql = "CREATE TABLE `cart` (
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
    
    // Re-enable foreign key checks
    $conn->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "Cart table has been recreated successfully.\n";
    
    // Verify the table structure
    $stmt = $conn->query("SHOW CREATE TABLE cart");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "\nTable structure:\n";
    echo $result['Create Table'];
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

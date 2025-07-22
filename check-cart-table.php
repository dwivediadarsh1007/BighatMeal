<?php
require_once 'config.php';

try {
    // Check if cart table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'cart'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        // Create cart table if it doesn't exist
        $sql = "CREATE TABLE IF NOT EXISTS `cart` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `product_id` int(11) NOT NULL,
            `quantity` int(11) NOT NULL DEFAULT '1',
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_cart_item` (`user_id`,`product_id`),
            CONSTRAINT `fk_cart_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        $conn->exec($sql);
        echo "Cart table created successfully\n";
    } else {
        echo "Cart table already exists\n";
    }
    
    // Check cart table structure
    $stmt = $conn->query("DESCRIBE cart");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Cart table structure:\n";
    print_r($columns);
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

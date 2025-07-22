<?php
require_once 'config.php';

try {
    // Check if cart table exists
    $tableExists = $conn->query("SHOW TABLES LIKE 'cart'")->rowCount() > 0;
    
    if (!$tableExists) {
        // Create cart table if it doesn't exist
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
        echo "Cart table created successfully.\n";
    } else {
        echo "Cart table already exists. Checking structure...\n";
        
        // Check for meal_type column
        $stmt = $conn->query("SHOW COLUMNS FROM cart LIKE 'meal_type'");
        if ($stmt->rowCount() > 0) {
            // Remove meal_type column if it exists
            $conn->exec("ALTER TABLE cart DROP COLUMN meal_type");
            echo "Removed 'meal_type' column from cart table.\n";
        }
    }
    
    // Show current cart table structure
    $stmt = $conn->query("DESCRIBE cart");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nCurrent Cart Table Structure:\n";
    echo str_repeat("-", 50) . "\n";
    printf("%-20s | %-20s | %-10s\n", "Column", "Type", "Null");
    echo str_repeat("-", 50) . "\n";
    
    foreach ($columns as $col) {
        printf("%-20s | %-20s | %-10s\n", 
            $col['Field'], 
            $col['Type'], 
            $col['Null']
        );
    }
    
    // Show sample cart data
    $stmt = $conn->query("SELECT * FROM cart LIMIT 5");
    $sample_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nSample Cart Data (first 5 rows):\n";
    print_r($sample_data);
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage() . "\n");
}

echo "\nScript completed successfully.\n";
?>

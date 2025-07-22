<?php
require_once 'config.php';

try {
    // Check if the cart table exists
    $tableExists = $conn->query("SHOW TABLES LIKE 'cart'")->rowCount() > 0;
    
    if (!$tableExists) {
        echo "Cart table does not exist. Creating it...\n";
        
        $sql = "CREATE TABLE IF NOT EXISTS cart (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            meal_type ENUM('custom-meal', 'standard') DEFAULT 'standard',
            items JSON,
            total_calories DECIMAL(10,2) DEFAULT 0,
            total_protein DECIMAL(10,2) DEFAULT 0,
            total_carbs DECIMAL(10,2) DEFAULT 0,
            total_fat DECIMAL(10,2) DEFAULT 0,
            total_fiber DECIMAL(10,2) DEFAULT 0,
            total_price DECIMAL(10,2) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $conn->exec($sql);
        echo "Cart table created successfully.\n";
    } else {
        echo "Cart table exists. Checking structure...\n";
        
        // Check if meal_type column exists
        $stmt = $conn->query("SHOW COLUMNS FROM cart LIKE 'meal_type'");
        if ($stmt->rowCount() === 0) {
            echo "Adding missing columns to cart table...\n";
            
            // Add missing columns
            $conn->exec("ALTER TABLE cart 
                        ADD COLUMN meal_type ENUM('custom-meal', 'standard') DEFAULT 'standard' AFTER user_id,
                        ADD COLUMN items JSON AFTER meal_type,
                        ADD COLUMN total_calories DECIMAL(10,2) DEFAULT 0 AFTER items,
                        ADD COLUMN total_protein DECIMAL(10,2) DEFAULT 0 AFTER total_calories,
                        ADD COLUMN total_carbs DECIMAL(10,2) DEFAULT 0 AFTER total_protein,
                        ADD COLUMN total_fat DECIMAL(10,2) DEFAULT 0 AFTER total_carbs,
                        ADD COLUMN total_fiber DECIMAL(10,2) DEFAULT 0 AFTER total_fat,
                        ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
            
            echo "Cart table structure updated successfully.\n";
        } else {
            echo "Cart table structure is up to date.\n";
        }
    }
    
    // Show the current structure
    echo "\nCurrent cart table structure:\n";
    $result = $conn->query("DESCRIBE cart");
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "- {$row['Field']} ({$row['Type']})\n";
    }
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage() . "\n");
}
?>

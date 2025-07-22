<?php
require_once 'config.php';

try {
    // Start transaction
    $conn->beginTransaction();
    
    // Create a temporary table with the new structure
    $sql = "CREATE TABLE cart_new (
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
    )";
    
    $conn->exec($sql);
    
    // Copy existing data to the new table if needed
    $conn->exec("INSERT INTO cart_new (user_id, items, total_price, created_at) 
                 SELECT user_id, JSON_ARRAY(JSON_OBJECT('product_id', product_id, 'quantity', quantity)), 
                       (SELECT price FROM products WHERE id = product_id) * quantity, 
                       created_at 
                 FROM cart");
    
    // Drop the old table
    $conn->exec("DROP TABLE cart");
    
    // Rename new table to original name
    $conn->exec("RENAME TABLE cart_new TO cart");
    
    // Commit the transaction
    $conn->commit();
    
    echo "Cart table updated successfully!\n";
    
} catch (PDOException $e) {
    // Roll back the transaction if something failed
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    die("Error updating cart table: " . $e->getMessage());
}

echo "Cart table structure has been updated successfully.\n";
?>

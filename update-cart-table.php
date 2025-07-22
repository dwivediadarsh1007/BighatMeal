<?php
require_once 'config.php';

try {
    // Drop existing cart table
    $conn->exec("DROP TABLE IF EXISTS cart");
    
    // Create new cart table
    $sql = "CREATE TABLE cart (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        meal_type VARCHAR(50) NOT NULL,
        items JSON NOT NULL,
        total_calories DECIMAL(10,2) NOT NULL,
        total_protein DECIMAL(10,2) NOT NULL,
        total_carbs DECIMAL(10,2) NOT NULL,
        total_fat DECIMAL(10,2) NOT NULL,
        total_fiber DECIMAL(10,2) NOT NULL,
        total_price DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )";
    
    $conn->exec($sql);
    echo "Cart table updated successfully\n";
    
    // Test insert
    $testData = [
        'name' => 'Test Item',
        'quantity' => 100,
        'calories' => 100,
        'protein' => 10,
        'carbs' => 20,
        'fat' => 5,
        'fiber' => 3,
        'price' => 50
    ];
    
    $stmt = $conn->prepare("INSERT INTO cart (user_id, meal_type, items, total_calories, total_protein, total_carbs, total_fat, total_fiber, total_price, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $result = $stmt->execute([1, 'test', json_encode([$testData]), 100, 10, 20, 5, 3, 50]);
    
    if ($result) {
        echo "Test insert successful\n";
    } else {
        echo "Test insert failed: " . print_r($stmt->errorInfo(), true) . "\n";
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>

<?php
require_once 'config.php';

try {
    // Check cart table structure
    $result = $conn->query("DESCRIBE cart");
    echo "Cart table structure:\n";
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "- {$row['Field']} ({$row['Type']})\n";
    }
    
    // Get sample cart data
    echo "\nSample cart data:\n";
    $stmt = $conn->prepare("SELECT * FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($cart_items as $item) {
        echo "\nCart Item:\n";
        echo "ID: {$item['id']}\n";
        echo "Meal Type: {$item['meal_type']}\n";
        echo "Items JSON: " . print_r(json_decode($item['items'], true), true) . "\n";
        echo "Total Calories: {$item['total_calories']}\n";
        echo "Total Price: {$item['total_price']}\n";
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>

<?php
require_once 'config.php';

try {
    // Check cart table structure
    $result = $conn->query("DESCRIBE cart");
    echo "Cart table structure:\n";
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "- {$row['Field']} ({$row['Type']})\n";
    }
    
    // Get all cart items for current user
    $stmt = $conn->prepare("SELECT * FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nCart items:\n";
    foreach ($cart_items as $item) {
        echo "\nItem ID: {$item['id']}\n";
        echo "Meal Type: {$item['meal_type']}\n";
        echo "Items JSON: " . print_r(json_decode($item['items'], true), true) . "\n";
        echo "Total Price: {$item['total_price']}\n";
        echo "Product ID: " . (isset($item['product_id']) ? $item['product_id'] : 'not set') . "\n";
        echo "Quantity: " . (isset($item['quantity']) ? $item['quantity'] : 'not set') . "\n";
        echo "Price: " . (isset($item['price']) ? $item['price'] : 'not set') . "\n";
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>

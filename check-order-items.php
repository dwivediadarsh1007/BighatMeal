<?php
session_start();
require_once 'config.php';

// Check order items
$stmt = $conn->prepare("SELECT oi.*, o.total_amount as order_total 
                       FROM order_items oi 
                       JOIN orders o ON oi.order_id = o.id 
                       WHERE o.id = ?");
$stmt->execute([$_GET['order_id'] ?? 1]);
$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate total from items
$calculated_total = 0;
foreach ($order_items as $item) {
    $calculated_total += $item['quantity'] * $item['price'];
}

// Display results
echo "Order Total in Database: " . ($order_items[0]['order_total'] ?? 'N/A') . "<br>";
echo "Calculated Total: " . $calculated_total . "<br><br>";

echo "<h3>Order Items:</h3>";
foreach ($order_items as $item) {
    echo "Name: " . $item['product_name'] . "<br>";
    echo "Quantity: " . $item['quantity'] . "<br>";
    echo "Price: " . $item['price'] . "<br>";
    echo "---<br>";
}
?>

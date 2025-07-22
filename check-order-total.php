<?php
session_start();
require_once '../config.php';

// Get order ID from URL
$order_id = $_GET['order_id'] ?? null;

if ($order_id) {
    // Get order details
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get order items
    $stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate total from items
    $calculated_total = 0;
    foreach ($order_items as $item) {
        $calculated_total += $item['quantity'] * $item['price'];
    }
    $calculated_total += 50; // Add delivery fee
    
    // Display results
    echo "<h3>Order Details:</h3>";
    echo "Order ID: " . $order['id'] . "<br>";
    echo "Stored Total Amount: " . $order['total_amount'] . "<br>";
    echo "Calculated Total: " . $calculated_total . "<br><br>";
    
    echo "<h3>Order Items:</h3>";
    foreach ($order_items as $item) {
        echo "Name: " . $item['product_name'] . "<br>";
        echo "Quantity: " . $item['quantity'] . "<br>";
        echo "Price: " . $item['price'] . "<br>";
        echo "---<br>";
    }
} else {
    echo "Please provide an order ID.";
}
?>

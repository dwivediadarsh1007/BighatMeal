<?php
require_once 'config.php';

// Check orders table structure
$stmt = $conn->query("DESCRIBE orders");
$orders_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check order_items table structure
$stmt = $conn->query("DESCRIBE order_items");
$order_items_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Display results
echo "<h2>Orders Table Structure</h2>";
foreach ($orders_columns as $column) {
    echo "<p>" . htmlspecialchars($column['Field'] . " - " . $column['Type']) . "</p>";
}

echo "<h2>Order Items Table Structure</h2>";
foreach ($order_items_columns as $column) {
    echo "<p>" . htmlspecialchars($column['Field'] . " - " . $column['Type']) . "</p>";
}

// Check sample order data
$stmt = $conn->query("SELECT id, total_amount FROM orders LIMIT 5");
$sample_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Sample Orders Data</h2>";
foreach ($sample_orders as $order) {
    echo "<p>Order ID: " . htmlspecialchars($order['id']) . ", Total Amount: " . htmlspecialchars($order['total_amount']) . "</p>";
}
?>

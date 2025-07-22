<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type to JSON
header('Content-Type: application/json');

// Simulate a session
session_start();
$_SESSION['user_id'] = 2; // Assuming user with ID 2 exists

// Include config
require_once 'config.php';

try {
    // Test database connection
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Test query
    $testProductId = 1; // Assuming product with ID 1 exists
    $stmt = $conn->prepare("SELECT id, name, price FROM products WHERE id = ?");
    $stmt->execute([$testProductId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        throw new Exception("Test product not found. Please check if products exist in the database.");
    }
    
    // Test cart table
    $stmt = $conn->query("SHOW TABLES LIKE 'cart'");
    if ($stmt->rowCount() === 0) {
        // Create cart table if it doesn't exist
        $conn->exec("CREATE TABLE IF NOT EXISTS `cart` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `product_id` int(11) NOT NULL,
            `quantity` int(11) NOT NULL DEFAULT '1',
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_cart_item` (`user_id`,`product_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    
    // Try to add to cart
    $userId = (int)$_SESSION['user_id'];
    $quantity = 1;
    
    // Check if item already in cart
    $stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$userId, $testProductId]);
    $existingItem = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingItem) {
        // Update quantity
        $newQuantity = $existingItem['quantity'] + $quantity;
        $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        $stmt->execute([$newQuantity, $existingItem['id']]);
        $action = 'updated';
    } else {
        // Add new item
        $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $testProductId, $quantity]);
        $action = 'added';
    }
    
    // Get updated cart count
    $stmt = $conn->prepare("SELECT COALESCE(SUM(quantity), 0) as cart_count FROM cart WHERE user_id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $cartCount = (int)($result['cart_count'] ?? 0);
    
    // Return success response
    echo json_encode([
        'status' => 'success',
        'message' => 'Item ' . $action . ' to cart successfully',
        'cart_count' => $cartCount,
        'action' => $action,
        'product_id' => $testProductId,
        'user_id' => $userId
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error',
        'error' => $e->getMessage(),
        'error_info' => $e->errorInfo ?? null,
        'trace' => $e->getTraceAsString()
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>

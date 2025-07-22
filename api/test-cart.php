<?php
// Disable error display to prevent HTML output
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Function to send JSON response
function sendJsonResponse($status, $message, $data = []) {
    http_response_code($status === 'error' ? 500 : 200);
    header('Content-Type: application/json');
    echo json_encode(['status' => $status, 'message' => $message] + $data);
    exit();
}

try {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Set test user ID (for testing only)
    $_SESSION['user_id'] = 1;

    // Include database configuration
    require_once __DIR__ . '/../config.php';

    // Check if cart table exists, create if not
    $tableCheck = $conn->query("SHOW TABLES LIKE 'cart'");
    if ($tableCheck->rowCount() === 0) {
        $sql = "CREATE TABLE IF NOT EXISTS `cart` (
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
    }

    // Test adding an item to cart
    $testProductId = 1;
    $userId = $_SESSION['user_id'];

    // Check if product exists
    $stmt = $conn->prepare("SELECT id FROM products WHERE id = ? LIMIT 1");
    $stmt->execute([$testProductId]);
    $product = $stmt->fetch();

    if (!$product) {
        // Create test product if it doesn't exist
        $conn->exec("INSERT INTO products (id, name, price, is_available) VALUES (1, 'Test Product', 9.99, 1)");
    }

    // Add to cart
    $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE quantity = quantity + 1");
    $result = $stmt->execute([$userId, $testProductId]);

    if ($result) {
        // Get updated cart count
        $stmt = $conn->prepare("SELECT COALESCE(SUM(quantity), 0) as cart_count FROM cart WHERE user_id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        
        sendJsonResponse('success', 'Test successful', [
            'cart_count' => (int)($result['cart_count'] ?? 0),
            'message' => 'Item added to cart successfully'
        ]);
    } else {
        throw new Exception('Failed to add item to cart');
    }

} catch (PDOException $e) {
    sendJsonResponse('error', 'Database error: ' . $e->getMessage());
} catch (Exception $e) {
    sendJsonResponse('error', $e->getMessage());
}

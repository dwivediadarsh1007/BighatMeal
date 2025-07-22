<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Simulate a logged-in user for testing
$_SESSION['user_id'] = 1; // Change this to a valid user ID in your database

// Include the database configuration
require_once 'config.php';

// Function to log messages
function logMessage($message) {
    $logFile = __DIR__ . '/logs/test.log';
    if (!file_exists(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

// Test database connection
function testDatabaseConnection($conn) {
    try {
        $stmt = $conn->query("SELECT 1");
        return ['success' => true, 'message' => 'Database connection successful'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()];
    }
}

// Test cart table
function testCartTable($conn) {
    try {
        // Check if cart table exists
        $tableCheck = $conn->query("SHOW TABLES LIKE 'cart'");
        if ($tableCheck->rowCount() === 0) {
            return ['success' => false, 'message' => 'Cart table does not exist'];
        }
        
        // Check cart table structure
        $columns = $conn->query("DESCRIBE cart")->fetchAll(PDO::FETCH_COLUMN);
        $requiredColumns = ['id', 'user_id', 'product_id', 'quantity'];
        $missingColumns = array_diff($requiredColumns, $columns);
        
        if (!empty($missingColumns)) {
            return ['success' => false, 'message' => 'Cart table is missing columns: ' . implode(', ', $missingColumns)];
        }
        
        return ['success' => true, 'message' => 'Cart table exists and has the correct structure'];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Error checking cart table: ' . $e->getMessage()];
    }
}

// Test adding to cart
function testAddToCart($conn, $userId, $productId = 1) {
    try {
        // First, ensure the product exists
        $stmt = $conn->prepare("SELECT id FROM products WHERE id = ? AND is_available = 1 LIMIT 1");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            return ['success' => false, 'message' => 'Test product not found or not available'];
        }
        
        // Try to add to cart
        $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE quantity = quantity + 1");
        $result = $stmt->execute([$userId, $productId]);
        
        if ($result) {
            // Get the updated cart count
            $stmt = $conn->prepare("SELECT COALESCE(SUM(quantity), 0) as cart_count FROM cart WHERE user_id = ?");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'success' => true, 
                'message' => 'Successfully added to cart',
                'cart_count' => (int)$result['cart_count']
            ];
        } else {
            return ['success' => false, 'message' => 'Failed to add to cart'];
        }
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Error adding to cart: ' . $e->getMessage()];
    }
}

// Run tests
$results = [
    'database_connection' => testDatabaseConnection($conn),
    'cart_table' => testCartTable($conn)
];

// Only test adding to cart if the cart table exists and has the correct structure
if ($results['cart_table']['success']) {
    $results['add_to_cart'] = testAddToCart($conn, $_SESSION['user_id']);
}

// Output results as JSON
header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT);

// Log results
logMessage("Test Results: " . json_encode($results, JSON_PRETTY_PRINT));
?>

<?php
// Enable full error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once 'config.php';

// Set JSON header
header('Content-Type: application/json');

// Test data
$testData = [
    'product_id' => 1,
    'quantity' => 1
];

// Simulate a session
session_start();
$_SESSION['user_id'] = 2; // Use an existing user ID

// Log the test
$logFile = __DIR__ . '/logs/test-debug.log';
$logMessage = "=== Starting Test at " . date('Y-m-d H:i:s') . " ===\n";
$logMessage .= "Test Data: " . json_encode($testData, JSON_PRETTY_PRINT) . "\n";
$logMessage .= "Session Data: " . json_encode($_SESSION, JSON_PRETTY_PRINT) . "\n\n";

// Test database connection
try {
    $logMessage .= "Testing database connection...\n";
    $conn->query('SELECT 1');
    $logMessage .= "✓ Database connection successful\n\n";
    
    // Test product query
    $logMessage .= "Testing product query...\n";
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$testData['product_id']]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        $logMessage .= "✓ Product found: " . json_encode($product, JSON_PRETTY_PRINT) . "\n\n";
        
        // Test cart insertion
        $logMessage .= "Testing cart insertion...\n";
        $conn->beginTransaction();
        
        try {
            $stmt = $conn->prepare("
                INSERT INTO cart (user_id, product_id, quantity, created_at, updated_at) 
                VALUES (?, ?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE 
                    quantity = quantity + VALUES(quantity),
                    updated_at = NOW()
            
            ");
            
            $result = $stmt->execute([
                $_SESSION['user_id'],
                $testData['product_id'],
                $testData['quantity']
            ]);
            
            if ($result) {
                $logMessage .= "✓ Cart updated successfully\n";
                $cartId = $conn->lastInsertId();
                $logMessage .= "Cart ID: " . ($cartId ?: 'N/A (updated existing item)') . "\n";
                
                // Get updated cart count
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $cartCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                $logMessage .= "Items in cart: " . $cartCount . "\n";
                
                $conn->commit();
                $logMessage .= "✓ Transaction committed\n";
                
                $response = [
                    'status' => 'success',
                    'message' => 'Test completed successfully',
                    'cart_count' => (int)$cartCount,
                    'product' => $product,
                    'log' => $logMessage
                ];
            } else {
                throw new Exception("Failed to execute cart update");
            }
            
        } catch (Exception $e) {
            $conn->rollBack();
            throw $e;
        }
        
    } else {
        $logMessage .= "✗ Product not found with ID: " . $testData['product_id'] . "\n";
        throw new Exception("Product not found");
    }
    
} catch (Exception $e) {
    $error = "Test failed: " . $e->getMessage();
    $logMessage .= "✗ $error\n";
    $logMessage .= "Error details: " . $e->getTraceAsString() . "\n";
    
    // Get database error info if available
    if (isset($stmt)) {
        $errorInfo = $stmt->errorInfo();
        if ($errorInfo[1]) {
            $logMessage .= "SQL Error: " . json_encode($errorInfo) . "\n";
        }
    }
    
    http_response_code(500);
    $response = [
        'status' => 'error',
        'message' => $error,
        'log' => $logMessage
    ];
}

// Write log to file
file_put_contents($logFile, $logMessage, FILE_APPEND);

// Output the result
echo json_encode($response, JSON_PRETTY_PRINT);
?>

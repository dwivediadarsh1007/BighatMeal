<?php
// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session and include config
session_start();
require_once '../config.php';

// Set JSON header
header('Content-Type: application/json');

try {
    // Log the incoming request
    error_log('=== REMOVE FROM CART REQUEST ===');
    error_log('POST data: ' . print_r($_POST, true));
    error_log('SESSION data: ' . ($_SESSION ? print_r($_SESSION, true) : 'No session data'));

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Not logged in', 401);
    }

    // Get and validate item ID
    $itemId = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);
    
    if (!$itemId) {
        throw new Exception('Invalid item ID', 400);
    }

    error_log('Attempting to remove item ID: ' . $itemId . ' for user ID: ' . $_SESSION['user_id']);

    // Start transaction
    $conn->beginTransaction();

    try {
        // Get the cart item
        $stmt = $conn->prepare("SELECT id, items, total_price FROM cart WHERE id = :id AND user_id = :user_id");
        $stmt->execute([
            ':id' => $itemId,
            ':user_id' => $_SESSION['user_id']
        ]);
        
        $cartItem = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$cartItem) {
            throw new Exception('Cart item not found', 404);
        }
        
        // Decode the items array
        $items = json_decode($cartItem['items'] ?? '[]', true);
        if (!is_array($items) || empty($items)) {
            throw new Exception('No items found in cart');
        }
        
        // If there's only one item, remove the entire cart record
        if (count($items) === 1) {
            $stmt = $conn->prepare("DELETE FROM cart WHERE id = :id AND user_id = :user_id");
            $result = $stmt->execute([
                ':id' => $itemId,
                ':user_id' => $_SESSION['user_id']
            ]);
        } else {
            // Remove the first item from the array
            array_shift($items);
            
            // Recalculate the total price (you might want to adjust this based on your logic)
            $newTotal = 0;
            foreach ($items as $item) {
                $newTotal += ($item['price'] * ($item['quantity'] ?? 1));
            }
            
            // Update the cart with the remaining items
            $stmt = $conn->prepare("UPDATE cart SET items = :items, total_price = :total_price WHERE id = :id AND user_id = :user_id");
            $result = $stmt->execute([
                ':items' => json_encode($items),
                ':total_price' => $newTotal,
                ':id' => $itemId,
                ':user_id' => $_SESSION['user_id']
            ]);
        }

        if (!$result) {
            throw new Exception('Failed to update cart');
        }

        // Commit the transaction
        $conn->commit();
        
        error_log('Successfully removed item from cart');
        
        echo json_encode([
            'success' => true,
            'message' => 'Item removed successfully',
            'item_id' => $itemId
        ]);
        
    } catch (Exception $e) {
        // Rollback the transaction on error
        $conn->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    error_log('Database Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred',
        'error' => $e->getMessage()
    ]);
    
} catch (Exception $e) {
    $statusCode = $e->getCode() ?: 500;
    error_log('Error: ' . $e->getMessage());
    http_response_code($statusCode);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'code' => $statusCode
    ]);
}

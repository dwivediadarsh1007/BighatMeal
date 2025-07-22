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
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Not logged in', 401);
    }

    // Get and validate input
    $itemId = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1]
    ]);
    
    if (!$itemId || !$quantity) {
        throw new Exception('Invalid input parameters', 400);
    }

    // Start transaction
    $conn->beginTransaction();

    try {
        // Get the cart item
        $stmt = $conn->prepare("SELECT * FROM cart WHERE id = :id AND user_id = :user_id");
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
        
        // Update the quantity in the first item (assuming one item per cart entry for simplicity)
        if (isset($items[0])) {
            $items[0]['quantity'] = $quantity;
            
            // Recalculate the total price
            $totalPrice = 0;
            foreach ($items as $item) {
                $itemPrice = $item['price'] ?? 0;
                $itemQty = $item['quantity'] ?? 1;
                $totalPrice += $itemPrice * $itemQty;
            }
            
            // Update the cart
            $updateStmt = $conn->prepare("UPDATE cart SET items = :items, total_price = :total_price, updated_at = NOW() WHERE id = :id");
            $result = $updateStmt->execute([
                ':items' => json_encode($items),
                ':total_price' => $totalPrice,
                ':id' => $itemId
            ]);
            
            if (!$result) {
                throw new Exception('Failed to update cart');
            }
            
            // Commit the transaction
            $conn->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Cart updated successfully',
                'item_id' => $itemId,
                'quantity' => $quantity,
                'total_price' => $totalPrice
            ]);
        } else {
            throw new Exception('No items found in cart entry');
        }
        
    } catch (Exception $e) {
        // Rollback the transaction on error
        $conn->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
}

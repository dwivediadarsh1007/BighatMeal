<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Get POST data
$itemId = $_POST['item_id'] ?? null;
$quantity = intval($_POST['quantity'] ?? 1);

// Validate input
if (!$itemId || $quantity < 1) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

try {
    // Get the current cart items
    $stmt = $conn->prepare("SELECT * FROM cart WHERE id = ? AND user_id = ?");
    $stmt->execute([$itemId, $_SESSION['user_id']]);
    $cartItem = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$cartItem) {
        throw new Exception('Cart item not found');
    }
    
    // Decode the items JSON
    $items = json_decode($cartItem['items'], true);
    
    // Update the quantity in the items array
    // Note: This assumes a single item per cart entry
    // If you have multiple items per cart entry, you'll need to adjust this logic
    if (isset($items[0])) {
        $items[0]['quantity'] = $quantity;
    }
    
    // Recalculate totals based on the new quantity
    $totalPrice = 0;
    $totalCalories = 0;
    $totalProtein = 0;
    $totalCarbs = 0;
    $totalFat = 0;
    $totalFiber = 0;
    
    foreach ($items as $item) {
        $totalPrice += $item['price'] * $item['quantity'];
        $totalCalories += $item['calories'] * $item['quantity'];
        $totalProtein += $item['protein'] * $item['quantity'];
        $totalCarbs += $item['carbs'] * $item['quantity'];
        $totalFat += $item['fat'] * $item['quantity'];
        $totalFiber += $item['fiber'] * $item['quantity'];
    }
    
    // Update the cart item in the database
    $stmt = $conn->prepare("UPDATE cart SET 
        items = ?, 
        total_price = ?, 
        total_calories = ?, 
        total_protein = ?, 
        total_carbs = ?, 
        total_fat = ?, 
        total_fiber = ?,
        updated_at = NOW()
        WHERE id = ? AND user_id = ?");
    
    $result = $stmt->execute([
        json_encode($items),
        $totalPrice,
        $totalCalories,
        $totalProtein,
        $totalCarbs,
        $totalFat,
        $totalFiber,
        $itemId,
        $_SESSION['user_id']
    ]);
    
    if (!$result) {
        throw new Exception('Failed to update cart item');
    }
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

<?php
// Start session first
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['items']) || !isset($data['type'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

// Set content type after session checks
header('Content-Type: application/json');
require_once '../config.php';

try {
    // Log received data
    error_log('Received cart data: ' . print_r($data, true));
    
    // Validate input
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in');
    }
    
    if (!isset($data['items']) || !is_array($data['items'])) {
        throw new Exception('Invalid items data format');
    }
    
    if (empty($data['items'])) {
        throw new Exception('No items selected');
    }
    
    // Validate meal type
    $validTypes = ['custom-meal', 'standard'];
    if (!in_array($data['type'], $validTypes)) {
        throw new Exception('Invalid meal type. Must be one of: ' . implode(', ', $validTypes));
    }
    
    $userId = $_SESSION['user_id'];
    $mealType = $data['type'];
    $items = $data['items'];
    
    // Calculate totals
    $totalCalories = 0;
    $totalProtein = 0;
    $totalCarbs = 0;
    $totalFat = 0;
    $totalFiber = 0;
    $totalPrice = 0;

    // Process items
    $processedItems = [];
    foreach ($items as $item) {
        error_log('Processing item: ' . print_r($item, true));
        
        // Validate required fields
        $requiredFields = ['name', 'quantity', 'price', 'calories', 'protein', 'carbs', 'fat', 'fiber'];
        foreach ($requiredFields as $field) {
            if (!isset($item[$field])) {
                error_log("Missing required field: $field in item: " . print_r($item, true));
                $item[$field] = 0; // Set default value
            }
        }
        
        // Store price in rupees (not paise)
        $price = (float)$item['price'];
        $calories = (float)($item['calories'] ?? 0);
        $protein = (float)($item['protein'] ?? 0);
        $carbs = (float)($item['carbs'] ?? 0);
        $fat = (float)($item['fat'] ?? 0);
        $fiber = (float)($item['fiber'] ?? 0);
        
        $cartItem = [
            'name' => $item['name'],
            'quantity' => (int)$item['quantity'],
            'calories' => $calories,
            'protein' => $protein,
            'carbs' => $carbs,
            'fat' => $fat,
            'fiber' => $fiber,
            'price' => $price,  // Store in rupees
            'image_url' => $item['image_url'] ?? 'images/default-food.jpg'
        ];
        
        $processedItems[] = $cartItem;
        
        // Debug log
        error_log(sprintf(
            'Adding item: %s - Price: ₹%.2f - Calories: %.1f - Protein: %.1fg - Carbs: %.1fg - Fat: %.1fg - Fiber: %.1fg',
            $item['name'],
            $price,
            $calories,
            $protein,
            $carbs,
            $fat,
            $fiber
        ));
        
        // Update totals
        $totalCalories += $calories * $cartItem['quantity'];
        $totalProtein += $protein * $cartItem['quantity'];
        $totalCarbs += $carbs * $cartItem['quantity'];
        $totalFat += $fat * $cartItem['quantity'];
        $totalFiber += $fiber * $cartItem['quantity'];
        $totalPrice += $price * $cartItem['quantity'];
    }

    // Log processed items
    error_log('Processed items: ' . print_r($processedItems, true));
    
    // Insert into cart
    $stmt = $conn->prepare("INSERT INTO cart (user_id, meal_type, items, total_calories, total_protein, total_carbs, total_fat, total_fiber, total_price, created_at) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . $conn->errorInfo()[2]);
    }
    
    $result = $stmt->execute([
        $userId,
        $mealType,
        json_encode($processedItems),
        $totalCalories,
        $totalProtein,
        $totalCarbs,
        $totalFat,
        $totalFiber,
        $totalPrice
    ]);
    
    if (!$result) {
        throw new Exception('Failed to execute statement: ' . print_r($stmt->errorInfo(), true));
    }

    // Log successful insert
    error_log('Successfully added to cart: User ID: ' . $userId . ', Items: ' . count($processedItems));
    
    echo json_encode([
        'success' => true, 
        'message' => 'Successfully added to cart',
        'debug' => [
            'user_id' => $userId,
            'item_count' => count($processedItems),
            'total_price' => $totalPrice
        ]
    ]);
} catch (Exception $e) {
    error_log('Add to cart error: ' . $e->getMessage());
    error_log('Error details: ' . print_r($e, true));
    
    // Include error details in response for debugging
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage(),
        'error_details' => [
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}
?>

<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to the user

// Function to send JSON response and exit
function sendJsonResponse($status, $message = '', $data = []) {
    // Clear any previous output
    if (ob_get_level()) {
        ob_clean();
    }
    
    // Set the content type
    header('Content-Type: application/json');
    
    // Set the HTTP status code
    http_response_code($status === 'error' ? 500 : 200);
    
    // Build the response array
    $response = ['status' => $status];
    if ($message !== '') {
        $response['message'] = $message;
    }
    $response = array_merge($response, $data);
    
    // Encode and output the response
    echo json_encode($response);
    exit();
}

// Log errors to a file
function logError($error) {
    $logFile = __DIR__ . '/../logs/error.log';
    if (!file_exists(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $error" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

try {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401); // Unauthorized
        sendJsonResponse('error', 'Please login to view cart count');
    }

    // Include database configuration
    require_once __DIR__ . '/../config.php';

    // Check if cart table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'cart'");
    if ($tableCheck->rowCount() === 0) {
        // Cart table doesn't exist, return 0 count
        sendJsonResponse('success', 'Cart is empty', ['cart_count' => 0]);
    }

    // Get cart items for the user
    $stmt = $conn->prepare("SELECT items FROM cart WHERE user_id = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . implode(" ", $conn->errorInfo()));
    }
    
    $stmt->execute([$_SESSION['user_id']]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate total quantity from all items
    $totalQuantity = 0;
    foreach ($cartItems as $cartItem) {
        $items = json_decode($cartItem['items'], true);
        if (is_array($items)) {
            foreach ($items as $item) {
                $totalQuantity += isset($item['quantity']) ? (int)$item['quantity'] : 0;
            }
        }
    }
    
    // Return success response with the total count
    sendJsonResponse('success', 'Cart count retrieved', [
        'cart_count' => $totalQuantity
    ]);
    
} catch (PDOException $e) {
    // Database error
    $errorMsg = "Database error: " . $e->getMessage();
    logError($errorMsg);
    http_response_code(500);
    sendJsonResponse('error', 'Database error occurred while fetching cart count', [
        'error' => $errorMsg
    ]);
} catch (Exception $e) {
    // Other errors
    $errorMsg = $e->getMessage();
    logError("Error in get-cart-count.php: " . $errorMsg);
    http_response_code(500);
    sendJsonResponse('error', 'An error occurred while fetching cart count', [
        'error' => $errorMsg
    ]);
}

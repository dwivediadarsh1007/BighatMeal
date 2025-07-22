<?php
// Enable full error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log');

// Create logs directory if it doesn't exist
if (!file_exists(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0777, true);
}

// Set headers for JSON response
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: 0');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Function to log errors
function logError($message) {
    $logFile = __DIR__ . '/logs/error.log';
    $timestamp = date('[Y-m-d H:i:s]');
    
    // If message is an array or object, convert to JSON
    if (is_array($message) || is_object($message)) {
        $message = json_encode($message, JSON_PRETTY_PRINT);
    }
    
    // Log to both PHP error log and our custom log file
    error_log("$timestamp $message\n", 3, $logFile);
    error_log("$timestamp $message\n");
}

// Function to send JSON response
function sendJsonResponse($status, $message = '', $data = [], $httpCode = null) {
    // Clear any previous output
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set the content type and no cache headers
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: 0');
    
    // Set the HTTP status code if not provided
    if ($httpCode === null) {
        $httpCode = ($status === 'error') ? 500 : 200;
    }
    http_response_code($httpCode);
    
    // Build the response array
    $response = [
        'status' => $status,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    // Only include data if it's not empty
    if (!empty($data)) {
        $response['data'] = $data;
    }
    
    // Log errors
    if ($status === 'error') {
        logError([
            'ERROR_RESPONSE' => [
                'message' => $message,
                'data' => $data,
                'http_code' => $httpCode,
                'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
            ]
        ]);
    }
    
    // Encode and output the response
    $jsonResponse = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    
    // If JSON encoding fails, send a minimal error response
    if ($jsonResponse === false) {
        $jsonResponse = json_encode([
            'status' => 'error',
            'message' => 'Failed to encode response: ' . json_last_error_msg(),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
    echo $jsonResponse;
    exit();
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
require_once 'config.php';

// Log the start of the script
logError(['SCRIPT_START' => [
    'time' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'post' => $_POST,
    'get' => $_GET,
    'input' => file_get_contents('php://input'),
    'session' => session_status() === PHP_SESSION_ACTIVE ? $_SESSION : 'session not started'
]]);

// Ensure database connection is established
if (!isset($conn) || $conn === null) {
    $error = 'Database connection failed in add-to-cart.php';
    logError(['DATABASE_ERROR' => $error]);
    sendJsonResponse('error', $error, [], 500);
}

try {
    // Test the database connection
    $conn->query('SELECT 1');
    logError('Database connection test successful');
} catch (PDOException $e) {
    $error = 'Database connection test failed: ' . $e->getMessage();
    logError(['DATABASE_CONNECTION_ERROR' => [
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]]);
    sendJsonResponse('error', $error, [], 500);
}

// Log request for debugging
logError('Add to cart request: ' . json_encode([
    'post' => $_POST,
    'session' => isset($_SESSION) ? $_SESSION : [],
    'user_id' => $_SESSION['user_id'] ?? null
]));

// Function to create cart table if it doesn't exist
function ensureCartTableExists($conn) {
    try {
        // First check if table exists
        $stmt = $conn->query("SHOW TABLES LIKE 'cart'");
        $tableExists = $stmt->rowCount() > 0;
        
        if (!$tableExists) {
            logError("Cart table does not exist, creating it...");
            
            // Disable foreign key checks temporarily
            $conn->exec("SET FOREIGN_KEY_CHECKS = 0");
            
            // Create the table
            $sql = "CREATE TABLE `cart` (
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
            logError("Cart table created successfully");
            
            // Re-enable foreign key checks
            $conn->exec("SET FOREIGN_KEY_CHECKS = 1");
        } else {
            logError("Cart table already exists");
        }
        
        return true;
        
    } catch (PDOException $e) {
        $error = "Error with cart table: " . $e->getMessage() . "\n" . $e->getTraceAsString();
        logError($error);
        throw $e; // Re-throw to be caught by the caller
    }
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $error = [
        'message' => 'User not logged in',
        'session_status' => session_status(),
        'session_data' => $_SESSION,
        'session_id' => session_id()
    ];
    
    logError(['AUTH_ERROR' => $error]);
    sendJsonResponse('error', 'Please login to add items to cart', $error, 401);
}

$userId = (int)$_SESSION['user_id'];
logError(['USER_SESSION' => [
    'user_id' => $userId,
    'session_id' => session_id()
]]);

// Get the request body
$input = [];
$requestMethod = $_SERVER['REQUEST_METHOD'];
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$rawInput = file_get_contents('php://input');

logError(['REQUEST_DETAILS' => [
    'method' => $requestMethod,
    'content_type' => $contentType,
    'raw_input' => $rawInput,
    'post' => $_POST,
    'get' => $_GET
]]);

// Parse input based on content type
if (strpos($contentType, 'application/json') !== false) {
    $input = json_decode($rawInput, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        logError(['JSON_PARSE_ERROR' => [
            'error' => json_last_error_msg(),
            'raw_input' => $rawInput
        ]]);
        sendJsonResponse('error', 'Invalid JSON data', ['error' => json_last_error_msg()], 400);
    }
} else if ($requestMethod === 'POST') {
    $input = $_POST;
} else {
    parse_str($rawInput, $input);
}

logError(['PARSED_INPUT' => $input]);

// Validate input
if (empty($input['product_id']) || !is_numeric($input['product_id'])) {
    $error = [
        'message' => 'Invalid product ID',
        'product_id' => $input['product_id'] ?? 'not provided',
        'input' => $input
    ];
    logError(['VALIDATION_ERROR' => $error]);
    sendJsonResponse('error', 'Invalid product ID', $error, 400);
}

$productId = (int)$input['product_id'];
$quantity = isset($input['quantity']) ? max(1, (int)$input['quantity']) : 1;

logError(['PROCESSING_REQUEST' => [
    'user_id' => $userId,
    'product_id' => $productId,
    'quantity' => $quantity
]]);

// Log the request with more details
logError(sprintf(
    "Add to cart request - User ID: %d, Product ID: %d, Quantity: %d, Session: %s",
    $_SESSION['user_id'],
    $productId,
    $quantity,
    json_encode($_SESSION)
));

// Check if product exists and is available
try {
    $query = "SELECT id, name, price, is_available FROM products WHERE id = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Failed to prepare product query: " . implode(" ", $conn->errorInfo()));
    }
    
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        logError("Product not found - ID: $productId");
        http_response_code(404);
        sendJsonResponse('error', 'Product not found');
        exit;
    }
    
    if (!$product['is_available']) {
        logError("Product not available - ID: $productId, Name: " . $product['name']);
        http_response_code(400);
        sendJsonResponse('error', 'This product is currently out of stock');
        exit;
    }
} catch (PDOException $e) {
    $error = "Database error checking product: " . $e->getMessage();
    logError($error);
    http_response_code(500);
    sendJsonResponse('error', 'Error checking product availability', [
        'error' => $error
    ]);
}

// Ensure cart table exists
try {
    if (!ensureCartTableExists($conn)) {
        throw new Exception("Failed to ensure cart table exists");
    }
} catch (Exception $e) {
    logError("Cart table check failed: " . $e->getMessage());
    sendJsonResponse('error', 'Failed to initialize shopping cart. Please try again later.', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}

try {
    // Begin transaction
    $conn->beginTransaction();
    
    // Check if product is already in cart
    $stmt = $conn->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
    if (!$stmt) {
        throw new Exception("Prepare SELECT failed: " . implode(" ", $conn->errorInfo()));
    }
    
    if (!$stmt->execute([$userId, $productId])) {
        throw new Exception("Execute SELECT failed: " . implode(" ", $stmt->errorInfo()));
    }
    
    $existingItem = $stmt->fetch(PDO::FETCH_ASSOC);
    $action = '';
    $newQuantity = $quantity;
    $message = '';
    
    try {
        if ($existingItem) {
            // Update quantity if item already in cart
            $newQuantity = $existingItem['quantity'] + $quantity;
            $updateStmt = $conn->prepare("UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ?");
            if (!$updateStmt->execute([$newQuantity, $existingItem['id']])) {
                throw new Exception("Execute UPDATE failed: " . implode(" ", $updateStmt->errorInfo()));
            }
            $message = 'Cart updated successfully';
            $action = 'updated';
        } else {
            // Get product price
            $priceStmt = $conn->prepare("SELECT price FROM products WHERE id = ?");
            $priceStmt->execute([$productId]);
            $product = $priceStmt->fetch(PDO::FETCH_ASSOC);
            $price = $product ? (float)$product['price'] : 0;
            
            // Add new item to cart with price
            $insertStmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity, total_price) VALUES (?, ?, ?, ?)");
            if (!$insertStmt->execute([$userId, $productId, $quantity, $price * $quantity])) {
                // If it's a duplicate entry, try to update instead
                if ($conn->errorCode() == '23000') {
                    // Get product price for update
                    $priceStmt = $conn->prepare("SELECT price FROM products WHERE id = ?");
                    $priceStmt->execute([$productId]);
                    $product = $priceStmt->fetch(PDO::FETCH_ASSOC);
                    $price = $product ? (float)$product['price'] : 0;
                    
                    // Update cart with new quantity and price
                    $updateStmt = $conn->prepare("UPDATE cart SET quantity = quantity + ?, total_price = (quantity + ?) * ?, updated_at = NOW() WHERE user_id = ? AND product_id = ?");
                    if (!$updateStmt->execute([$quantity, $quantity, $price, $userId, $productId])) {
                        throw new Exception("Failed to update cart: " . implode(" ", $updateStmt->errorInfo()));
                    }
                    $message = 'Cart updated successfully (duplicate resolved)';
                    $action = 'updated';
                } else {
                    throw new Exception("Execute INSERT failed: " . implode(" ", $insertStmt->errorInfo()));
                }
            } else {
                $message = 'Item added to cart successfully';
                $action = 'added';
            }
        }
        
        logError([
            'CART_ACTION' => [
                'action' => $action,
                'user_id' => $userId,
                'product_id' => $productId,
                'quantity' => $quantity,
                'existing_quantity' => $existingItem['quantity'] ?? 0,
                'new_quantity' => $newQuantity,
                'message' => $message
            ]
        ]);
        
    } catch (PDOException $e) {
        // If it's a duplicate entry, try to update instead
        if ($e->getCode() == '23000' && strpos($e->getMessage(), 'Duplicate entry') !== false) {
            logError(['DUPLICATE_ENTRY' => [
                'message' => 'Duplicate entry detected, attempting update instead',
                'user_id' => $userId,
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]]);
            
            // Get product price for update
            $priceStmt = $conn->prepare("SELECT price FROM products WHERE id = ?");
            $priceStmt->execute([$productId]);
            $product = $priceStmt->fetch(PDO::FETCH_ASSOC);
            $price = $product ? (float)$product['price'] : 0;
            
            // Update existing item with new quantity and price
            $updateStmt = $conn->prepare("UPDATE cart SET quantity = quantity + ?, total_price = (quantity + ?) * ?, updated_at = NOW() WHERE user_id = ? AND product_id = ?");
            if (!$updateStmt->execute([$quantity, $quantity, $price, $userId, $productId])) {
                throw new Exception("Failed to update cart after duplicate entry: " . implode(" ", $updateStmt->errorInfo()));
            }
            
            if ($updateStmt->rowCount() > 0) {
                $message = 'Cart updated successfully (duplicate resolved)';
                $action = 'updated';
                logError(['DUPLICATE_RESOLVED' => [
                    'user_id' => $userId,
                    'product_id' => $productId,
                    'quantity_added' => $quantity
                ]]);
            } else {
                throw new Exception("No rows affected when trying to resolve duplicate entry");
            }
        } else {
            throw $e; // Re-throw if it's not a duplicate entry error
        }
    }
    
    // Get updated cart count
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = ?");
    $stmt->execute([$userId]);
    $cartCount = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Commit transaction
    $conn->commit();
    
    logError(sprintf(
        'Cart %s - User ID: %d, Product ID: %d, Quantity: %d, Cart Count: %d',
        $action, $userId, $productId, $quantity, $cartCount
    ));
    
    // Send success response
    sendJsonResponse('success', "Item $action to cart successfully", [
        'cart_count' => $cartCount,
        'action' => $action,
        'product_id' => $productId,
        'user_id' => $userId
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    // Log the error
    $errorMsg = 'Error adding to cart: ' . $e->getMessage();
    $errorDetails = [
        'error' => $errorMsg,
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ];
    
    logError('Cart Error: ' . json_encode($errorDetails));
    
    // Send error response
    http_response_code(500);
    sendJsonResponse('error', 'Failed to add item to cart', [
        'message' => 'An error occurred while updating your cart',
        'details' => $errorDetails
    ]);
}

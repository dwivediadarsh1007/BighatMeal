<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/plain');

echo "=== Testing Cart Functionality ===\n\n";

// Include config
try {
    require_once 'config.php';
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Test user ID (use an existing user ID from your database)
    $testUserId = 1; // Change this to an existing user ID
    
    // Test product ID (use an existing product ID from your database)
    $testProductId = 1; // Change this to an existing product ID
    $testQuantity = 1;
    
    echo "Testing with user ID: $testUserId, product ID: $testProductId, quantity: $testQuantity\n\n";
    
    // Step 1: Check if user exists
    echo "1. Checking if user exists... ";
    $stmt = $conn->prepare("SELECT id, username FROM users WHERE id = ?");
    $stmt->execute([$testUserId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        die("✗ User with ID $testUserId does not exist. Please use a valid user ID.\n");
    }
    echo "✓ Found user: {$user['username']} (ID: {$user['id']})\n";
    
    // Step 2: Check if product exists and is available
    echo "2. Checking if product exists and is available... ";
    $stmt = $conn->prepare("SELECT id, name, price, is_available FROM products WHERE id = ?");
    $stmt->execute([$testProductId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        die("✗ Product with ID $testProductId does not exist.\n");
    }
    
    if (!$product['is_available']) {
        die("✗ Product '{$product['name']}' is not available.\n");
    }
    echo "✓ Found product: {$product['name']} (Price: {$product['price']})\n";
    
    // Step 3: Check cart table structure
    echo "3. Verifying cart table structure... ";
    $stmt = $conn->query("DESCRIBE `cart`");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $requiredColumns = ['id', 'user_id', 'product_id', 'quantity', 'created_at', 'updated_at'];
    $missingColumns = array_diff($requiredColumns, $columns);
    
    if (!empty($missingColumns)) {
        die("✗ Cart table is missing required columns: " . implode(', ', $missingColumns) . "\n");
    }
    echo "✓ Cart table structure is valid\n";
    
    // Step 4: Test adding to cart
    echo "4. Testing add to cart...\n";
    
    // First, clear any existing cart items for this test
    $conn->exec("DELETE FROM cart WHERE user_id = $testUserId AND product_id = $testProductId");
    
    // Add item to cart
    $stmt = $conn->prepare("
        INSERT INTO cart (user_id, product_id, quantity, created_at, updated_at)
        VALUES (?, ?, ?, NOW(), NOW())
        ON DUPLICATE KEY UPDATE 
            quantity = quantity + VALUES(quantity),
            updated_at = NOW()
    ");
    
    try {
        $stmt->execute([$testUserId, $testProductId, $testQuantity]);
        $cartId = $conn->lastInsertId();
        echo "   ✓ Added product to cart\n";
        
        // Verify the item was added
        $stmt = $conn->prepare("
            SELECT c.*, p.name, p.price 
            FROM cart c
            JOIN products p ON c.product_id = p.id
            WHERE c.user_id = ? AND c.product_id = ?
        ");
        $stmt->execute([$testUserId, $testProductId]);
        $cartItem = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$cartItem) {
            throw new Exception("Failed to verify cart item");
        }
        
        echo "   ✓ Verified cart item: {$cartItem['name']} (Quantity: {$cartItem['quantity']}, Price: {$cartItem['price']})\n";
        
        // Test updating quantity
        echo "5. Testing quantity update...\n";
        $updateQuantity = 2;
        $stmt = $conn->prepare("
            UPDATE cart 
            SET quantity = ?, updated_at = NOW()
            WHERE user_id = ? AND product_id = ?
        ");
        $stmt->execute([$updateQuantity, $testUserId, $testProductId]);
        
        // Verify the update
        $stmt = $conn->prepare("SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$testUserId, $testProductId]);
        $updatedItem = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($updatedItem['quantity'] == $updateQuantity) {
            echo "   ✓ Successfully updated quantity to {$updateQuantity}\n";
        } else {
            echo "   ✗ Failed to update quantity (expected: {$updateQuantity}, got: {$updatedItem['quantity']})\n";
        }
        
        // Clean up (remove test item)
        $conn->exec("DELETE FROM cart WHERE user_id = $testUserId AND product_id = $testProductId");
        
    } catch (PDOException $e) {
        echo "   ✗ Error adding to cart: " . $e->getMessage() . "\n";
        if (isset($e->errorInfo)) {
            echo "   SQL State: " . $e->errorInfo[0] . "\n";
            echo "   Driver Error: " . $e->errorInfo[2] . "\n";
        }
    }
    
    echo "\n=== Test Complete ===\n";
    echo "If you see all checkmarks (✓), the cart functionality is working correctly.\n";
    
} catch (PDOException $e) {
    echo "\n✗ Database Error: " . $e->getMessage() . "\n";
    if (isset($e->errorInfo)) {
        echo "SQL State: " . $e->errorInfo[0] . "\n";
        echo "Driver Error: " . $e->errorInfo[2] . "\n";
    }
}
?>

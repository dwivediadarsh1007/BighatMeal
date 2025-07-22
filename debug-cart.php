<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Simulate a logged-in user (uncomment and set a valid user ID)
// $_SESSION['user_id'] = 1;

// Database connection
try {
    $host = 'localhost';
    $dbname = 'food_delivery';
    $username = 'root';
    $password = '';
    
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
    ]);
    
    echo "<h2>Database Connection:</h2>";
    echo "<p>✓ Connected to database successfully</p>";
    
    // Check if cart table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'cart'");
    $cartTableExists = $stmt->rowCount() > 0;
    
    echo "<h2>Cart Table Check:</h2>";
    if ($cartTableExists) {
        echo "<p>✓ Cart table exists</p>";
        
        // Show cart table structure
        $stmt = $conn->query("DESCRIBE cart");
        echo "<h3>Cart Table Structure:</h3>";
        echo "<pre>";
        print_r($stmt->fetchAll());
        echo "</pre>";
    } else {
        echo "<p>✗ Cart table does not exist</p>";
    }
    
    // Check if user is logged in
    echo "<h2>Session Information:</h2>";
    if (isset($_SESSION['user_id'])) {
        echo "<p>✓ User is logged in (User ID: " . $_SESSION['user_id'] . ")</p>";
        
        // Check if user exists in database
        $stmt = $conn->prepare("SELECT id, username FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if ($user) {
            echo "<p>✓ User exists in database (Username: " . htmlspecialchars($user['username']) . ")</p>";
        } else {
            echo "<p>✗ User does not exist in database</p>";
        }
    } else {
        echo "<p>✗ User is not logged in</p>";
    }
    
    // Test product (change ID as needed)
    $testProductId = 1;
    echo "<h2>Product Check (ID: $testProductId):</h2>";
    
    $stmt = $conn->prepare("SELECT id, name, price, is_available FROM products WHERE id = ?");
    $stmt->execute([$testProductId]);
    $product = $stmt->fetch();
    
    if ($product) {
        echo "<p>✓ Product found: " . htmlspecialchars($product['name']) . " (₹" . $product['price'] . ")</p>";
        echo "<p>Status: " . ($product['is_available'] ? 'Available' : 'Out of Stock') . "</p>";
    } else {
        echo "<p>✗ Product not found</p>";
    }
    
    // Test cart functionality if user is logged in and product exists
    if (isset($_SESSION['user_id']) && $product) {
        echo "<h2>Cart Test:</h2>";
        
        try {
            // Test adding to cart
            $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE quantity = quantity + 1");
            $result = $stmt->execute([$_SESSION['user_id'], $testProductId]);
            
            if ($result) {
                echo "<p>✓ Successfully added product to cart</p>";
                
                // Show cart contents
                $stmt = $conn->prepare("SELECT c.*, p.name, p.price FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $cartItems = $stmt->fetchAll();
                
                if (count($cartItems) > 0) {
                    echo "<h3>Cart Contents:</h3>";
                    echo "<table border='1' cellpadding='8'>";
                    echo "<tr><th>Product</th><th>Quantity</th><th>Price</th><th>Total</th></tr>";
                    
                    $grandTotal = 0;
                    foreach ($cartItems as $item) {
                        $total = $item['quantity'] * $item['price'];
                        $grandTotal += $total;
                        
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($item['name']) . "</td>";
                        echo "<td>" . $item['quantity'] . "</td>";
                        echo "<td>₹" . number_format($item['price'], 2) . "</td>";
                        echo "<td>₹" . number_format($total, 2) . "</td>";
                        echo "</tr>";
                    }
                    
                    echo "<tr><td colspan='3'><strong>Grand Total:</strong></td><td><strong>₹" . number_format($grandTotal, 2) . "</strong></td></tr>";
                    echo "</table>";
                } else {
                    echo "<p>No items in cart</p>";
                }
            } else {
                echo "<p>✗ Failed to add product to cart</p>";
            }
        } catch (PDOException $e) {
            echo "<p>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        }
    }
    
} catch (PDOException $e) {
    echo "<h2>Database Error:</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>

<h2>Debug Information:</h2>
<h3>Session Data:</h3>
<pre><?php print_r($_SESSION); ?></pre>

<h3>POST Data:</h3>
<pre><?php print_r($_POST); ?></pre>

<h3>GET Data:</h3>
<pre><?php print_r($_GET); ?></pre>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
    h2 { color: #333; border-bottom: 1px solid #eee; padding-bottom: 5px; }
    .success { color: green; }
    .error { color: red; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
    table { border-collapse: collapse; margin: 10px 0; }
    th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
    th { background-color: #f2f2f2; }
</style>

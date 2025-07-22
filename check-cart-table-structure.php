<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Include config
require_once 'config.php';

echo "<h1>Cart Debug Information</h1>";

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        die("<p>Error: User is not logged in. <a href='login.php'>Please log in</a>.</p>");
    }
    
    $user_id = $_SESSION['user_id'];
    echo "<h2>User ID: $user_id</h2>";
    
    // Check if cart table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'cart'");
    if ($stmt->rowCount() === 0) {
        die("<p>Error: Cart table does not exist in the database.</p>");
    }
    
    // Show cart table structure
    echo "<h2>Cart Table Structure</h2>";
    $stmt = $conn->query("DESCRIBE cart");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Show cart items for current user
    echo "<h2>Cart Items for User ID: $user_id</h2>";
    $stmt = $conn->prepare("SELECT * FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($items) === 0) {
        echo "<p>No items found in cart for this user.</p>";
    } else {
        echo "<table border='1' cellpadding='5'>";
        // Table header
        echo "<tr>";
        foreach (array_keys($items[0]) as $column) {
            echo "<th>" . htmlspecialchars($column) . "</th>";
        }
        echo "</tr>";
        
        // Table rows
        foreach ($items as $item) {
            echo "<tr>";
            foreach ($item as $key => $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Show products in cart with details
    echo "<h2>Cart Items with Product Details</h2>";
    $stmt = $conn->prepare("
        SELECT c.*, p.name, p.price, p.image, p.calories, p.protein, p.carbs, p.fat, p.fiber 
        FROM cart c 
        LEFT JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($items) === 0) {
        echo "<p>No items with product details found in cart.</p>";
    } else {
        echo "<table border='1' cellpadding='5'>";
        // Table header
        echo "<tr>";
        foreach (array_keys($items[0]) as $column) {
            echo "<th>" . htmlspecialchars($column) . "</th>";
        }
        echo "</tr>";
        
        // Table rows
        foreach ($items as $item) {
            echo "<tr>";
            foreach ($item as $key => $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (PDOException $e) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px 0;'>";
    echo "<h3>Database Error:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

// Show session data
echo "<h2>Session Data</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
?>

<h2>Debug Links</h2>
<ul>
    <li><a href="cart.php">View Cart</a></li>
    <li><a href="bestsellers.php">View Bestsellers</a></li>
    <li><a href="add-to-cart.php?product_id=1&quantity=1">Add Test Item to Cart</a> (Product ID: 1, Qty: 1)</li>
</ul>

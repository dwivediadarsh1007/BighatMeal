<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/plain');

echo "=== Fixing Products Table ===\n\n";

// Include config
try {
    require_once 'config.php';
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if image column exists
    $stmt = $conn->query("SHOW COLUMNS FROM products LIKE 'image'");
    if ($stmt->rowCount() === 0) {
        echo "Adding 'image' column to products table...\n";
        $conn->exec("ALTER TABLE products ADD COLUMN image VARCHAR(255) DEFAULT NULL AFTER description");
        echo "✓ Added 'image' column to products table\n";
    } else {
        echo "✓ 'image' column already exists in products table\n";
    }
    
    // Add default image if not set
    echo "\nSetting default images for products...\n";
    $defaultImage = 'default-food.jpg';
    $stmt = $conn->prepare("UPDATE products SET image = ? WHERE image IS NULL OR image = ''");
    $stmt->execute([$defaultImage]);
    $updated = $stmt->rowCount();
    echo "✓ Updated $updated products with default image\n";
    
    // Verify the changes
    echo "\n=== Verification ===\n";
    $stmt = $conn->query("SELECT id, name, image FROM products LIMIT 5");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($products) > 0) {
        echo "Sample products with images:\n";
        foreach ($products as $product) {
            echo "- ID: {$product['id']}, Name: {$product['name']}, Image: {$product['image']}\n";
        }
    }
    
    echo "\n✓ Products table has been updated successfully\n";
    
} catch (PDOException $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    if (isset($e->errorInfo)) {
        echo "SQL State: " . $e->errorInfo[0] . "\n";
        echo "Driver Error: " . $e->errorInfo[2] . "\n";
    }
}
?>

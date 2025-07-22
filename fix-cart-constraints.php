<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/plain');

echo "=== Fixing Cart Table Constraints ===\n\n";

// Include config
try {
    require_once 'config.php';
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Disable foreign key checks temporarily
    $conn->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Drop existing foreign key constraints if they exist
    $stmt = $conn->query("
        SELECT CONSTRAINT_NAME 
        FROM information_schema.TABLE_CONSTRAINTS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'cart' 
        AND CONSTRAINT_TYPE = 'FOREIGN KEY'
    ");
    
    $constraints = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($constraints as $constraint) {
        echo "Dropping foreign key constraint: $constraint\n";
        $conn->exec("ALTER TABLE `cart` DROP FOREIGN KEY `$constraint`");
    }
    
    // Add foreign key constraints
    echo "\nAdding foreign key constraints...\n";
    
    // Add user_id foreign key
    $conn->exec("
        ALTER TABLE `cart`
        ADD CONSTRAINT `fk_cart_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
    ");
    echo "✓ Added foreign key for user_id\n";
    
    // Add product_id foreign key
    $conn->exec("
        ALTER TABLE `cart`
        ADD CONSTRAINT `fk_cart_product`
        FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
    ");
    echo "✓ Added foreign key for product_id\n";
    
    // Re-enable foreign key checks
    $conn->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    // Verify the constraints
    echo "\n=== Verifying Constraints ===\n";
    
    $stmt = $conn->query("
        SELECT 
            TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, 
            REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM 
            INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
        WHERE 
            TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'cart'
            AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    
    $constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($constraints) === 0) {
        echo "✗ No foreign key constraints found on cart table\n";
    } else {
        echo "Foreign key constraints on cart table:\n";
        foreach ($constraints as $constraint) {
            echo "- {$constraint['COLUMN_NAME']} references ";
            echo "{$constraint['REFERENCED_TABLE_NAME']}.{$constraint['REFERENCED_COLUMN_NAME']} ";
            echo "({$constraint['CONSTRAINT_NAME']})\n";
        }
    }
    
    echo "\n✓ Cart table constraints have been updated successfully\n";
    
} catch (PDOException $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    if (isset($e->errorInfo)) {
        echo "SQL State: " . $e->errorInfo[0] . "\n";
        echo "Driver Error: " . $e->errorInfo[2] . "\n";
    }
    
    // Make sure to re-enable foreign key checks even if there's an error
    if (isset($conn)) {
        $conn->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
}
?>

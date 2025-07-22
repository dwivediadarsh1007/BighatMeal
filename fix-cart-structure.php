<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once 'config.php';

// Function to log messages
function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    echo "[$timestamp] $message<br>\n";
    flush();
    
    // Also log to file
    $logFile = __DIR__ . '/logs/fix-cart.log';
    if (!file_exists(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}

// Start output buffering
ob_start();

try {
    logMessage("Starting cart table fix...");
    
    // Check if cart table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'cart'");
    
    if ($tableCheck->rowCount() > 0) {
        logMessage("Cart table exists. Checking structure...");
        
        // Check if quantity column exists
        $columns = $conn->query("SHOW COLUMNS FROM cart LIKE 'quantity'")->fetchAll();
        
        if (empty($columns)) {
            logMessage("Adding missing 'quantity' column to cart table...");
            $conn->exec("ALTER TABLE cart ADD COLUMN quantity INT NOT NULL DEFAULT 1 AFTER product_id");
            logMessage("✓ Added 'quantity' column to cart table");
        } else {
            logMessage("✓ 'quantity' column already exists");
        }
        
        // Check if unique constraint exists
        $constraints = $conn->query("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE TABLE_NAME = 'cart' 
            AND CONSTRAINT_TYPE = 'UNIQUE'
            AND CONSTRAINT_NAME = 'unique_cart_item'
        ")->fetchAll();
        
        if (empty($constraints)) {
            logMessage("Adding unique constraint on (user_id, product_id)...");
            try {
                $conn->exec("ALTER TABLE cart ADD CONSTRAINT unique_cart_item UNIQUE (user_id, product_id)");
                logMessage("✓ Added unique constraint on (user_id, product_id)");
            } catch (PDOException $e) {
                logMessage("Warning: Could not add unique constraint - " . $e->getMessage());
            }
        } else {
            logMessage("✓ Unique constraint already exists");
        }
        
    } else {
        logMessage("Cart table does not exist. Creating...");
        
        $sql = "CREATE TABLE IF NOT EXISTS `cart` (
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
        logMessage("✓ Created cart table with correct structure");
    }
    
    // Verify the structure
    logMessage("\nVerifying cart table structure...");
    $columns = $conn->query("DESCRIBE cart")->fetchAll(PDO::FETCH_ASSOC);
    
    $expectedColumns = [
        'id' => 'int',
        'user_id' => 'int',
        'product_id' => 'int',
        'quantity' => 'int',
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp'
    ];
    
    $allGood = true;
    foreach ($expectedColumns as $col => $type) {
        $found = false;
        foreach ($columns as $column) {
            if (strtolower($column['Field']) === strtolower($col)) {
                $found = true;
                logMessage(sprintf("✓ Found column: %-12s %s", $column['Field'], $column['Type']));
                break;
            }
        }
        if (!$found) {
            logMessage(sprintf("✗ Missing column: %s", $col));
            $allGood = false;
        }
    }
    
    if ($allGood) {
        logMessage("\n✓ Cart table structure is correct!");
    } else {
        logMessage("\n✗ Cart table structure has issues. Please check the logs.");
    }
    
} catch (PDOException $e) {
    logMessage("\n✗ Database error: " . $e->getMessage());
    logMessage("Error details: " . print_r($e->errorInfo, true));
}

// Output the log
$output = ob_get_clean();
echo "<pre>$output</pre>";

echo "<p>Check the log file for details: " . __DIR__ . '/logs/fix-cart.log' . "</p>";
?>

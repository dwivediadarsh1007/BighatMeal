<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database configuration
require_once 'config.php';

// Function to create the cart table if it doesn't exist
function createCartTable($conn) {
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
    
    try {
        $conn->exec($sql);
        return ['success' => true, 'message' => 'Cart table created successfully'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Error creating cart table: ' . $e->getMessage()];
    }
}

// Check if cart table exists
function checkCartTable($conn) {
    try {
        $tableCheck = $conn->query("SHOW TABLES LIKE 'cart'");
        if ($tableCheck->rowCount() === 0) {
            return ['exists' => false, 'message' => 'Cart table does not exist'];
        }
        
        // Get table structure
        $columns = $conn->query("DESCRIBE cart")->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'exists' => true,
            'message' => 'Cart table exists',
            'structure' => $columns
        ];
        
    } catch (PDOException $e) {
        return ['exists' => false, 'message' => 'Error checking cart table: ' . $e->getMessage()];
    }
}

// Check if products table exists
function checkProductsTable($conn) {
    try {
        $tableCheck = $conn->query("SHOW TABLES LIKE 'products'");
        if ($tableCheck->rowCount() === 0) {
            return ['exists' => false, 'message' => 'Products table does not exist'];
        }
        
        // Get table structure
        $columns = $conn->query("DESCRIBE products")->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'exists' => true,
            'message' => 'Products table exists',
            'structure' => $columns
        ];
        
    } catch (PDOException $e) {
        return ['exists' => false, 'message' => 'Error checking products table: ' . $e->getMessage()];
    }
}

// Run checks
$results = [
    'cart_table' => checkCartTable($conn),
    'products_table' => checkProductsTable($conn)
];

// If cart table doesn't exist, try to create it
if (isset($results['cart_table']['exists']) && !$results['cart_table']['exists']) {
    $results['create_cart_table'] = createCartTable($conn);
    // Re-check cart table after creation attempt
    $results['cart_table'] = checkCartTable($conn);
}

// Output results as JSON
header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT);
?>

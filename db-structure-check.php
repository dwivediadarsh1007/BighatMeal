<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once 'config.php';

// Set content type to text/plain for better readability
header('Content-Type: text/plain');

// Function to check if a table exists
function tableExists($conn, $tableName) {
    $stmt = $conn->query("SHOW TABLES LIKE '$tableName'");
    return $stmt->rowCount() > 0;
}

// Function to get table structure
function getTableStructure($conn, $tableName) {
    $stmt = $conn->query("DESCRIBE `$tableName`");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Required tables and their columns
$requiredTables = [
    'users' => [
        'id', 'username', 'password', 'email', 'created_at'
    ],
    'products' => [
        'id', 'name', 'description', 'price', 'image', 'is_available', 'created_at'
    ],
    'cart' => [
        'id', 'user_id', 'product_id', 'quantity', 'created_at', 'updated_at'
    ]
];

echo "=== Database Structure Check ===\n\n";

// Check each required table
foreach ($requiredTables as $table => $columns) {
    echo "Checking table: $table\n";
    echo str_repeat("-", 50) . "\n";
    
    if (!tableExists($conn, $table)) {
        echo "✗ Table '$table' does not exist!\n\n";
        continue;
    }
    
    echo "✓ Table '$table' exists\n";
    
    // Check each required column
    $allColumnsExist = true;
    $tableStructure = getTableStructure($conn, $table);
    $existingColumns = array_column($tableStructure, 'Field');
    
    foreach ($columns as $column) {
        if (!in_array($column, $existingColumns)) {
            echo "✗ Column '$column' is missing in table '$table'\n";
            $allColumnsExist = false;
        } else {
            echo "✓ Column '$column' exists\n";
        }
    }
    
    if ($allColumnsExist) {
        echo "✓ All required columns exist in table '$table'\n";
    } else {
        echo "✗ Some columns are missing in table '$table'\n";
    }
    
    echo "\n";
}

// Check foreign key constraints
echo "=== Checking Foreign Key Constraints ===\n";

try {
    $stmt = $conn->query("
        SELECT 
            TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, 
            REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM 
            INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
        WHERE 
            TABLE_SCHEMA = DATABASE()
            AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    
    $constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($constraints) === 0) {
        echo "✗ No foreign key constraints found\n";
    } else {
        echo "Found " . count($constraints) . " foreign key constraints:\n";
        foreach ($constraints as $constraint) {
            echo "- {$constraint['TABLE_NAME']}.{$constraint['COLUMN_NAME']} ";
            echo "references {$constraint['REFERENCED_TABLE_NAME']}.{$constraint['REFERENCED_COLUMN_NAME']} ";
            echo "({$constraint['CONSTRAINT_NAME']})\n";
        }
    }
    
} catch (PDOException $e) {
    echo "✗ Error checking foreign keys: " . $e->getMessage() . "\n";
}

// Check sample data
echo "\n=== Sample Data Check ===\n";

try {
    // Check users
    $stmt = $conn->query("SELECT id, username, email FROM users LIMIT 1");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "✓ Found user: {$user['username']} (ID: {$user['id']}, Email: {$user['email']})\n";
    } else {
        echo "✗ No users found in the database\n";
    }
    
    // Check products
    $stmt = $conn->query("SELECT id, name, price, is_available FROM products LIMIT 1");
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        echo "✓ Found product: {$product['name']} (ID: {$product['id']}, Price: {$product['price']}, Available: " . ($product['is_available'] ? 'Yes' : 'No') . ")\n";
    } else {
        echo "✗ No products found in the database\n";
    }
    
    // Check cart
    $stmt = $conn->query("SELECT c.*, p.name as product_name FROM cart c LEFT JOIN products p ON c.product_id = p.id LIMIT 1");
    $cartItem = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($cartItem) {
        echo "✓ Found cart item: {$cartItem['product_name']} (ID: {$cartItem['id']}, Qty: {$cartItem['quantity']})\n";
    } else {
        echo "ℹ No items found in cart\n";
    }
    
} catch (PDOException $e) {
    echo "✗ Error checking sample data: " . $e->getMessage() . "\n";
}

echo "\n=== Check Complete ===\n";
?>

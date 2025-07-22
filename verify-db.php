<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/plain');

echo "=== Database Verification Script ===\n\n";

// Include config
try {
    require_once 'config.php';
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Connected to database successfully\n";
} catch (PDOException $e) {
    die("✗ Database connection failed: " . $e->getMessage() . "\n");
}

// Function to check table structure
function checkTable($conn, $tableName, $expectedColumns) {
    echo "\nChecking table: $tableName\n";
    
    try {
        // Check if table exists
        $stmt = $conn->query("SHOW TABLES LIKE '$tableName'");
        if ($stmt->rowCount() === 0) {
            echo "✗ Table '$tableName' does not exist\n";
            return false;
        }
        
        echo "✓ Table '$tableName' exists\n";
        
        // Check columns
        $stmt = $conn->query("DESCRIBE `$tableName`");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $columnNames = array_column($columns, 'Field');
        
        $allColumnsExist = true;
        foreach ($expectedColumns as $col) {
            if (!in_array($col, $columnNames)) {
                echo "✗ Column '$col' is missing in table '$tableName'\n";
                $allColumnsExist = false;
            } else {
                echo "✓ Column '$col' exists\n";
            }
        }
        
        return $allColumnsExist;
        
    } catch (PDOException $e) {
        echo "✗ Error checking table '$tableName': " . $e->getMessage() . "\n";
        return false;
    }
}

// Expected tables and their columns
$tables = [
    'users' => ['id', 'username', 'password', 'email', 'created_at'],
    'products' => ['id', 'name', 'description', 'price', 'image', 'is_available', 'created_at'],
    'cart' => ['id', 'user_id', 'product_id', 'quantity', 'created_at', 'updated_at']
];

$allTablesExist = true;
foreach ($tables as $table => $columns) {
    if (!checkTable($conn, $table, $columns)) {
        $allTablesExist = false;
    }
}

// Check sample data
echo "\n=== Checking Sample Data ===\n";

try {
    // Check users
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "Users in database: $count\n";
    
    // Check products
    $stmt = $conn->query("SELECT COUNT(*) as count FROM products");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "Products in database: $count\n";
    
    // Show sample products
    $stmt = $conn->query("SELECT id, name, price, is_available FROM products LIMIT 3");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($products) > 0) {
        echo "\nSample products:\n";
        foreach ($products as $product) {
            echo "- ID: {$product['id']}, Name: {$product['name']}, Price: {$product['price']}, Available: " . ($product['is_available'] ? 'Yes' : 'No') . "\n";
        }
    }
    
} catch (PDOException $e) {
    echo "Error checking sample data: " . $e->getMessage() . "\n";
}

// Check foreign keys
echo "\n=== Checking Foreign Keys ===\n";

try {
    $stmt = $conn->query("
        SELECT 
            TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, 
            REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM 
            INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
        WHERE 
            REFERENCED_TABLE_SCHEMA = DATABASE()
            AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    
    $constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($constraints) === 0) {
        echo "No foreign key constraints found\n";
    } else {
        foreach ($constraints as $constraint) {
            echo "- {$constraint['TABLE_NAME']}.{$constraint['COLUMN_NAME']} references ";
            echo "{$constraint['REFERENCED_TABLE_NAME']}.{$constraint['REFERENCED_COLUMN_NAME']} ";
            echo "({$constraint['CONSTRAINT_NAME']})\n";
        }
    }
    
} catch (PDOException $e) {
    echo "Error checking foreign keys: " . $e->getMessage() . "\n";
}

echo "\n=== Verification Complete ===\n";

if ($allTablesExist) {
    echo "✓ All required tables exist\n";
    echo "\nNext steps:\n";
    echo "1. Open http://localhost/Food/test-cart-page.php in your browser\n";
    echo "2. Click the 'Add to Cart' button to test the functionality\n";
    echo "3. Check the debug output for any errors\n";
} else {
    echo "✗ Some tables or columns are missing. Please check the output above.\n";
}
?>

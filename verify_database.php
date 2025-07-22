<?php
require_once 'config.php';

try {
    // Check if addresses table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'addresses'");
    $table_exists = $stmt->rowCount() > 0;
    
    // Check sample address
    $stmt = $conn->query("SELECT COUNT(*) FROM addresses");
    $sample_address_exists = $stmt->fetchColumn() > 0;
    
    // Check orders table structure
    $stmt = $conn->query("DESCRIBE orders");
    $orders_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Database Verification Results:\n";
    echo "-----------------------------\n";
    echo "Addresses Table Exists: " . ($table_exists ? "Yes\n" : "No\n");
    echo "Sample Address Exists: " . ($sample_address_exists ? "Yes\n" : "No\n");
    echo "\nOrders Table Columns:\n";
    foreach ($orders_columns as $column) {
        echo "- $column\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>

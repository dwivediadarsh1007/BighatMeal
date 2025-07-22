<?php
require_once 'config.php';

try {
    // Read the SQL file
    $sql = file_get_contents('data/healthy_salad_bowl.sql');
    
    // Split SQL statements
    $statements = explode(';', $sql);
    
    // Execute each statement
    foreach ($statements as $statement) {
        if (trim($statement)) {
            $conn->exec(trim($statement));
        }
    }
    
    echo "Data imported successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>

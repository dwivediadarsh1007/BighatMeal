<?php
require_once 'config.php';

try {
    // Enable buffered queries
    $conn->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
    
    // Read the SQL file
    $sql = file_get_contents('data/update_database.sql');
    
    // Split SQL statements
    $statements = explode(';', $sql);
    
    // Execute each statement
    foreach ($statements as $statement) {
        if (trim($statement)) {
            // Prepare and execute each statement separately
            $stmt = $conn->prepare(trim($statement));
            $stmt->execute();
            
            // Fetch results if needed
            if ($stmt->rowCount() > 0) {
                $stmt->fetchAll();
            }
        }
    }
    
    echo "Database updated successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>

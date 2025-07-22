<?php
require_once 'config.php';

// Array of localities to add
$localities = [
    'Adhartal',
    'Suhagi',
    'Damohanaka',
    'Vijay Nagar',
    'Shobahapur',
    'VFJ',
    'Maharajpur'
];

try {
    // First, check if the table is empty
    $check = $conn->query("SELECT COUNT(*) as count FROM localities");
    $result = $check->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        echo "Localities already exist in the database.\n";
        exit;
    }
    
    // Prepare the insert statement
    $stmt = $conn->prepare("INSERT INTO localities (name, created_at) VALUES (?, NOW())");
    
    // Insert each locality
    foreach ($localities as $locality) {
        $stmt->execute([$locality]);
        echo "Added locality: " . $locality . "\n";
    }
    
    echo "\nAll localities have been added successfully!\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>

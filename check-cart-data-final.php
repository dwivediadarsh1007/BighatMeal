<?php
require_once 'config.php';

try {
    // Get cart table structure
    $result = $conn->query("DESCRIBE cart");
    echo "Cart table structure:\n";
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "- {$row['Field']} ({$row['Type']})\n";
    }
    
    // Get sample cart data
    echo "\nSample cart record:\n";
    $stmt = $conn->prepare("SELECT * FROM cart WHERE user_id = ? LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $cart_item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($cart_item) {
        echo "\nCart Item Data:\n";
        foreach ($cart_item as $key => $value) {
            echo "- {$key}: " . (is_string($value) ? $value : print_r($value, true)) . "\n";
        }
        
        // Check items JSON structure if it exists
        if (isset($cart_item['items'])) {
            echo "\nItems JSON Structure:\n";
            $items = json_decode($cart_item['items'], true);
            echo print_r($items, true);
        }
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>

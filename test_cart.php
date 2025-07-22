<?php
// Test script to verify cart functionality
session_start();

// Simulate a logged-in user (replace with an actual user ID from your database)
$_SESSION['user_id'] = 1;

// Test data
$testData = [
    'type' => 'custom-meal',
    'items' => [
        [
            'name' => 'Test Meal',
            'quantity' => 1,
            'price' => 10.99,
            'calories' => 500,
            'protein' => 30,
            'carbs' => 50,
            'fat' => 15,
            'fiber' => 5,
            'image_url' => 'images/default-food.jpg'
        ]
    ]
];

// Make the request
$ch = curl_init('http://localhost/Food/api/add-to-cart.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Output results
echo "<h1>Test Cart Functionality</h1>";
echo "<h2>Request:</h2>";
echo "<pre>";
print_r($testData);
echo "</pre>";

echo "<h2>Response (HTTP $httpCode):</h2>";
echo "<pre>";
print_r(json_decode($response, true));
echo "</pre>";

// Show current cart contents
echo "<h2>Current Cart Contents:</h2>";
try {
    require_once 'config.php';
    $stmt = $conn->query("SELECT * FROM cart WHERE user_id = " . $_SESSION['user_id'] . " ORDER BY created_at DESC");
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    foreach ($cartItems as $item) {
        $item['items'] = json_decode($item['items'], true);
        print_r($item);
    }
    echo "</pre>";
} catch (Exception $e) {
    echo "Error fetching cart: " . $e->getMessage();
}
?>

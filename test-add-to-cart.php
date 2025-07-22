<?php
// This is a test script to debug the add to cart functionality
require_once 'config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Simulate a logged-in user for testing (UNCOMMENT FOR TESTING)
// $_SESSION['user_id'] = 1; // Make sure this user exists in your database

// Test product ID (make sure it exists in your products table)
$test_product_id = 1; // Change this to an existing product ID

// Test data
$test_data = [
    'product_id' => $test_product_id,
    'quantity' => 1
];

// Set up the request
$_POST = $test_data;

// Include the add-to-cart script
require 'add-to-cart.php';

// The response will be output as JSON
// Check your browser's developer console (F12) to see the detailed response
?>

<!-- Simple HTML for manual testing -->
<!DOCTYPE html>
<html>
<head>
    <title>Test Add to Cart</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <h1>Test Add to Cart</h1>
    <div id="result">
        <h3>Direct PHP Test Result:</h3>
        <pre><?php 
            $response = json_decode(ob_get_clean(), true);
            echo htmlspecialchars(print_r($response, true));
        ?></pre>
    </div>
    
    <div style="margin-top: 30px;">
        <h3>AJAX Test:</h3>
        <button id="testAjax">Test Add to Cart via AJAX</button>
        <div id="ajaxResult" style="margin-top: 15px;"></div>
    </div>

    <script>
    $(document).ready(function() {
        $('#testAjax').click(function() {
            $.ajax({
                url: 'add-to-cart.php',
                type: 'POST',
                data: {
                    product_id: <?php echo $test_product_id; ?>,
                    quantity: 1
                },
                dataType: 'json',
                success: function(response) {
                    $('#ajaxResult').html('<pre>' + JSON.stringify(response, null, 2) + '</pre>');
                },
                error: function(xhr, status, error) {
                    $('#ajaxResult').html('<strong>Error:</strong><pre>' + xhr.responseText + '</pre>');
                }
            });
        });
    });
    </script>
</body>
</html>

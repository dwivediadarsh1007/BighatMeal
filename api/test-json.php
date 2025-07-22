<?php
// Disable error display to prevent HTML output
ini_set('display_errors', 0);
error_reporting(0);

// Function to send JSON response
function sendJsonResponse($status, $message = '', $data = []) {
    // Clear any previous output
    if (ob_get_level()) {
        ob_clean();
    }
    
    // Set the content type
    header('Content-Type: application/json');
    
    // Set the HTTP status code
    http_response_code($status === 'error' ? 500 : 200);
    
    // Build the response array
    $response = ['status' => $status];
    if ($message !== '') {
        $response['message'] = $message;
    }
    $response = array_merge($response, $data);
    
    // Encode and output the response
    echo json_encode($response);
    exit();
}

// Test the JSON response
sendJsonResponse('success', 'Test successful', [
    'test' => true,
    'time' => date('Y-m-d H:i:s')
]);
?>

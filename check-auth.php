<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set JSON content type header
header('Content-Type: application/json');

// Check if user is logged in
$response = [
    'loggedIn' => isset($_SESSION['user_id']),
    'userId' => $_SESSION['user_id'] ?? null,
    'timestamp' => date('Y-m-d H:i:s')
];

// Log the auth check for debugging
error_log('Auth check - User ID: ' . ($response['userId'] ?? 'not set') . ', Logged in: ' . ($response['loggedIn'] ? 'yes' : 'no'));

// Return the response
echo json_encode($response);
?>

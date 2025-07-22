<?php
// Set error reporting
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/db_errors.log');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuration
$host = 'sql201.infinityfree.com';
$dbname = 'if0_39518502_food';
$username = 'if0_39518502';
$password = 'Adarsh148989';

// Function to send JSON error response
function sendJsonError($message, $statusCode = 500) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => $message
    ]);
    exit();
}

try {
    // Create PDO instance
    $conn = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );
} catch (PDOException $e) {
    // Log the error
    error_log('Database connection failed: ' . $e->getMessage());
    
    // Send JSON error response
    sendJsonError('Database connection failed. Please try again later.');
}
?>

<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to get raw request data
function getRawRequest() {
    return [
        'headers' => getallheaders(),
        'method' => $_SERVER['REQUEST_METHOD'],
        'get' => $_GET,
        'post' => $_POST,
        'input' => file_get_contents('php://input')
    ];
}

// Function to send raw response
function sendRawResponse($data) {
    header('Content-Type: text/plain');
    echo "=== RAW RESPONSE ===\n\n";
    var_export($data);
    exit();
}

// Test the response
$response = [
    'timestamp' => date('Y-m-d H:i:s'),
    'request' => getRawRequest(),
    'session' => isset($_SESSION) ? $_SESSION : 'No session data',
    'server' => [
        'php_version' => phpversion(),
        'sapi' => php_sapi_name(),
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time')
    ]
];

// Check if we should return as JSON or raw
if (isset($_GET['format']) && $_GET['format'] === 'json') {
    header('Content-Type: application/json');
    echo json_encode($response, JSON_PRETTY_PRINT);
} else {
    sendRawResponse($response);
}
?>

<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

try {
    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Unauthorized access');
    }

    // Get order ID from query parameter
    $order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
    
    if ($order_id <= 0) {
        throw new Exception('Invalid order ID');
    }

    // Get the current status of the order
    $stmt = $conn->prepare("SELECT status FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        throw new Exception('Order not found');
    }

    echo json_encode([
        'status' => $result['status']
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
    exit;
}

<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

try {
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (!isset($data['order_id']) || !isset($data['status'])) {
        throw new Exception('Missing required fields');
    }

    // Validate order ID
    $order_id = (int)$data['order_id'];
    if ($order_id <= 0) {
        throw new Exception('Invalid order ID');
    }

    // Update order status
    $stmt = $conn->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([strtolower($data['status']), $order_id]);

    // Get the updated status
    $stmt = $conn->prepare("SELECT status FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $updated_status = $stmt->fetch(PDO::FETCH_ASSOC)['status'];

    echo json_encode([
        'success' => true,
        'message' => 'Status updated successfully',
        'status' => $updated_status
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}

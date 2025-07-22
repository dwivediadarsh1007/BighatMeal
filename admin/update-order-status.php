<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

try {
    // Check authentication
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        throw new Exception('Unauthorized access');
    }

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

    // Validate the new status
    $valid_statuses = ['processing', 'confirmed', 'preparing', 'out_for_delivery', 'delivered', 'cancelled'];
    $new_status = strtolower($data['status']);
    
    if (!in_array($new_status, $valid_statuses)) {
        throw new Exception('Invalid status provided');
    }

    // Get the current status to ensure we're not setting an invalid transition
    $stmt = $conn->prepare("SELECT status FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $current_status = strtolower($stmt->fetch(PDO::FETCH_ASSOC)['status']);

    // Define valid status transitions
    $valid_transitions = [
        'processing' => ['confirmed', 'cancelled'],
        'confirmed' => ['preparing', 'cancelled'],
        'preparing' => ['out_for_delivery', 'cancelled'],
        'out_for_delivery' => ['delivered', 'cancelled'],
        'delivered' => [], // cannot change from delivered
        'cancelled' => [] // cannot change from cancelled
    ];

    // Check if this is a valid transition
    if (!in_array($new_status, $valid_transitions[$current_status])) {
        throw new Exception("Cannot change status from $current_status to $new_status");
    }

    // Update order status
    $stmt = $conn->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
    $affected_rows = $stmt->execute([$new_status, $order_id]);

    // Verify the update was successful
    if ($affected_rows !== 1) {
        throw new Exception('Failed to update status');
    }

    // Get the updated status to confirm
    $stmt = $conn->prepare("SELECT status FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $updated_status = strtolower($stmt->fetch(PDO::FETCH_ASSOC)['status']);

    // Verify the status was actually updated
    if ($updated_status !== $new_status) {
        throw new Exception('Failed to update status');
    }

    // Get status message
    $messages = [
        'processing' => 'Order is now being processed.',
        'confirmed' => 'Order has been confirmed.',
        'preparing' => 'Order is being prepared.',
        'out_for_delivery' => 'Order is out for delivery.',
        'delivered' => 'Order has been delivered.',
        'cancelled' => 'Order has been cancelled.'
    ];

    // Get status color
    $colors = [
        'processing' => 'warning',
        'confirmed' => 'primary',
        'preparing' => 'info',
        'out_for_delivery' => 'warning',
        'delivered' => 'success',
        'cancelled' => 'danger'
    ];

    echo json_encode([
        'success' => true,
        'message' => $messages[$updated_status] ?? 'Status updated successfully',
        'status' => $updated_status,
        'color' => $colors[$updated_status] ?? 'secondary'
    ]);

} catch (Exception $e) {
    error_log('Status update error: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}

// Helper function to get status color
function getStatusColor($status) {
    $colors = [
        'processing' => 'warning',
        'confirmed' => 'primary',
        'preparing' => 'info',
        'out_for_delivery' => 'warning',
        'delivered' => 'success',
        'cancelled' => 'danger'
    ];
    return $colors[strtolower($status)] ?? 'secondary';
}

// Helper function to get status message
function getStatusMessage($status) {
    $messages = [
        'processing' => 'Order is now being processed.',
        'confirmed' => 'Order has been confirmed.',
        'preparing' => 'Order is being prepared.',
        'out_for_delivery' => 'Order is out for delivery.',
        'delivered' => 'Order has been delivered.',
        'cancelled' => 'Order has been cancelled.'
    ];
    return $messages[$status] ?? 'Status updated successfully';
}

<?php
session_start();
require_once '../config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

try {
    // Get all orders
    $stmt = $conn->prepare("SELECT id, status FROM orders");
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Reset orders that are not already processing
    $orders_to_reset = array_filter($orders, function($order) {
        return strtolower($order['status']) !== 'processing';
    });
    
    // Update each order individually with proper validation
    $affected = 0;
    foreach ($orders_to_reset as $order) {
        try {
            $stmt = $conn->prepare("UPDATE orders SET status = 'processing', updated_at = NOW() WHERE id = ? AND status != 'processing'");
            $stmt->execute([$order['id']]);
            $affected += $stmt->rowCount();
        } catch (Exception $e) {
            error_log("Failed to reset order #{$order['id']}: " . $e->getMessage());
        }
    }
    
    // Get the number of affected rows
    $affected = $stmt->rowCount();
    
    // Log the action
    error_log("Admin reset orders: $affected orders reset to processing status");
    
    // Redirect back to orders page with success message
    $_SESSION['success_message'] = "Successfully reset $affected orders to processing status";
    header('Location: orders.php');
    exit();
} catch (Exception $e) {
    // Log the error
    error_log('Order reset error: ' . $e->getMessage());
    
    // Redirect back to orders page with error message
    $_SESSION['error_message'] = 'Failed to reset orders: ' . $e->getMessage();
    header('Location: orders.php');
    exit();
}

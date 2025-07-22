<?php

// Function to format price
function formatPrice($amount) {
    // Convert to float and ensure positive value
    $amount = floatval($amount);
    $amount = $amount >= 0 ? $amount : 0;
    return number_format($amount, 2, '.', '');
}

// Function to get status color (backward compatibility)
function getStatusColor($status) {
    return getStatusBadgeClass($status);
}

// Function to get status badge class
function getStatusBadgeClass($status) {
    $status = strtolower($status);
    $colors = [
        'pending' => 'warning',
        'processing' => 'info',
        'confirmed' => 'primary',
        'preparing' => 'info',
        'out_for_delivery' => 'warning',
        'delivered' => 'success',
        'cancelled' => 'danger'
    ];
    return $colors[$status] ?? 'secondary';
}

// Function to get formatted date
function formatDate($date) {
    return date('F j, Y', strtotime($date));
}

// Function to get formatted datetime
function formatDateTime($datetime) {
    return date('F j, Y g:i A', strtotime($datetime));
}

// Function to get formatted status text
function getFormattedStatus($status) {
    $status = strtolower($status);
    $texts = [
        'processing' => 'Processing',
        'confirmed' => 'Confirmed',
        'preparing' => 'Preparing',
        'out_for_delivery' => 'Out for Delivery',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled'
    ];
    return $texts[$status] ?? ucfirst($status);
}

// Function to sanitize output
function sanitizeOutput($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// Function to get image URL
function getImageUrl($image_path, $default = 'images/default.jpg') {
    return !empty($image_path) ? $image_path : $default;
}

// Function to get user role display text
function getUserRole($role) {
    $roles = [
        'admin' => 'Administrator',
        'user' => 'Customer',
        'delivery' => 'Delivery Person'
    ];
    return $roles[$role] ?? ucfirst($role);
}

// Function to format phone number
function formatPhone($phone) {
    return !empty($phone) ? preg_replace('/\D/', '', $phone) : '';
}

// Function to format address
function formatAddress($address) {
    $parts = [];
    if (!empty($address['address_line1'])) $parts[] = $address['address_line1'];
    if (!empty($address['address_line2'])) $parts[] = $address['address_line2'];
    if (!empty($address['city'])) $parts[] = $address['city'];
    if (!empty($address['state'])) $parts[] = $address['state'];
    if (!empty($address['pincode'])) $parts[] = $address['pincode'];
    return implode(', ', $parts);
}

// Function to get order status options
function getOrderStatusOptions() {
    return [
        'processing' => 'Processing',
        'confirmed' => 'Confirmed',
        'preparing' => 'Preparing',
        'out_for_delivery' => 'Out for Delivery',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled'
    ];
}

// Function to get payment status options
function getPaymentStatusOptions() {
    return [
        'pending' => 'Pending',
        'paid' => 'Paid',
        'failed' => 'Failed',
        'refunded' => 'Refunded'
    ];
}

// Function to get payment method options
function getPaymentMethodOptions() {
    return [
        'cod' => 'Cash on Delivery',
        'online' => 'Online Payment'
    ];
}

// Function to get order items for an order
function getOrderItems($conn, $order_id) {
    $stmt = $conn->prepare("SELECT oi.*, p.name as product_name, p.image_url 
                           FROM order_items oi 
                           LEFT JOIN products p ON oi.product_id = p.id 
                           WHERE oi.order_id = ?");
    $stmt->execute([$order_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get user orders
function getUserOrders($conn, $user_id) {
    $stmt = $conn->prepare("SELECT o.*, a.address_line1, a.address_line2, a.city, a.state, a.pincode, a.phone as delivery_phone 
                           FROM orders o 
                           LEFT JOIN addresses a ON o.address_id = a.id 
                           WHERE o.user_id = ? 
                           ORDER BY o.created_at DESC");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get all orders for admin
function getAllOrders($conn) {
    $stmt = $conn->prepare("SELECT o.*, u.full_name as customer_name, u.phone as customer_phone, u.email as customer_email,
                           a.address_line1, a.address_line2, a.city, a.state, a.pincode, a.phone as delivery_phone
                           FROM orders o 
                           JOIN users u ON o.user_id = u.id 
                           LEFT JOIN addresses a ON o.address_id = a.id 
                           ORDER BY o.created_at DESC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to update order status
function updateOrderStatus($conn, $order_id, $status) {
    $stmt = $conn->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
    return $stmt->execute([$status, $order_id]);
}

// Function to delete order
function deleteOrder($conn, $order_id) {
    try {
        $conn->beginTransaction();
        
        // Delete order items first
        $stmt = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
        $stmt->execute([$order_id]);
        
        // Then delete the order
        $stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);
        
        $conn->commit();
        return true;
    } catch(PDOException $e) {
        $conn->rollBack();
        return false;
    }
}

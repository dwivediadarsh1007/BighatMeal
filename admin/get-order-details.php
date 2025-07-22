<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit();
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    exit();
}

$order_id = $_GET['id'];

try {
    // Get order details
    $stmt = $conn->prepare("
        SELECT 
            o.*, 
            u.full_name as customer_name,
            u.phone as customer_phone,
            u.email as customer_email,
            a.address_line1,
            a.address_line2,
            a.city,
            a.state,
            a.pincode
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        LEFT JOIN addresses a ON o.address_id = a.id
        WHERE o.id = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if (!$order) {
        http_response_code(404);
        exit();
    }

    // Get order items with nutrition details
    $stmt = $conn->prepare("
        SELECT 
            p.name,
            oi.quantity,
            p.calories,
            p.protein,
            p.carbs,
            p.fat,
            p.fiber,
            (p.price * oi.quantity) as price
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll();

    echo json_encode([
        'order' => $order,
        'items' => $items
    ]);

} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?>

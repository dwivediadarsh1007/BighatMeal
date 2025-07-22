<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$order_id = isset($_GET['order_id']) ? $_GET['order_id'] : null;

if (!$order_id) {
    header('Location: cart.php');
    exit();
}

// Get order details
$stmt = $conn->prepare("
    SELECT o.*, p.name as product_name, p.price, p.image_url 
    FROM orders o 
    JOIN order_items oi ON o.id = oi.order_id 
    JOIN products p ON oi.product_id = p.id 
    WHERE o.id = ? AND o.user_id = ?");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order_details = $stmt->fetchAll();

if (empty($order_details)) {
    header('Location: cart.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Success - BighatMeal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                        <h2 class="mt-3">Order Placed Successfully!</h2>
                        <p class="lead">Thank you for your order! Your order has been placed successfully.</p>
                        
                        <div class="mt-4">
                            <h5>Order Details</h5>
                            <p><strong>Order ID:</strong> #<?php echo $order_id; ?></p>
                            <p><strong>Order Date:</strong> <?php echo date('F j, Y', strtotime($order_details[0]['created_at'])); ?></p>
                            <p><strong>Total Amount:</strong> ₹<?php echo number_format($order_details[0]['total_amount'], 2); ?></p>
                            <p><strong>Status:</strong> <span class="badge bg-warning">Processing</span></p>
                        </div>
                        
                        <div class="mt-4">
                            <a href="orders.php" class="btn btn-primary">Track Order</a>
                            <a href="index.php" class="btn btn-secondary">Continue Shopping</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
session_start();
require_once 'config.php';
require_once 'utils.php';

// Handle order cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    $order_id = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
    
    if ($order_id) {
        try {
            // Verify the order belongs to the user and is in a cancellable state
            $checkStmt = $conn->prepare("SELECT id, status FROM orders WHERE id = ? AND user_id = ?");
            $checkStmt->execute([$order_id, $_SESSION['user_id']]);
            $order = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($order) {
                // Check if order can be cancelled (only processing/pending orders can be cancelled)
                if (in_array(strtolower($order['status']), ['processing', 'pending', 'confirmed'])) {
                    $updateStmt = $conn->prepare("UPDATE orders SET status = 'cancelled', updated_at = NOW() WHERE id = ? AND user_id = ?");
                    $updateStmt->execute([$order_id, $_SESSION['user_id']]);
                    
                    if ($updateStmt->rowCount() > 0) {
                        $_SESSION['success'] = "Order #$order_id has been cancelled successfully.";
                    }
                } else {
                    $_SESSION['error'] = "Order #$order_id cannot be cancelled as it's already " . ucfirst($order['status']);
                }
            } else {
                $_SESSION['error'] = "Order not found or you don't have permission to cancel it.";
            }
        } catch (Exception $e) {
            error_log("Error cancelling order: " . $e->getMessage());
            $_SESSION['error'] = "An error occurred while cancelling the order. Please try again.";
        }
    } else {
        $_SESSION['error'] = "Invalid order ID.";
    }
    
    // Redirect back to the orders page
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

try {
    // Get all orders with addresses and items in one query
    $stmt = $conn->prepare("
        SELECT 
            o.*, 
            a.address_line1,
            a.address_line2,
            a.city,
            a.state,
            a.pincode,
            a.phone as delivery_phone,
            oi.product_id,
            oi.quantity,
            p.name as product_name,
            p.price,
            p.image_url
        FROM orders o
        LEFT JOIN addresses a ON o.address_id = a.id
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE o.user_id = ?
        ORDER BY o.created_at DESC");
    
    $stmt->execute([$_SESSION['user_id']]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group orders and their items
    $orders = [];
    foreach ($results as $row) {
        $order_id = $row['id'];
        if (!isset($orders[$order_id])) {
            $orders[$order_id] = [
                'id' => $order_id,
                'user_id' => $row['user_id'],
                'total_amount' => $row['total_amount'],
                'status' => $row['status'] ?? 'processing',
                'delivery_address' => $row['delivery_address'] ?? '',
                'delivery_instructions' => $row['delivery_instructions'] ?? '',
                'created_at' => $row['created_at'],
                'address_id' => $row['address_id'],
                'payment_method' => $row['payment_method'],
                'payment_status' => $row['payment_status'],
                'updated_at' => $row['updated_at'],
                'address_line1' => $row['address_line1'],
                'address_line2' => $row['address_line2'],
                'city' => $row['city'],
                'state' => $row['state'],
                'pincode' => $row['pincode'],
                'delivery_phone' => $row['delivery_phone'],
                'items' => []
            ];
        }
        
        if (isset($row['product_id'])) {
            $orders[$order_id]['items'][] = [
                'product_id' => $row['product_id'],
                'product_name' => $row['product_name'],
                'quantity' => $row['quantity'],
                'price' => $row['price'],
                'image_url' => $row['image_url'] ?: 'images/default-product.png'
            ];
        }
    }

    // Convert to array and sort by ID
    $orders = array_values($orders);
    usort($orders, function($a, $b) {
        return $b['id'] - $a['id'];
    });

} catch (Exception $e) {
    die("Error fetching orders: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - BighatMeal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        .address {
            font-size: 0.9em;
            line-height: 1.4;
            padding: 8px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }

        .order-item-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
            background-color: #f8f9fa;
        }

        .order-card {
            margin-bottom: 1.5rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            transition: transform 0.2s ease-in-out;
        }
        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        }

        .order-status {
            font-weight: 500;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container mt-4">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-3">
                <?php include 'includes/profile-menu.php'; ?>
            </div>
            <div class="col-md-9">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="card-title mb-0">My Orders</h5>
                        </div>
                        
                        <?php if (empty($orders)): ?>
                            <div class="alert alert-info">
                                You haven't placed any orders yet.
                            </div>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <div class="card order-card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div>
                                                <h6 class="mb-0">Order #<?php echo $order['id']; ?></h6>
                                                <small class="text-muted"><?php echo formatDate($order['created_at']); ?></small>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="badge bg-<?php echo getStatusBadgeClass($order['status']); ?> mb-2">
                                                        <?php echo ucfirst($order['status']); ?>
                                                    </span>
                                                    <?php if (in_array(strtolower($order['status']), ['processing', 'pending', 'confirmed'])): ?>
                                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to cancel this order? This action cannot be undone.');">
                                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                            <button type="submit" name="cancel_order" class="btn btn-sm btn-outline-danger">
                                                                <i class="bi bi-x-circle"></i> Cancel Order
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                         
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>Payment Method:</strong> <?php echo ucfirst($order['payment_method']); ?></p>
                                                <p><strong>Payment Status:</strong> <?php echo ucfirst($order['payment_status']); ?></p>
                                            </div>
                                             <div class="col-md-6">
                                                <p><strong>Total Amount:</strong> ₹<?php 
                                                    // Debug: Show raw and formatted amount
                                                    echo formatPrice($order['total_amount']); 
                                                    error_log("Order #{$order['id']} - Total Amount: " . $order['total_amount']);
                                                    ?></p>
                                                <p><strong>Delivery Address:</strong> 
                                                    <?php
                                                    if (isset($order['address_line1'])) {
                                                        echo '<div class="address">';
                                                        echo htmlspecialchars($order['address_line1']) . '<br>';
                                                        if (isset($order['address_line2'])) {
                                                            echo htmlspecialchars($order['address_line2']) . '<br>';
                                                        }
                                                        if (isset($order['city'])) {
                                                            echo htmlspecialchars($order['city']) . ', ';
                                                        }
                                                        if (isset($order['state'])) {
                                                            echo htmlspecialchars($order['state']) . '<br>';
                                                        }
                                                        if (isset($order['pincode'])) {
                                                            echo htmlspecialchars($order['pincode']) . '<br>';
                                                        }
                                                        if (isset($order['delivery_phone'])) {
                                                            echo 'Phone: ' . htmlspecialchars($order['delivery_phone']);
                                                        }
                                                        echo '</div>';
                                                    } else {
                                                        echo '<div class="address">Address not available</div>';
                                                    }
                                                    ?>
                                                </p>
                                            </div>
                                        </div>
                                         
                                        <div class="mt-3">
                                            <h6>Order Items:</h6>
                                            <ul class="list-unstyled">
                                                <?php if (!empty($order['items'])): ?>
                                                    <?php foreach ($order['items'] as $item): ?>
                                                        <li class="d-flex align-items-center mb-2">
                                                            <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                                                 alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                                                 class="order-item-image me-3">
                                                            <div>
                                                                <h6 class="mb-1"><?php echo htmlspecialchars($item['product_name']); ?></h6>
                                                                <p class="mb-0">₹<?php echo formatPrice($item['price']); ?> × <?php echo $item['quantity']; ?></p>
                                                            </div>
                                                        </li>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <li class="text-muted">No items found in this order</li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Function to get status color
        function getStatusColor(status) {
            const colors = {
                'processing': 'warning',
                'confirmed': 'primary',
                'preparing': 'info',
                'out_for_delivery': 'warning',
                'delivered': 'success',
                'cancelled': 'danger'
            };
            return colors[status.toLowerCase()] || 'primary';
        }

        // Function to check for status updates
        function checkStatusUpdates() {
            const orderStatuses = document.querySelectorAll('.order-status');
            orderStatuses.forEach(status => {
                const orderId = status.closest('.order-card').querySelector('h6').textContent.replace('Order #', '').trim();
                
                fetch(`check-order-status.php?order_id=${orderId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.status !== status.textContent.toLowerCase()) {
                            // Update the status badge
                            status.textContent = data.status;
                            status.className = `badge bg-${getStatusColor(data.status)}`;
                            
                            // Show toast notification
                            const toast = document.createElement('div');
                            toast.className = 'toast position-fixed top-0 end-0 m-3 bg-info';
                            toast.innerHTML = `
                                <div class="toast-header">
                                    <strong class="me-auto">Status Update</strong>
                                    <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
                                </div>
                                <div class="toast-body">
                                    Your order status has been updated to ${data.status}
                                </div>
                            `;
                            
                            document.body.appendChild(toast);
                            const bsToast = new bootstrap.Toast(toast, { autohide: true, delay: 5000 });
                            bsToast.show();
                            
                            toast.addEventListener('hidden.bs.toast', () => {
                                toast.remove();
                            });
                        }
                    })
                    .catch(error => console.error('Error checking status:', error));
            });
        }

        // Check for status updates every 30 seconds
        setInterval(checkStatusUpdates, 30000);
        
        // Initial check
        checkStatusUpdates();
    </script>
</body>
</html>

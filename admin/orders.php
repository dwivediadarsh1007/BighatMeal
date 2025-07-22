<?php
require_once '../config.php';
require_once '../utils.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];
    
    try {
        $stmt = $conn->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$new_status, $order_id]);
        $_SESSION['success_message'] = 'Order status updated successfully';
    } catch(PDOException $e) {
        $_SESSION['error_message'] = 'Error updating order status';
    }
    header('Location: orders.php');
    exit();
}

// Handle order deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_order'])) {
    $order_id = $_POST['order_id'];
    
    try {
        // Start transaction
        $conn->beginTransaction();
        
        // Delete order items first
        $stmt = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
        $stmt->execute([$order_id]);
        
        // Then delete the order
        $stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);
        
        $conn->commit();
        $_SESSION['success_message'] = 'Order deleted successfully';
    } catch(PDOException $e) {
        // Rollback on error
        $conn->rollBack();
        $_SESSION['error_message'] = 'Error deleting order: ' . $e->getMessage();
    }
    header('Location: orders.php');
    exit();
}

// Fetch orders with their items
try {
    $stmt = $conn->prepare("
        SELECT 
            o.*, 
            o.address_id,
            u.full_name as customer_name,
            u.phone as customer_phone,
            u.email as customer_email,
            CASE 
                WHEN a.id IS NULL THEN 'No address provided'
                ELSE CONCAT(
                    COALESCE(a.address_line1, ''), 
                    CASE WHEN a.address_line2 IS NOT NULL AND a.address_line2 != '' THEN CONCAT(', ', a.address_line2) ELSE '' END,
                    CASE WHEN a.area IS NOT NULL AND a.area != '' THEN CONCAT(', ', a.area) ELSE '' END,
                    CASE WHEN a.locality_id IS NOT NULL THEN 
                        CONCAT(', ', (SELECT name FROM localities WHERE id = a.locality_id LIMIT 1)) 
                        ELSE '' 
                    END,
                    CASE WHEN a.city IS NOT NULL AND a.city != '' THEN CONCAT(', ', a.city) ELSE '' END,
                    CASE WHEN a.state IS NOT NULL AND a.state != '' THEN CONCAT(', ', a.state) ELSE '' END,
                    CASE WHEN a.pincode IS NOT NULL AND a.pincode != '' THEN CONCAT(' - ', a.pincode) ELSE '' END
                )
            END as delivery_address,
            (
                SELECT COALESCE(
                    (
                        SELECT GROUP_CONCAT(
                            CONCAT(
                                COALESCE(oi.quantity, 1), 'x ',
                                COALESCE(oi.product_name, p.name, 'Custom Meal'),
                                ' - ₹', ROUND(oi.price/100, 2)
                            )
                            ORDER BY COALESCE(oi.product_name, p.name, 'Custom Meal')
                            SEPARATOR '<br>'
                        )
                        FROM order_items oi 
                        LEFT JOIN products p ON oi.product_id = p.id
                        WHERE oi.order_id = o.id
                    ),
                    'No items'
                )
            ) as order_details,
            (SELECT (COALESCE(SUM(oi.price * oi.quantity), 0)/100)  -- Sum (price * quantity) in paise, convert to rupees
            FROM order_items oi 
            WHERE oi.order_id = o.id) as calculated_total
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        LEFT JOIN addresses a ON o.address_id = a.id
        ORDER BY o.created_at DESC
    ");
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['error_message'] = 'Error fetching orders: ' . $e->getMessage();
    $orders = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        .order-details {
            max-height: 200px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Manage Orders</h1>
                </div>

                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['success_message']; 
                        unset($_SESSION['success_message']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['error_message']; 
                        unset($_SESSION['error_message']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Contact</th>
                                <th>Delivery Address</th>
                                <th>Order Details</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Date & Time</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="8" class="text-center">No orders found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>#<?php echo htmlspecialchars($order['id']); ?></td>
                                        <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($order['customer_phone']); ?><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($order['customer_email']); ?></small>
                                        </td>
                                        <td>
                                            <small>
                                                <?php 
                                                if (!empty($order['address_id'])) {
                                                    // Simple display for the table cell
                                                    echo 'View Address';
                                                    
                                                    // Get basic address details for the modal
                                                    $stmt = $conn->prepare("SELECT * FROM addresses WHERE id = ?");
                                                    $stmt->execute([$order['address_id']]);
                                                    $address = $stmt->fetch(PDO::FETCH_ASSOC);
                                                    
                                                    if ($address) {
                                                        // Simple address display in the table
                                                        $simple_address = [];
                                                        if (!empty($address['address_line1'])) $simple_address[] = $address['address_line1'];
                                                        if (!empty($address['area'])) $simple_address[] = $address['area'];
                                                        if (!empty($address['city'])) $simple_address[] = $address['city'];
                                                        
                                                        echo ' <a href="#" data-bs-toggle="modal" data-bs-target="#addressModal' . $order['id'] . '" title="' . htmlspecialchars(implode(', ', $simple_address)) . '">(View Details)</a>';
                                                        
                                                        // Address details modal
                                                        echo '<div class="modal fade" id="addressModal' . $order['id'] . '" tabindex="-1">';
                                                        echo '  <div class="modal-dialog">';
                                                        echo '    <div class="modal-content">';
                                                        echo '      <div class="modal-header">';
                                                        echo '        <h5 class="modal-title">Delivery Address</h5>';
                                                        echo '        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>';
                                                        echo '      </div>';
                                                        echo '      <div class="modal-body">';
                                                        
                                                        // Display address details
                                                        if (!empty($address['address_line1'])) {
                                                            echo '<p>' . htmlspecialchars($address['address_line1']) . '</p>';
                                                        }
                                                        if (!empty($address['address_line2'])) {
                                                            echo '<p>' . htmlspecialchars($address['address_line2']) . '</p>';
                                                        }
                                                        if (!empty($address['area'])) {
                                                            echo '<p>Area: ' . htmlspecialchars($address['area']) . '</p>';
                                                        }
                                                        if (!empty($address['city'])) {
                                                            echo '<p>City: ' . htmlspecialchars($address['city']) . '</p>';
                                                        }
                                                        if (!empty($address['state'])) {
                                                            echo '<p>State: ' . htmlspecialchars($address['state']) . '</p>';
                                                        }
                                                        if (!empty($address['pincode'])) {
                                                            echo '<p>Pincode: ' . htmlspecialchars($address['pincode']) . '</p>';
                                                        }
                                                        if (!empty($address['phone'])) {
                                                            echo '<p>Phone: ' . htmlspecialchars($address['phone']) . '</p>';
                                                        }
                                                        
                                                        echo '      </div>';
                                                        echo '    </div>';
                                                        echo '  </div>';
                                                        echo '</div>';
                                                    } else {
                                                        echo ' (Address not found)';
                                                    }
                                                } else {
                                                    echo 'No address provided';
                                                }
                                                ?>
                                            </small>
                                        </td>
                                        <td class="order-details">
                                            <?php echo $order['order_details'] ?? 'No items'; ?>
                                        </td>
                                        <td>₹<?php echo number_format(($order['calculated_total'] ?? ($order['total_amount'] ? $order['total_amount']/100 : 50)), 2); ?></td>
                                        <td>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <select name="status" onchange="this.form.submit()" class="form-select form-select-sm">
                                                    <option value="processing" <?php echo ($order['status'] === 'processing') ? 'selected' : ''; ?>>Processing</option>
                                                    <option value="confirmed" <?php echo ($order['status'] === 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                                                    <option value="preparing" <?php echo ($order['status'] === 'preparing') ? 'selected' : ''; ?>>Preparing</option>
                                                    <option value="out_for_delivery" <?php echo ($order['status'] === 'out_for_delivery') ? 'selected' : ''; ?>>Out for Delivery</option>
                                                    <option value="delivered" <?php echo ($order['status'] === 'delivered') ? 'selected' : ''; ?>>Delivered</option>
                                                    <option value="cancelled" <?php echo ($order['status'] === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                                </select>
                                                <input type="hidden" name="update_status" value="1">
                                            </form>
                                        </td>
                                        <td><?php echo date('M j, Y H:i', strtotime($order['created_at'])); ?></td>
                                        <td>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this order?');">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <button type="submit" name="delete_order" class="btn btn-danger btn-sm">
                                                    <i class="bi bi-trash"></i> Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Enable Bootstrap tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>
</html>
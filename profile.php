<?php
session_start();
require_once 'config.php';

// Debug: Check if session is working
echo '<!-- Debug: Session ID: ' . session_id() . ' -->';
echo '<!-- Debug: Session data: ' . print_r($_SESSION, true) . ' -->';

if (!isset($_SESSION['user_id'])) {
    echo '<!-- Debug: No user_id in session, redirecting to login -->';
    header('Location: login.php');
    exit();
}

echo '<!-- Debug: User ID in session: ' . $_SESSION['user_id'] . ' -->';

// Initialize user data array with default values
$user = [
    'full_name' => '',
    'email' => '',
    'phone' => '',
    'created_at' => date('Y-m-d H:i:s'),
    'address' => ''
];

// Get user details
try {
    // Check if we have a valid user_id
    if (empty($_SESSION['user_id'])) {
        throw new Exception('No user_id in session');
    }
    
    // Get fresh user data from database
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $dbUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($dbUser) {
        // Update session with fresh data from database
        $_SESSION['full_name'] = $dbUser['full_name'] ?? '';
        $_SESSION['email'] = $dbUser['email'] ?? '';
        $_SESSION['phone'] = $dbUser['phone'] ?? '';
        $_SESSION['address'] = $dbUser['address'] ?? '';
        $_SESSION['role'] = $dbUser['role'] ?? 'user';
        
        // Set user data for the view
        $user = array_merge([
            'full_name' => '',
            'email' => '',
            'phone' => '',
            'address' => '',
            'created_at' => date('Y-m-d H:i:s')
        ], $dbUser);
    } else {
        throw new Exception('No user found with ID: ' . $_SESSION['user_id']);
    }
} catch (Exception $e) {
    error_log("Error fetching user data: " . $e->getMessage());
    $_SESSION['error'] = "An error occurred while loading your profile. Please try again later.";
}

// Get recent orders
try {
    $stmt = $conn->prepare("
        SELECT o.*, oi.product_id, p.name as product_name, p.price, p.image_url 
        FROM orders o 
        JOIN order_items oi ON o.id = oi.order_id 
        JOIN products p ON oi.product_id = p.id 
        WHERE o.user_id = ? 
        ORDER BY o.created_at DESC 
        LIMIT 5");
    $stmt->execute([$_SESSION['user_id']]);
    $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $recent_orders = [];
    error_log("Error fetching recent orders: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - BighatMeal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .icon-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }
        .profile-details {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
        }
        .profile-details h6 {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .profile-details p {
            font-size: 1rem;
            margin-bottom: 0;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <!-- Profile Sidebar -->
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Profile Menu</h5>
                        <ul class="list-unstyled">
                            <li class="mb-3">
                                <a href="profile.php" class="text-decoration-none active">
                                    <i class="bi bi-person"></i> Profile
                                </a>
                            </li>
                            <li class="mb-3">
                                <a href="orders.php" class="text-decoration-none">
                                    <i class="bi bi-receipt"></i> My Orders
                                </a>
                            </li>
                            <li class="mb-3">
                                <a href="address.php" class="text-decoration-none">
                                    <i class="bi bi-geo-alt"></i> Addresses
                                </a>
                            </li>
                            <li class="mb-3">
                                <a href="settings.php" class="text-decoration-none">
                                    <i class="bi bi-gear"></i> Settings
                                </a>
                            </li>
                            <li class="mb-3">
                                <a href="logout.php" class="text-decoration-none">
                                    <i class="bi bi-box-arrow-right"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Profile Content -->
            <div class="col-md-9">
                <div class="card mb-4">
                    <div class="card-body">
                        <!-- Debug information removed -->
                        
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                        <?php endif; ?>
                        
                        <div class="profile-details">
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-center mb-4">
                                                <div class="d-flex align-items-center">
                                                    <div class="icon-circle bg-primary bg-opacity-10 text-primary me-3">
                                                        <i class="bi bi-person"></i>
                                                    </div>
                                                    <h5 class="card-title mb-0">Personal Information</h5>
                                                </div>
                                                <a href="settings.php" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </a>
                                            </div>
                                            <div class="ms-4 ps-3">
                                                <div class="mb-3">
                                                    <p class="text-muted mb-1">Full Name</p>
                                                    <p class="mb-0 fs-5"><?php echo !empty($_SESSION['full_name']) ? htmlspecialchars($_SESSION['full_name']) : '<span class="text-muted">Not provided</span>'; ?></p>
                                                </div>
                                                <div class="mb-3">
                                                    <p class="text-muted mb-1">Email Address</p>
                                                    <p class="mb-0">
                                                        <?php if (!empty($_SESSION['email'])): ?>
                                                            <a href="mailto:<?php echo htmlspecialchars($_SESSION['email']); ?>" class="text-decoration-none">
                                                                <?php echo htmlspecialchars($_SESSION['email']); ?>
                                                            </a>
                                                        <?php else: ?>
                                                            <span class="text-muted">Not provided</span>
                                                        <?php endif; ?>
                                                    </p>
                                                </div>
                                                <div class="mb-3">
                                                    <p class="text-muted mb-1">Phone Number</p>
                                                    <p class="mb-0">
                                                        <?php if (!empty($_SESSION['phone'])): ?>
                                                            <a href="tel:<?php echo htmlspecialchars($_SESSION['phone']); ?>" class="text-decoration-none">
                                                                <?php echo htmlspecialchars($_SESSION['phone']); ?>
                                                            </a>
                                                        <?php else: ?>
                                                            <span class="text-muted">Not provided</span>
                                                        <?php endif; ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-4">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-center mb-4">
                                                <div class="d-flex align-items-center">
                                                    <div class="icon-circle bg-primary bg-opacity-10 text-primary me-3">
                                                        <i class="bi bi-geo-alt"></i>
                                                    </div>
                                                    <h5 class="card-title mb-0">Default Address</h5>
                                                </div>
                                                <a href="address.php" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </a>
                                            </div>
                                            <div class="ms-4 ps-3">
                                                <?php if (!empty($_SESSION['address'])): ?>
                                                    <p class="mb-3"><?php echo nl2br(htmlspecialchars($_SESSION['address'])); ?></p>
                                                <?php else: ?>
                                                    <p class="text-muted mb-3">No address saved yet.</p>
                                                    <a href="address.php" class="btn btn-primary">
                                                        <i class="bi bi-plus-circle"></i> Add Address
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <div class="mt-4 pt-3 border-top">
                                                    <p class="text-muted mb-1">Member Since</p>
                                                    <p class="mb-0">
                                                        <?php 
                                                        $created_at = '2025-06-27 14:50:43'; // Default fallback date
                                                        if (!empty($user['created_at'])) {
                                                            $created_at = $user['created_at'];
                                                        } elseif (!empty($_SESSION['created_at'])) {
                                                            $created_at = $_SESSION['created_at'];
                                                        }
                                                        $date = new DateTime($created_at);
                                                        echo $date->format('F j, Y'); 
                                                        ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="card mt-4">
                    <div class="card-body">
                        <h5 class="card-title">Recent Orders</h5>
                        <?php if (!empty($recent_orders)): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Price</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_orders as $order): ?>
                                            <tr>
                                                <td>
                                                    <img src="<?php echo $order['image_url']; ?>" 
                                                         alt="<?php echo $order['product_name']; ?>" 
                                                         class="img-thumbnail" style="width: 50px; height: 50px;">
                                                    <?php echo $order['product_name']; ?>
                                                </td>
                                                <td>₹<?php echo number_format($order['price'], 2); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo ($order['status'] == 'delivered' ? 'success' : 
                                                              ($order['status'] == 'processing' ? 'warning' : 'primary')); ?>">
                                                        <?php echo ucfirst($order['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">No recent orders found.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

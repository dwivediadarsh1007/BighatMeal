<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Handle page routing
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$action = isset($_GET['action']) ? $_GET['action'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$role = isset($_GET['role']) ? $_GET['role'] : '';

// Include appropriate page content
switch ($page) {
    case 'products':
        require_once 'products.php';
        exit();
        break;
    case 'categories':
        require_once 'categories.php';
        exit();
        break;
    case 'orders':
        require_once 'orders.php';
        exit();
        break;
    case 'users':
        require_once 'users.php';
        exit();
        break;
    default:
        // Get statistics
        $stmt = $conn->query("SELECT COUNT(*) as total_orders FROM orders");
        $total_orders = $stmt->fetch()['total_orders'];

        $stmt = $conn->query("SELECT COUNT(*) as total_products FROM products");
        $total_products = $stmt->fetch()['total_products'];

        $stmt = $conn->query("SELECT COUNT(*) as total_users FROM users WHERE role = 'user'");
        $total_users = $stmt->fetch()['total_users'];

        $stmt = $conn->query("SELECT SUM(total_amount) as total_revenue FROM orders WHERE status = 'delivered'");
        $total_revenue = $stmt->fetch()['total_revenue'];
        break;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - BighatMeal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard.php">
                                <i class="bi bi-speedometer2"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="products.php">
                                <i class="bi bi-box"></i>
                                Products
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="orders.php">
                                <i class="bi bi-cart-check"></i>
                                Orders
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="users.php">
                                <i class="bi bi-people"></i>
                                Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="categories.php">
                                <i class="bi bi-tags"></i>
                                Categories
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../logout.php">
                                <i class="bi bi-box-arrow-right"></i>
                                Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                </div>

                <div class="row">
                    <!-- Total Orders Card -->
                    <div class="col-md-3 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title mb-0">Total Orders</h5>
                                        <p class="card-text display-6 mb-1"><?php echo $total_orders; ?></p>
                                        <p class="card-text small text-muted">Total orders placed</p>
                                    </div>
                                    <div class="icon-container bg-primary rounded-circle p-3">
                                        <i class="bi bi-cart-check fs-1 text-white"></i>
                                    </div>
                                </div>
                                <a href="./orders.php" class="stretched-link"></a>
                            </div>
                            <div class="card-footer bg-light border-top">
                                <small class="text-muted">View all orders</small>
                            </div>
                        </div>
                    </div>

                    <!-- Total Products Card -->
                    <div class="col-md-3 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title mb-0">Total Products</h5>
                                        <p class="card-text display-6 mb-1"><?php echo $total_products; ?></p>
                                        <p class="card-text small text-muted">Active products</p>
                                    </div>
                                    <div class="icon-container bg-success rounded-circle p-3">
                                        <i class="bi bi-box fs-1 text-white"></i>
                                    </div>
                                </div>
                                <a href="./products.php" class="stretched-link"></a>
                            </div>
                            <div class="card-footer bg-light border-top">
                                <small class="text-muted">Manage products</small>
                            </div>
                        </div>
                    </div>

                    <!-- Total Users Card -->
                    <div class="col-md-3 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title mb-0">Total Users</h5>
                                        <p class="card-text display-6 mb-1"><?php echo $total_users; ?></p>
                                        <p class="card-text small text-muted">Registered users</p>
                                    </div>
                                    <div class="icon-container bg-info rounded-circle p-3">
                                        <i class="bi bi-people fs-1 text-white"></i>
                                    </div>
                                </div>
                                <a href="./users.php" class="stretched-link"></a>
                            </div>
                            <div class="card-footer bg-light border-top">
                                <small class="text-muted">View all users</small>
                            </div>
                        </div>
                    </div>

                    <!-- Total Revenue Card -->
                    <div class="col-md-3 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title mb-0">Total Revenue</h5>
                                        <p class="card-text display-6 mb-1"><?php echo number_format($total_revenue, 2); ?></p>
                                        <p class="card-text small text-muted">From delivered orders</p>
                                    </div>
                                    <div class="icon-container bg-warning rounded-circle p-3">
                                        <i class="bi bi-currency-rupee fs-1 text-white"></i>
                                    </div>
                                </div>
                                <a href="./orders.php?status=delivered" class="stretched-link"></a>
                            </div>
                            <div class="card-footer bg-light border-top">
                                <small class="text-muted">View revenue details</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions Section -->
                <div class="row mt-4">
                    <div class="col-12">
                        <h2 class="h4 mb-4">Quick Actions</h2>
                        <div class="d-flex flex-wrap gap-3">
                            <!-- Add Product Button -->
                            <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?page=products&action=add" class="btn btn-primary btn-lg d-flex align-items-center gap-2" 
                               title="Add a new product to the menu">
                                <i class="bi bi-plus-lg"></i>
                                <span>Add New Product</span>
                            </a>

                            <!-- Add Category Button -->
                            <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?page=categories&action=add" class="btn btn-success btn-lg d-flex align-items-center gap-2" 
                               title="Create a new food category">
                                <i class="bi bi-tags"></i>
                                <span>Add New Category</span>
                            </a>

                            <!-- View Pending Orders Button -->
                            <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?page=orders&status=pending" class="btn btn-warning btn-lg d-flex align-items-center gap-2" 
                               title="View orders that need attention">
                                <i class="bi bi-hourglass"></i>
                                <span>View Pending Orders</span>
                            </a>

                            <!-- Manage Admins Button -->
                            <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?page=users&role=admin" class="btn btn-info btn-lg d-flex align-items-center gap-2" 
                               title="Manage administrator accounts">
                                <i class="bi bi-shield"></i>
                                <span>Manage Admins</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Add styles for quick action buttons -->
                <style>
                    .quick-action-btn {
                        transition: all 0.3s ease;
                        position: relative;
                        overflow: hidden;
                    }
                    
                    .quick-action-btn:hover {
                        transform: translateY(-2px);
                        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
                    }
                    
                    .quick-action-btn i {
                        font-size: 1.5rem;
                        opacity: 0.8;
                    }
                    
                    .quick-action-btn span {
                        font-weight: 500;
                    }
                    
                    @media (max-width: 768px) {
                        .quick-action-btn {
                            width: 100%;
                            justify-content: center;
                        }
                        
                        .quick-action-btn i {
                            font-size: 1.2rem;
                        }
                    }
                </style>

                <!-- Add JavaScript for quick action buttons -->
                <script>
                    // Add click handlers for quick action buttons
                    document.addEventListener('DOMContentLoaded', function() {
                        const quickActionButtons = document.querySelectorAll('.btn-lg');
                        
                        quickActionButtons.forEach(button => {
                            button.addEventListener('click', function(e) {
                                // Prevent default link behavior
                                e.preventDefault();
                                
                                // Get the href attribute
                                const url = this.getAttribute('href');
                                
                                // Add loading state
                                this.classList.add('disabled');
                                this.querySelector('i').classList.add('bi-spin');
                                
                                // Navigate after a short delay
                                setTimeout(() => {
                                    window.location.href = url;
                                }, 500);
                            });
                        });
                    });
                </script>

                <!-- Recent Orders -->
                <h2>Recent Orders</h2>
                <div class="table-responsive">
                    <table class="table table-striped table-sm">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $conn->query("SELECT o.*, u.username FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 10");
                            while ($order = $stmt->fetch()):
                            ?>
                            <tr>
                                <td><?php echo $order['id']; ?></td>
                                <td><?php echo $order['username']; ?></td>
                                <td><?php echo '$' . number_format($order['total_amount'], 2); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $order['status'] === 'delivered' ? 'success' : ($order['status'] === 'cancelled' ? 'danger' : 'warning'); ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="view-order.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

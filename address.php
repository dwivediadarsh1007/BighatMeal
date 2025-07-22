<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle address form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if this is a delete request
    if (isset($_POST['delete_address'])) {
        try {
            // First check if there are any orders using this address
            $checkOrders = $conn->prepare("SELECT COUNT(*) as order_count FROM orders WHERE address_id = ? AND user_id = ?");
            $checkOrders->execute([$_POST['address_id'], $_SESSION['user_id']]);
            $result = $checkOrders->fetch(PDO::FETCH_ASSOC);
            
            if ($result['order_count'] > 0) {
                // If there are orders, don't delete but show a message
                $_SESSION['error'] = "Cannot delete this address because it's associated with existing orders. You can mark it as inactive instead.";
            } else {
                // No orders, safe to delete
                $stmt = $conn->prepare("DELETE FROM addresses WHERE id = ? AND user_id = ?");
                $stmt->execute([$_POST['address_id'], $_SESSION['user_id']]);
                
                if ($stmt->rowCount() > 0) {
                    $_SESSION['success'] = "Address deleted successfully!";
                } else {
                    $_SESSION['error'] = "Address not found or you don't have permission to delete it.";
                }
            }
            
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
            
        } catch (Exception $e) {
            // If we get a foreign key constraint error, show a user-friendly message
            if (strpos($e->getMessage(), 'foreign key constraint') !== false) {
                $error = "Cannot delete this address because it's being used in existing orders. You can mark it as inactive instead.";
            } else {
                $error = "Error processing your request: " . $e->getMessage();
            }
        }
    } else {
        // Handle add new address
        try {
            $conn->beginTransaction();
            
            // Prepare the SQL query
            $sql = "
                INSERT INTO addresses 
                (user_id, address_line1, address_line2, city, state, pincode, phone, 
                locality_id, area, landmark, is_default, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())
            ";
            
            $stmt = $conn->prepare($sql);
            
            $stmt->execute([
                $user_id,
                $_POST['address_line1'],
                $_POST['address_line2'] ?? '',
                $_POST['city'],
                $_POST['state'],
                $_POST['pincode'],
                $_POST['phone'],
                (int)$_POST['locality_id'],
                $_POST['area'],
                $_POST['landmark'] ?? ''
            ]);
            
            // If this is the first address, set it as default
            $count = $conn->query("SELECT COUNT(*) FROM addresses WHERE user_id = " . $user_id)->fetchColumn();
            if ($count == 1) {
                $conn->exec("UPDATE addresses SET is_default = 1 WHERE id = " . $conn->lastInsertId());
            }
            
            $conn->commit();
            $_SESSION['success'] = "Address added successfully!";
            
        } catch (Exception $e) {
            $conn->rollBack();
            $error = "Error adding address: " . $e->getMessage();
        }
    }
}

// Check for success/error messages in session
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

// Get user's addresses
$addresses = [];
try {
    $stmt = $conn->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
    $stmt->execute([$user_id]);
    $addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Log error but don't show to user
    error_log('Error fetching addresses: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Addresses - BighatMeal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-3">
                <?php include 'includes/profile-menu.php'; ?>
            </div>
            
            <div class="col-md-9">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Delivery Addresses</h5>
                        
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <!-- Add New Address Form -->
                        <div class="mb-4">
                            <h6>Add New Address</h6>
                            <form method="POST" action="address.php">
                                <div class="mb-3">
                                    <label class="form-label">Address Line 1</label>
                                    <input type="text" name="address_line1" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Address Line 2 (Optional)</label>
                                    <input type="text" name="address_line2" class="form-control">
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">City</label>
                                        <input type="text" name="city" id="city" class="form-control" value="Jabalpur" readonly>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">State</label>
                                        <input type="text" name="state" class="form-control" value="Madhya Pradesh" readonly>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Pincode</label>
                                        <input type="text" name="pincode" class="form-control" required>
                                    </div>
                                </div>
                                <?php
                                // Fetch all active localities
                                $localities = $conn->query("SELECT * FROM localities ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
                                ?>
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Area</label>
                                        <select name="area" id="area" class="form-select" required>
                                            <option value="">Select Area</option>
                                            <option value="Adhartal">Adhartal</option>
                                            <option value="Suhagi">Suhagi</option>
                                            <option value="Damohanaka">Damohanaka</option>
                                            <option value="Vijay Nagar">Vijay Nagar</option>
                                            <option value="Shobahapur">Shobahapur</option>
                                            <option value="VFJ">VFJ</option>
                                            <option value="Maharajpur">Maharajpur</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Landmark (Optional)</label>
                                    <input type="text" name="landmark" class="form-control" placeholder="e.g., Near Central Park, Opposite Mall">
                                </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Phone Number</label>
                                    <input type="tel" name="phone" class="form-control" required>
                                </div>
                                <button type="submit" class="btn btn-primary">Add Address</button>
                            </form>
                        </div>
                        
                        <!-- Existing Addresses -->
                        <?php if (!empty($addresses)): ?>
                            <h6>My Addresses</h6>
                            <div class="list-group">
                                <?php foreach ($addresses as $address): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6><?php echo htmlspecialchars($address['address_line1']); ?></h6>
                                                <p class="mb-1 small">
                                                    <?php 
                                                    echo htmlspecialchars($address['address_line1']);
                                                    if (!empty($address['address_line2'])) {
                                                        echo ', ' . htmlspecialchars($address['address_line2']);
                                                    }
                                                    if (!empty($address['landmark'])) {
                                                        echo '<br>Landmark: ' . htmlspecialchars($address['landmark']);
                                                    }
                                                    ?>
                                                    <br>
                                                    <?php 
                                                    echo htmlspecialchars(
                                                        $address['area_name'] . ', ' . 
                                                        $address['locality_name'] . ', ' .
                                                        $address['city'] . ' - ' . 
                                                        $address['pincode']
                                                    );
                                                    ?>
                                                </p>
                                                <p class="mb-0 small text-muted">
                                                    Phone: <?php echo htmlspecialchars($address['phone']); ?>
                                                    <?php if (!empty($address['delivery_charge'])): ?>
                                                        <br>Delivery: ₹<?php echo number_format($address['delivery_charge'], 2); ?>
                                                        <?php if ($address['min_order_amount'] > 0): ?>
                                                            (Min. order: ₹<?php echo number_format($address['min_order_amount'], 2); ?>)
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </p>
                                            </div>
                                            <div>
                                                <button class="btn btn-sm btn-primary me-2">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this address? This action cannot be undone.');">
                                                    <input type="hidden" name="address_id" value="<?php echo $address['id']; ?>">
                                                    <input type="hidden" name="delete_address" value="1">
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="bi bi-trash"></i> Remove
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">No addresses added yet. Please add your delivery address.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simple form validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('addAddressForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const requiredFields = form.querySelectorAll('[required]');
                    let isValid = true;
                    
                    requiredFields.forEach(field => {
                        if (!field.value.trim()) {
                            alert(`Please fill in the ${field.labels[0].textContent} field`);
                            field.focus();
                            isValid = false;
                            return false;
                        }
                    });
                    
                    if (!isValid) {
                        e.preventDefault();
                    }
                });
            }
    // Auto-hide alerts after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            }, 5000);
        });
    });
    </script>
</body>
</html>

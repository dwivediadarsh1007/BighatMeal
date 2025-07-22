<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'config.php';

// Debug: Check database connection
try {
    $tables = $conn->query("SHOW TABLES LIKE 'users'")->fetchAll();
    if (empty($tables)) {
        die("Error: 'users' table does not exist in the database.");
    }
    
    // Check users table structure
    $columns = $conn->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
    $required_columns = ['id', 'username', 'full_name', 'email', 'phone', 'address'];
    $missing_columns = array_diff($required_columns, $columns);
    
    if (!empty($missing_columns)) {
        die("Error: Missing required columns in users table: " . implode(', ', $missing_columns));
    }
    
} catch (PDOException $e) {
    die("Database connection error: " . $e->getMessage());
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Clear any old form data from session
if (isset($_SESSION['form_data']) && !isset($_POST['full_name'])) {
    unset($_SESSION['form_data']);
}

// Debug: Show session data
echo '<!-- Debug: Session Data -->';
echo '<!-- ' . htmlspecialchars(print_r($_SESSION, true)) . ' -->';

// Fetch user data from database with error handling
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception("User not found in database");
    }
    
    // Ensure all required fields exist
    $user = array_merge([
        'id' => $_SESSION['user_id'],
        'username' => '',
        'full_name' => '',
        'email' => '',
        'phone' => '',
        'address' => '',
        'role' => 'user'
    ], $user);
    
    // Update session data
    $_SESSION['username'] = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['phone'] = $user['phone'];
    $_SESSION['address'] = $user['address'];
    $_SESSION['role'] = $user['role'];
    
} catch (Exception $e) {
    error_log("Error in settings.php - " . $e->getMessage());
    $_SESSION['error_message'] = "Error loading profile: " . $e->getMessage();
    
    // Initialize with session data as fallback
    $user = [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? '',
        'full_name' => $_SESSION['full_name'] ?? '',
        'email' => $_SESSION['email'] ?? '',
        'phone' => $_SESSION['phone'] ?? '',
        'address' => $_SESSION['address'] ?? '',
        'role' => $_SESSION['role'] ?? 'user'
    ];
}

// Debug: Log POST data
error_log('POST data: ' . print_r($_POST, true));

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Start transaction
        $conn->beginTransaction();
        
        // Debug: Check if POST data is being received
        error_log('Form submitted. Checking POST data...');
        
        // Get form data with null coalescing and trim
        $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
        $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $address = isset($_POST['address']) ? trim($_POST['address']) : '';
        
        // Debug: Log the received values
        error_log("Full Name: " . ($full_name ?: 'empty'));
        error_log("Phone: " . ($phone ?: 'empty'));
        error_log("Email: " . ($email ?: 'empty'));
        error_log("Address: " . ($address ?: 'empty'));
        
        // Basic validation
        $errors = [];
        
        if (empty($full_name)) {
            $errors[] = "Full name is required";
        }
        
        if (empty($phone)) {
            $errors[] = "Phone number is required";
        } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
            $errors[] = "Please enter a valid 10-digit phone number";
        }
        
        if (empty($email)) {
            $errors[] = "Email is required";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Please enter a valid email address";
        } else {
            // Check if email already exists (except for current user)
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $_SESSION['user_id']]);
            if ($stmt->fetch()) {
                $errors[] = "This email is already registered with another account";
            }
        }
        
        // If there are validation errors, stop here
        if (!empty($errors)) {
            throw new Exception(implode("\n", $errors));
        }
        
        // Debug: Log the data being saved
        error_log("Updating user profile for user ID: " . $_SESSION['user_id']);
        error_log("Full Name: " . $full_name);
        error_log("Email: " . $email);
        error_log("Phone: " . $phone);
        error_log("Address: " . $address);
        
        // Update user data in database
        $sql = "
            UPDATE users 
            SET 
                full_name = ?,
                email = ?,
                phone = ?,
                address = ?
            WHERE id = ?";
            
        $stmt = $conn->prepare($sql);
        
        $result = $stmt->execute([
            $full_name,
            $email,
            $phone,
            $address,
            $_SESSION['user_id']
        ]);
        
        if (!$result) {
            throw new Exception("Failed to execute update query");
        }
        
        // Update session data
        $_SESSION['full_name'] = $full_name;
        $_SESSION['email'] = $email;
        $_SESSION['phone'] = $phone;
        $_SESSION['address'] = $address;
        
        // Commit the transaction
        $conn->commit();
        
        // Set success message
        $_SESSION['success_message'] = "Profile updated successfully!";
        
        // Refresh the page to show updated data
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
        
    } catch (Exception $e) {
        // Rollback the transaction on error
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        
        error_log("Error updating profile: " . $e->getMessage());
        
        // Set error message with more details in development
        $errorMsg = "An error occurred while updating your profile.";
        if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
            $errorMsg .= "<br><small>" . $e->getMessage() . "</small>";
        }
        
        $_SESSION['error_message'] = $errorMsg;
        
        // Store form data in session to refill the form
        $_SESSION['form_data'] = [
            'full_name' => $full_name ?? '',
            'email' => $email ?? '',
            'phone' => $phone ?? '',
            'address' => $address ?? ''
        ];
        
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - BighatMeal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        .settings-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .settings-card .card-body {
            padding: 2rem;
        }
        .form-control, .form-select {
            padding: 0.75rem 1rem;
            border: 1px solid #e1e5ee;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .form-control:focus, .form-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.1);
        }
        .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 0.5rem;
        }
        .settings-section {
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #e9ecef;
        }
        .settings-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .settings-section h5 {
            color: #212529;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #f8f9fa;
            font-weight: 600;
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .avatar-upload {
            position: relative;
            display: inline-block;
            cursor: pointer;
        }
        .avatar-edit {
            position: absolute;
            right: 5px;
            bottom: 5px;
            background: #0d6efd;
            color: white;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .avatar-edit input {
            display: none;
        }
    </style>
</head>
<body>
    <?php 
    // Debug: Show database info
    $dbInfo = [];
    try {
        // Get database name
        $dbName = $conn->query('SELECT DATABASE()')->fetchColumn();
        $dbInfo['Database'] = $dbName;
        
        // Get table info
        $tables = $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $dbInfo['Tables'] = $tables;
        
        // Get users table structure
        $usersTable = $conn->query("DESCRIBE users")->fetchAll(PDO::FETCH_ASSOC);
        $dbInfo['Users Table Structure'] = $usersTable;
        
        // Get current user data
        if (isset($_SESSION['user_id'])) {
            $userData = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $userData->execute([$_SESSION['user_id']]);
            $dbInfo['Current User Data'] = $userData->fetch(PDO::FETCH_ASSOC);
        }
        
    } catch (Exception $e) {
        $dbInfo['Error'] = $e->getMessage();
    }
    
    // Output debug info as HTML comment
    echo "\n<!-- DEBUG INFORMATION:\n" . print_r($dbInfo, true) . "\n-->\n";
    ?>
    
    <?php include 'includes/header.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-3">
                <?php include 'includes/profile-menu.php'; ?>
            </div>
            <div class="col-md-9">
                <div class="card settings-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="mb-0">Account Settings</h4>
                        </div>
                        
                        <?php if (isset($_SESSION['success_message'])): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                <?php 
                                    echo $_SESSION['success_message']; 
                                    unset($_SESSION['success_message']); 
                                ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['error_message'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <?php 
                                    echo $_SESSION['error_message']; 
                                    unset($_SESSION['error_message']); 
                                ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php 
                        // Debug: Show user data being used in the form
                        /*
                        echo '<div class="alert alert-info">';
                        echo '<h5>Debug Info:</h5>';
                        echo '<pre>User Data: ' . print_r($user, true) . '</pre>';
                        echo '</div>';
                        */
                        ?>
                        
                        <form id="profileForm" method="POST" class="needs-validation" novalidate action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                            <div class="settings-section">
                                <h5>Profile Information</h5>
                                <div class="row align-items-center mb-4">
                                    <div class="col-auto">
                                        <div class="avatar-upload">
                                            <img src="<?php echo !empty($user['avatar']) ? htmlspecialchars($user['avatar']) : 'images/default-avatar.png'; ?>" 
                                                 alt="Profile" class="profile-avatar" id="avatarPreview">
                                            <label class="avatar-edit" for="avatar">
                                                <i class="bi bi-camera"></i>
                                                <input type="file" id="avatar" name="avatar" accept="image/*" style="display: none;">
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <p class="mb-1">Profile Photo</p>
                                        <p class="text-muted small mb-0">JPG, GIF or PNG. Max size 2MB</p>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                                value="<?php 
                                                    echo isset($_SESSION['form_data']) ? 
                                                        htmlspecialchars($_SESSION['form_data']['full_name']) : 
                                                        (isset($user['full_name']) ? htmlspecialchars($user['full_name']) : ''); 
                                                ?>" required autocomplete="name">
                                        </div>
                                        <div class="invalid-feedback">
                                            Please enter your full name.
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                value="<?php 
                                                    echo isset($_SESSION['form_data']) ? 
                                                        htmlspecialchars($_SESSION['form_data']['email']) : 
                                                        (isset($user['email']) ? htmlspecialchars($user['email']) : ''); 
                                                ?>" required autocomplete="email">
                                        </div>
                                        <div class="invalid-feedback">
                                            Please enter a valid email address.
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                            <input type="tel" class="form-control" id="phone" name="phone" 
                                            value="<?php 
                                                echo isset($_SESSION['form_data']) ? 
                                                    htmlspecialchars($_SESSION['form_data']['phone']) : 
                                                    (isset($user['phone']) ? htmlspecialchars($user['phone']) : ''); 
                                            ?>" 
                                            pattern="[0-9]{10}" 
                                            title="Please enter a valid 10-digit phone number"
                                            required autocomplete="tel">
                                        </div>
                                        <div class="invalid-feedback">
                                            Please enter your phone number.
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="address" class="form-label">Address</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                                            <textarea class="form-control" id="address" name="address" rows="3" 
                                                placeholder="Enter your full address" autocomplete="street-address"><?php 
                                                    echo isset($_SESSION['form_data']) ? 
                                                        htmlspecialchars($_SESSION['form_data']['address']) : 
                                                        (isset($user['address']) ? htmlspecialchars($user['address']) : ''); 
                                                ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="settings-section">
                                <h5>Security</h5>
                                <div class="alert alert-info bg-light border-0">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-shield-lock fs-4 me-3 text-primary"></i>
                                        <div>
                                            <h6 class="mb-1">Password</h6>
                                            <p class="mb-0 small text-muted">Last changed: <?php echo !empty($user['password_updated_at']) ? date('F j, Y', strtotime($user['password_updated_at'])) : 'Never'; ?></p>
                                        </div>
                                        <a href="change-password.php" class="btn btn-outline-primary ms-auto">
                                            Change Password
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Save Button -->
                            <div class="d-flex justify-content-end mt-4">
                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <i class="bi bi-save me-2"></i>SAVE CHANGES
                                </button>
                            </div>
                            
                            <!-- Debug Info -->
                            <div class="mt-4 small text-muted">
                                <p>Debug Info:</p>
                                <ul>
                                    <li>User ID: <?php echo isset($user['id']) ? $user['id'] : 'Not set'; ?></li>
                                    <li>Form Submitted: <?php echo $_SERVER['REQUEST_METHOD'] === 'POST' ? 'Yes' : 'No'; ?></li>
                                    <li>Form Data: <?php echo !empty($_POST) ? 'Received' : 'Not received'; ?></li>
                                    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                                    <li>POST Data: <?php echo htmlspecialchars(print_r($_POST, true)); ?></li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        (function () {
            'use strict'
            
            var forms = document.querySelectorAll('.needs-validation')
            
            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        
                        form.classList.add('was-validated')
                    }, false)
                })
        })()
    </script>
</body>
</html>

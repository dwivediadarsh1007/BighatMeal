<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    
    // Basic validation
    $errors = [];
    
    if (strlen($username) < 4) {
        $errors[] = "Username must be at least 4 characters long.";
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }
    
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }
    
    if (empty($full_name)) {
        $errors[] = "Full name is required.";
    }
    
    if (empty($errors)) {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, email, full_name, phone, address, role) VALUES (?, ?, ?, ?, ?, ?, 'user')");
            $stmt->execute([$username, $hashed_password, $email, $full_name, $phone, $address]);
            
            // Set success message
            $_SESSION['success'] = "Registration successful! Please login to continue.";
            
            // Redirect to login page
            header('Location: login.php');
            exit();
        } catch(PDOException $e) {
            if ($e->errorInfo[1] == 1062) { // Duplicate entry error
                if (strpos($e->getMessage(), 'username') !== false) {
                    $error = "Username already exists. Please choose a different one.";
                } elseif (strpos($e->getMessage(), 'email') !== false) {
                    $error = "Email already registered. Please use a different email or login.";
                } else {
                    $error = "Registration failed. Please try again.";
                }
            } else {
                $error = "Registration failed. Please try again later.";
                error_log('Registration error: ' . $e->getMessage());
            }
        }
    } else {
        $error = implode("<br>", $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - BighatMeal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #2e7d32;
            --primary-light: #60ad5e;
            --secondary: #f5f5f5;
        }
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4efe9 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .register-container {
            max-width: 500px;
            width: 100%;
            margin: 0 auto;
            animation: fadeIn 0.8s ease-in-out;
        }
        .register-card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        .register-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }
        .register-header {
            background: linear-gradient(45deg, var(--primary), var(--primary-light));
            color: white;
            padding: 2rem 1rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .register-header h3 {
            margin: 0;
            font-weight: 700;
            position: relative;
            z-index: 1;
        }
        .register-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%);
            transform: rotate(30deg);
            animation: shine 6s infinite;
        }
        .register-body {
            padding: 2rem;
            background: white;
        }
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            transition: all 0.3s;
        }
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(46, 125, 50, 0.25);
        }
        .btn-register {
            background: var(--primary);
            border: none;
            padding: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            border-radius: 8px;
            transition: all 0.3s;
            text-transform: uppercase;
            width: 100%;
        }
        .btn-register:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
        }
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 1.5rem 0;
            color: #6c757d;
        }
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #dee2e6;
        }
        .divider::before {
            margin-right: 1rem;
        }
        .divider::after {
            margin-left: 1rem;
        }
        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            color: #6c757d;
        }
        .login-link a {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
        }
        .input-group-text {
            background-color: #f8f9fa;
            border: 2px solid #e9ecef;
            border-right: none;
            border-radius: 8px 0 0 8px !important;
        }
        .password-toggle {
            border-left: none;
            border-radius: 0 8px 8px 0 !important;
            background-color: #f8f9fa;
            border: 2px solid #e9ecef;
            border-left: none;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes shine {
            0% { transform: rotate(30deg) translate(-25%, -25%); }
            100% { transform: rotate(30deg) translate(25%, 25%); }
        }
        .form-floating>label {
            padding: 0.75rem 1rem;
        }
        .form-floating>.form-control:focus~label,
        .form-floating>.form-control:not(:placeholder-shown)~label {
            transform: scale(0.85) translateY(-0.5rem) translateX(0.15rem);
            color: var(--primary);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="register-container">
            <div class="register-card">
                <div class="register-header">
                    <h3>Create Account</h3>
                </div>
                <div class="register-body">
                    <?php 
                    // Display success message if set
                    if (isset($_SESSION['success'])) {
                        echo '<div class="alert alert-success animate__animated animate__fadeIn">
                            <i class="fas fa-check-circle me-2"></i> ' . htmlspecialchars($_SESSION['success']) . '
                        </div>';
                        unset($_SESSION['success']);
                    }
                    
                    // Display error message if set
                    if (isset($error)): ?>
                        <div class="alert alert-danger animate__animated animate__shakeX">
                            <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" class="animate__animated animate__fadeIn">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="full_name" name="full_name" placeholder="Full Name" required>
                                    <label for="full_name">Full Name</label>
                                    <div class="form-text">Your full name</div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
                                    <label for="username">Username</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-floating">
                                <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" required>
                                <label for="email">Email address</label>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-group mb-3">
                                    <label for="password" class="form-label">Password <small class="text-muted">(min 8 characters)</small></label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="password" name="password" required minlength="8">
                                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">Use a strong password with letters, numbers, and symbols.</div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-floating">
                                    <input type="tel" class="form-control" id="phone" name="phone" placeholder="Phone" required>
                                    <label for="phone">Phone Number</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="form-floating">
                                <textarea class="form-control" id="address" name="address" placeholder="Address" style="height: 100px" required></textarea>
                                <label for="address">Delivery Address</label>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-3">
                        <button type="submit" class="btn btn-primary btn-lg">Create Account</button>
                        <div class="divider">or</div>
                        <a href="google_auth.php" class="btn btn-outline-danger btn-lg d-flex align-items-center justify-content-center">
                            <i class="fab fa-google me-2"></i> Sign up with Google
                        </a>
                    </div>
                    <div class="text-center mt-4">
                        Already have an account? <a href="login.php">Login here</a>
                    </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });

        // Add animation to form elements
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('.form-control');
            inputs.forEach((input, index) => {
                input.style.animationDelay = `${index * 0.1}s`;
            });
        });

        // Phone number formatting
        document.getElementById('phone').addEventListener('input', function(e) {
            let x = e.target.value.replace(/\D/g, '').match(/(\d{0,3})(\d{0,3})(\d{0,4})/);
            e.target.value = !x[2] ? x[1] : '(' + x[1] + ') ' + x[2] + (x[3] ? '-' + x[3] : '');
        });
    </script>
</body>
</html>

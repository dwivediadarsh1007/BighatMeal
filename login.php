<?php
// Start session at the very beginning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

// Debug: Log session start
error_log('=== New Login Request ===');
error_log('Session ID: ' . session_id());
error_log('Session data: ' . print_r($_SESSION, true));

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get error message from session if it exists
$error = '';
if (isset($_SESSION['login_error'])) {
    $error = $_SESSION['login_error'];
    unset($_SESSION['login_error']); // Clear the error after retrieving it
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['form_submitted'])) {
    error_log('Login form submitted');
    
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Debug: Log the received credentials (don't log actual password in production)
    error_log('Login attempt - Username: ' . $username);
    
    // Basic validation
    error_log('Login attempt - Username: ' . $username);
    error_log('Password received: ' . (!empty($password) ? '[not empty]' : 'empty'));
    
    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password";
        error_log('Validation failed: ' . $error);
    } else {
        // Sanitize input
        $username = filter_var($username, FILTER_SANITIZE_STRING);
        $password = filter_var($password, FILTER_SANITIZE_STRING);
            // Check if login is via email or username
            $isEmail = filter_var($username, FILTER_VALIDATE_EMAIL);
            $field = $isEmail ? 'email' : 'username';
            
            // Debug
            error_log("Checking login for $field: $username");
        
        try {
            error_log("Preparing query for field: $field with value: $username");
            try {
                $query = "SELECT * FROM users WHERE $field = ?";
                error_log("Executing query: $query");
                $stmt = $conn->prepare($query);
                if (!$stmt) {
                    error_log("Prepare failed: " . print_r($conn->errorInfo(), true));
                    throw new PDOException("Prepare failed");
                }
                
                $result = $stmt->execute([$username]);
                if ($result === false) {
                    error_log("Execute failed: " . print_r($stmt->errorInfo(), true));
                    throw new PDOException("Execute failed");
                }
                
                $user = $stmt->fetch();
                error_log("Query executed. Found user: " . ($user ? 'Yes' : 'No'));
            } catch (PDOException $e) {
                error_log("Database error: " . $e->getMessage());
                error_log("Error details: " . print_r($conn->errorInfo(), true));
                throw $e; // Re-throw to be caught by outer try-catch
            }
            
            // Debug: Log user lookup result
            if ($user) {
                error_log("User found in database. ID: " . $user['id']);
                error_log("User has password hash: " . (!empty($user['password']) ? 'Yes' : 'No'));
                error_log("User has google_id: " . (!empty($user['google_id']) ? 'Yes' : 'No'));
            } else {
                error_log("No user found with $field: $username");
                $_SESSION['login_error'] = "Invalid username/email or password";
                header('Location: login.php');
                exit();
            }          
            // Verify password if user exists and either:
            // 1. Password is hashed and matches, or
            // 2. User is logging in with Google (no password check needed)
            if ($user) {
                $passwordMatch = false;
                $isGoogleUser = !empty($user['google_id']);
                
                if ($isGoogleUser) {
                    error_log("User is a Google user, redirecting to Google login");
                    header('Location: google_auth.php');
                    exit();
                } else {
                    error_log("Verifying password...");
                    error_log("Stored password hash: " . $user['password']);
                    error_log("Input password: [REDACTED]");
                    
                    if (empty($user['password'])) {
                        error_log("No password set for this user. Please use Google login or reset password.");
                        $error = "No password set for this account. Please use Google login or reset your password.";
                    } else {
                        $passwordMatch = password_verify($password, $user['password']);
                        error_log("Password verification result: " . ($passwordMatch ? 'Match' : 'No match'));
                        
                        if (!$passwordMatch) {
                            // Check if password needs rehashing
                            if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                                error_log("Password needs rehashing");
                                $newHash = password_hash($password, PASSWORD_DEFAULT);
                                // Update the password in the database
                                $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                                $updateStmt->execute([$newHash, $user['id']]);
                                error_log("Password rehashed and updated in database");
                                
                                // Verify the password again after rehashing
                                $passwordMatch = password_verify($password, $newHash);
                                error_log("Password verification after rehashing: " . ($passwordMatch ? 'Match' : 'No match'));
                            }
                            
                            if (!$passwordMatch) {
                                error_log("Incorrect password for user: " . $user['username']);
                                $_SESSION['login_error'] = "Incorrect password. Please try again.";
                                header('Location: login.php');
                                exit();
                            }
                        }
                    }
                }
                
                if (isset($passwordMatch) && $passwordMatch) {
                    // Regenerate session ID to prevent session fixation
                    session_regenerate_id(true);
                    
                    // Update session with user data
                    $_SESSION = []; // Clear existing session data
                    $_SESSION['user_id'] = (int)$user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'] ?? '';
                    $_SESSION['email'] = $user['email'] ?? '';
                    $_SESSION['phone'] = $user['phone'] ?? '';
                    $_SESSION['address'] = $user['address'] ?? '';
                    $_SESSION['role'] = $user['role'] ?? 'user';
                    $_SESSION['last_activity'] = time();
                    
                    // Set secure session cookie parameters
                    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
                    $httponly = true;
                    $samesite = 'lax';
                    
                    if (PHP_VERSION_ID < 70300) {
                        session_set_cookie_params(
                            0, // Lifetime
                            '/; samesite=' . $samesite,
                            '', // Domain
                            $secure,
                            $httponly
                        );
                    } else {
                        $params = [
                            'lifetime' => 0,
                            'path' => '/',
                            'domain' => '',
                            'secure' => $secure,
                            'httponly' => $httponly,
                            'samesite' => $samesite
                        ];
                        session_set_cookie_params($params);
                    }
                    
                    error_log("Session data set for user ID: " . $user['id']);
                    error_log("Session data: " . print_r($_SESSION, true));
                
                    // Update last login time
                    try {
                        $updateStmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                        $updateStmt->execute([$user['id']]);
                    } catch (PDOException $e) {
                        error_log("Failed to update last login time: " . $e->getMessage());
                        // Continue even if this fails
                    }
                    
                    // Check for redirect URL in session or use default
                    $redirectUrl = 'index.php'; // Default redirect
                    
                    // Check if there's a redirect URL in the session
                    if (isset($_SESSION['redirect_after_login']) && !empty($_SESSION['redirect_after_login'])) {
                        $redirectUrl = $_SESSION['redirect_after_login'];
                        unset($_SESSION['redirect_after_login']); // Clear it after use
                    }
                }
                // If not in session, check for redirect URL in the request
                else if (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
                    $redirectUrl = $_GET['redirect'];
                }
                
                // For admin, always go to dashboard regardless of redirect
                if ($user['role'] === 'admin') {
                    $redirectUrl = 'admin/dashboard.php';
                }
                
                // Ensure the redirect URL is safe (prevent open redirects)
                $redirectUrl = filter_var($redirectUrl, FILTER_SANITIZE_URL);
                
                // Set success message
                $_SESSION['success'] = "Welcome back, " . ($user['full_name'] ?? $user['username']) . "!";
                
                // Perform the redirect
                error_log('Login successful. Redirecting to: ' . $redirectUrl);
                error_log('New session data: ' . print_r($_SESSION, true));
            
                // Debug: Verify session is actually saved
                session_write_close();
            
                // Add debug cookie
                setcookie('debug_login', 'success', time() + 3600, '/');
            
                // Redirect with debug parameter
                if (strpos($redirectUrl, '?') === false) {
                    $redirectUrl .= '?debug=1';
                } else {
                    $redirectUrl .= '&debug=1';
                }
            
                header('Location: ' . $redirectUrl);
                exit();
            } else {
                // Set error message in session
                $_SESSION['login_error'] = "Invalid username/email or password";
                
                // Log failed login attempt (without logging the actual password)
                error_log("Login failed for $field: $username");
                
                // Preserve any redirect URL
                $redirectUrl = 'login.php';
                if (!empty($_SESSION['redirect_after_login'])) {
                    $redirectUrl .= '?redirect=' . urlencode($_SESSION['redirect_after_login']);
                }
                
                // Redirect back to login page with error
                header('Location: ' . $redirectUrl);
                exit();
            }
        } catch (PDOException $e) {
            // Set error message in session
            $_SESSION['login_error'] = "Login failed. Please try again later.";
            
            // Log the error
            error_log('Login error: ' . $e->getMessage());
            error_log('Error details: ' . print_r([
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], true));
            
            // Preserve any redirect URL
            $redirectUrl = 'login.php';
            if (!empty($_SESSION['redirect_after_login'])) {
                $redirectUrl .= '?redirect=' . urlencode($_SESSION['redirect_after_login']);
            }
            
            // Redirect back to login page with error
            header('Location: ' . $redirectUrl);
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BighatMeal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://accounts.google.com/gsi/client" async defer></script>
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
        .login-container {
            max-width: 400px;
            width: 100%;
            margin: 0 auto;
            animation: fadeIn 0.8s ease-in-out;
        }
        .login-card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }
        .login-header {
            background: linear-gradient(45deg, var(--primary), var(--primary-light));
            color: white;
            padding: 2rem 1rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .login-header h3 {
            margin: 0;
            font-weight: 700;
            position: relative;
            z-index: 1;
        }
        .login-header::before {
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
        .login-body {
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
        .form-floating>label {
            padding: 0.75rem 1rem;
        }
        .form-floating>.form-control:focus~label,
        .form-floating>.form-control:not(:placeholder-shown)~label {
            transform: scale(0.85) translateY(-0.5rem) translateX(0.15rem);
            color: var(--primary);
        }
        .btn-login {
            background: var(--primary);
            border: none;
            padding: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            border-radius: 8px;
            transition: all 0.3s;
            text-transform: uppercase;
        }
        .btn-login:hover {
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
        .social-login {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .social-btn {
            flex: 1;
            padding: 0.5rem;
            border-radius: 8px;
            border: 1px solid #dee2e6;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            transition: all 0.3s;
            cursor: pointer;
            text-align: center;
        }
        .social-btn:hover {
            background: #f8f9fa;
            transform: translateY(-2px);
        }
        .social-btn i {
            margin-right: 0.5rem;
        }
        .forgot-password {
            text-align: center;
            margin-top: 1rem;
        }
        .forgot-password a {
            color: var(--primary);
            text-decoration: none;
            font-size: 0.9rem;
        }
        .register-link {
            text-align: center;
            margin-top: 1.5rem;
            color: #6c757d;
        }
        .register-link a {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes shine {
            0% { transform: rotate(30deg) translate(-25%, -25%); }
            100% { transform: rotate(30deg) translate(25%, 25%); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="login-card">
                <div class="login-header">
                    <h3>Welcome Back!</h3>
                </div>
                <div class="login-body">
                    <?php 
                    // Display error message if it exists
                    if (!empty($error)): 
                    ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert" id="errorAlert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php 
                        echo htmlspecialchars($error);
                        // Clear the error after displaying it
                        $error = '';
                        unset($_SESSION['login_error']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" class="animate__animated animate__fadeIn" id="loginForm" novalidate>
                        <input type="hidden" name="form_submitted" value="1">
                        <div class="mb-3">
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-user text-muted"></i></span>
                                <input type="text" class="form-control" id="username" name="username" 
                                       placeholder="Username or Email" required autofocus
                                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                            </div>
                        </div>
                        <div class="mb-4">
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-lock text-muted"></i></span>
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="Password" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="form-text text-end">
                                <!-- Removed duplicate Forgot Password link -->
                            </div>
                        </div>
                        <div class="d-grid mb-3">
                            <button type="submit" name="login" class="btn btn-login btn-primary text-white w-100" id="loginButton">
                                <i class="fas fa-sign-in-alt me-2"></i>Login
                                <span id="loginSpinner" class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
                            </button>
                        </div>
                    </form>
                    
                    <div class="forgot-password d-flex justify-content-between">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="rememberMe" name="remember_me">
                            <label class="form-check-label" for="rememberMe">
                                Remember me
                            </label>
                        </div>
                        <a href="forgot-password.php" class="text-decoration-none">
                            Forgot Password?
                        </a>
                    </div>
                    
                    <div class="divider">or</div>
                    
                    <div class="social-login">
                        <a href="google_auth.php" class="social-btn text-decoration-none">
                            <i class="fab fa-google text-danger"></i>
                            <span>Continue with Google</span>
                        </a>
                        <button type="button" class="social-btn" disabled>
                            <i class="fab fa-facebook text-primary"></i>
                            <span>Facebook (Coming Soon)</span>
                        </button>
                    </div>
                    
                    <div class="register-link">
                        Don't have an account? <a href="register.php" class="animate__animated animate__fadeIn">Create Account</a>
                    </div>
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
        
        // Add animation class to form on load
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-hide error alert after 5 seconds
            const errorAlert = document.getElementById('errorAlert');
            if (errorAlert) {
                setTimeout(() => {
                    errorAlert.classList.remove('show');
                    errorAlert.classList.add('fade');
                    // Remove from DOM after animation
                    setTimeout(() => {
                        errorAlert.remove();
                    }, 150);
                }, 5000);
            }

            const form = document.querySelector('form');
            if (form) {
                form.classList.add('animate__animated', 'animate__fadeIn');
                
                // Add form validation
                form.addEventListener('submit', function(e) {
                    const username = document.getElementById('username');
                    const password = document.getElementById('password');
                    const loginButton = document.getElementById('loginButton');
                    const spinner = document.getElementById('loginSpinner');
                    
                    if (loginButton && spinner) {
                        loginButton.disabled = true;
                        spinner.classList.remove('d-none');
                        console.log('Login button disabled, spinner shown');
                    }
                });
            }
            
            // Debug: Check for URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('error')) {
                console.log('Error in URL:', urlParams.get('error'));
            }
            
            // Debug: Log session status
            console.log('Session status:', '<?php echo session_status() === PHP_SESSION_ACTIVE ? "Active" : "Not active"; ?>');
            
            // Debug: Log cookies
            console.log('Cookies:', document.cookie);
        });
        
        // Debug: Log Google sign-in button load
        function onGoogleSignInLoad() {
            console.log('Google Sign-In button loaded');
        }
        
        // Debug: Log Google sign-in errors
        function onGoogleSignInError(error) {
            console.error('Google Sign-In error:', error);
        }
        
        window.onload = function() {
            google.accounts.id.initialize({
                client_id: '161919459956-ru03vdhbru0365ct1cjcqo4n1hr53du0.apps.googleusercontent.com',
                callback: function(response) {
                    console.log('Google Sign-In response:', response);
                    // Handle the response
                }
            });
            
            // Debug: Log when Google button is rendered
            google.accounts.id.renderButton(
                document.getElementById('g_id_onload'),
                { 
                    type: 'standard',
                    theme: 'outline',
                    size: 'large',
                    width: 300,
                    text: 'signin_with',
                    shape: 'rectangular',
                    logo_alignment: 'left'
                }
            );
            
            console.log('Google Sign-In button rendered');
        };
    </script>
</body>
</html>

<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log all errors to a file
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/google_auth_errors.log');

// Start session with secure settings
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => true,
    'cookie_samesite' => 'Lax',
    'use_strict_mode' => true
]);

// Log session start
error_log('=== Google Auth Script Started ===');
error_log('Session ID: ' . session_id());

// Database configuration
$host = 'sql201.infinityfree.com';
$dbname = 'if0_39518502_food';
$username = 'if0_39518502';
$password = 'Adarsh148989';

try {
    // Create database connection
    $conn = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    die('Database connection failed. Please try again later.');
}

// Google OAuth 2.0 configuration
$clientId = '720604078545-6lljb5g5robfe0c5rms4cro3dguip7c7.apps.googleusercontent.com';
$clientSecret = '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';
$redirectUri = 'https://adarshdwivedi.free.nf/google_auth.php';

// Verify the redirect URI is properly set
if (strpos($redirectUri, 'adarshdwivedi.free.nf') === false) {
    die('Invalid redirect URI configuration');
}

// Google OAuth endpoints
$authorizationEndpoint = 'https://accounts.google.com/o/oauth2/v2/auth';
$tokenEndpoint = 'https://oauth2.googleapis.com/token';
$userInfoEndpoint = 'https://www.googleapis.com/oauth2/v3/userinfo';

// Debug: Log the configuration
error_log('Google OAuth Configuration:');
error_log('- Client ID: ' . substr($clientId, 0, 10) . '...');
error_log('- Redirect URI: ' . $redirectUri);
error_log('- Request URI: ' . ($_SERVER['REQUEST_URI'] ?? 'N/A'));

// Generate a random state parameter for CSRF protection
if (!isset($_SESSION['oauth2state'])) {
    $_SESSION['oauth2state'] = bin2hex(random_bytes(32));
}

// Handle the OAuth callback
if (isset($_GET['code'])) {
    // Verify the state matches what we sent
    if (!isset($_GET['state']) || $_GET['state'] !== $_SESSION['oauth2state']) {
        unset($_SESSION['oauth2state']);
        die('Invalid state parameter');
    }

    try {
        // Exchange the authorization code for an access token
        $tokenResponse = httpPost($tokenEndpoint, [
            'code' => $_GET['code'],
            'client_id' => '720604078545-6lljb5g5robfe0c5rms4cro3dguip7c7.apps.googleusercontent.com',
            'client_secret' => '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',
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code'
        ]);

        $tokenData = json_decode($tokenResponse, true);
        
        if (isset($tokenData['access_token'])) {
            // Get user info using the access token
            $userInfo = httpGet($userInfoEndpoint . '?access_token=' . urlencode($tokenData['access_token']));
            $userData = json_decode($userInfo, true);
            
            if (isset($userData['email'])) {
                // Check if google_id column exists, if not add it
                try {
                    $conn->exec("ALTER TABLE users ADD COLUMN google_id VARCHAR(255) DEFAULT NULL, ADD UNIQUE (google_id)");
                } catch (PDOException $e) {
                    // Column might already exist, which is fine
                    if ($e->getCode() != '42S21') { // Error code for duplicate column
                        error_log('Error adding google_id column: ' . $e->getMessage());
                    }
                }

                // Check if user exists in database by email
                $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->execute([$userData['email']]);
                $user = $stmt->fetch();
                
                if ($user) {
                    // Update existing user with google_id if not set
                    if (empty($user['google_id'])) {
                        $updateStmt = $conn->prepare("UPDATE users SET google_id = ? WHERE id = ?");
                        $updateStmt->execute([$userData['sub'], $user['id']]);
                    }
                    
                    // Log the user in
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'] ?? $userData['name'] ?? '';
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'] ?? 'user';
                    
                    // Debug: Log session data after setting
                    error_log('User logged in. User ID: ' . $user['id']);
                    error_log('Session data after login: ' . print_r($_SESSION, true));
                } else {
                    // Create new user with Google account
                    $username = generateUsername($userData['name'] ?? $userData['email']);
                    
                    try {
                        // First, try to insert with google_id
                        $stmt = $conn->prepare("INSERT INTO users (username, email, full_name, google_id, role) VALUES (?, ?, ?, ?, 'user')");
                        $stmt->execute([
                            $username,
                            $userData['email'],
                            $userData['name'] ?? '',
                            $userData['sub']
                        ]);
                    } catch (PDOException $e) {
                        // If insert fails (maybe google_id column doesn't exist yet), try without it
                        if ($e->getCode() == '42S22') { // Column not found
                            $stmt = $conn->prepare("INSERT INTO users (username, email, full_name, role) VALUES (?, ?, ?, 'user')");
                            $stmt->execute([
                                $username,
                                $userData['email'],
                                $userData['name'] ?? ''
                            ]);
                        } else {
                            throw $e; // Re-throw other errors
                        }
                    }
                    
                    $userId = $conn->lastInsertId();
                    
                    // Set session variables
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['username'] = $username;
                    $_SESSION['full_name'] = $userData['name'] ?? '';
                    $_SESSION['email'] = $userData['email'];
                    $_SESSION['role'] = 'user';
                }
                
                // Redirect to home page or previous page
                $redirectUrl = isset($_SESSION['redirect_after_login']) ? 
                    $_SESSION['redirect_after_login'] : 'index.php';
                unset($_SESSION['redirect_after_login']);
                
                header('Location: ' . $redirectUrl);
                exit();
            } else {
                die('Failed to get user info from Google');
            }
        } else {
            die('Failed to get access token from Google');
        }
    } catch (Exception $e) {
        die('Error: ' . $e->getMessage());
    }
} else {
    // Redirect to Google's OAuth 2.0 server
    $params = [
        'response_type' => 'code',
        'client_id' => $clientId,
        'redirect_uri' => $redirectUri,
        'scope' => 'openid email profile',
        'state' => $_SESSION['oauth2state'],
        'access_type' => 'online',
        'prompt' => 'select_account',
    ];
    
    $authUrl = $authorizationEndpoint . '?' . http_build_query($params);
    header('Location: ' . $authUrl);
    exit();
}

/**
 * Helper function to make HTTP POST requests
 */
function httpPost($url, $data) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        throw new Exception('cURL error: ' . curl_error($ch));
    }
    
    curl_close($ch);
    return $response;
}

/**
 * Helper function to make HTTP GET requests
 */
function httpGet($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        throw new Exception('cURL error: ' . curl_error($ch));
    }
    
    curl_close($ch);
    return $response;
}

/**
 * Generate a username from a full name
 */
function generateUsername($name) {
    $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name));
    if (empty($username)) {
        $username = 'user';
    }
    $username = substr($username, 0, 15) . rand(100, 999);
    return $username;
}
?>

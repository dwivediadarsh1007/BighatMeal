<?php
session_start();
require_once 'config.php';

echo "<pre style='background: #f8f9fa; padding: 20px; border: 1px solid #ddd; border-radius: 5px;'>";

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        die("Error: Not logged in");
    }
    
    $user_id = $_SESSION['user_id'];
    echo "Current User ID: " . htmlspecialchars($user_id) . "\n\n";
    
    // Check if users table exists
    $tables = $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables in database: " . implode(", ", $tables) . "\n\n";
    
    if (!in_array('users', $tables)) {
        die("Error: 'users' table does not exist");
    }
    
    // Show users table structure
    echo "Users table structure:\n";
    $columns = $conn->query("DESCRIBE users")->fetchAll(PDO::FETCH_ASSOC);
    print_r($columns);
    
    // Try to get user data
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "\nUser data from database:\n";
    if ($user) {
        print_r($user);
    } else {
        echo "No user found with ID: " . $user_id . "\n";
        
        // Check if any users exist
        $allUsers = $conn->query("SELECT id, username, email FROM users LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        echo "\nFirst few users in database:\n";
        print_r($allUsers);
    }
    
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage();
}

echo "</pre>";

// Show session data
echo "<h3>Session Data:</h3>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";

// Show POST data if any
if (!empty($_POST)) {
    echo "<h3>POST Data:</h3>";
    echo "<pre>" . print_r($_POST, true) . "</pre>";
}

// Add a simple form to test user lookup
echo '
<h3>Test User Lookup</h3>
<form method="post">
    <div class="mb-3">
        <label for="user_id" class="form-label">Enter User ID:</label>
        <input type="number" class="form-control" id="user_id" name="user_id" value="' . htmlspecialchars($_SESSION['user_id'] ?? '') . '">
    </div>
    <button type="submit" class="btn btn-primary">Lookup User</button>
</form>';
?>

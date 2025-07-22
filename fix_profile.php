<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'config.php';

echo "<pre>";

try {
    // Check if users table exists
    $tableExists = $conn->query("SHOW TABLES LIKE 'users'")->rowCount() > 0;
    
    if (!$tableExists) {
        die("Error: 'users' table does not exist.");
    }
    
    // Check table structure
    echo "Checking users table structure...\n";
    $columns = $conn->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
    $requiredColumns = ['id', 'username', 'password', 'full_name', 'email', 'phone', 'address'];
    
    // Add missing columns
    foreach ($requiredColumns as $column) {
        if (!in_array($column, $columns)) {
            $type = 'VARCHAR(255)';
            if ($column === 'id') $type = 'INT AUTO_INCREMENT PRIMARY KEY';
            if ($column === 'password') $type = 'VARCHAR(255)';
            
            $sql = "ALTER TABLE users ADD COLUMN $column $type";
            $conn->exec($sql);
            echo "Added column: $column\n";
        }
    }
    
    // Check if current user exists
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "\nCurrent User Data:\n";
            print_r($user);
            
            // Test update
            $testUpdate = $conn->prepare("
                UPDATE users 
                SET full_name = ?, email = ?, phone = ?, address = ?
                WHERE id = ?
            ");
            
            $testData = [
                'Test User ' . time(),
                'test' . time() . '@example.com',
                '1234567890',
                'Test Address',
                $userId
            ];
            
            $result = $testUpdate->execute($testData);
            
            echo "\nTest Update Result: " . ($result ? 'Success' : 'Failed') . "\n";
            echo "Rows affected: " . $testUpdate->rowCount() . "\n";
            
            // Verify update
            $stmt->execute([$userId]);
            $updatedUser = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "\nUpdated User Data:\n";
            print_r($updatedUser);
            
        } else {
            echo "\nNo user found with ID: $userId\n";
        }
    } else {
        echo "\nNo user is currently logged in.\n";
    }
    
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

echo "</pre>";
?>

<h2>Fix Profile Issues</h2>
<p>This tool will check and fix common profile issues.</p>

<?php if (isset($_SESSION['user_id'])): ?>
    <h3>Current User Information</h3>
    <p>User ID: <?php echo $_SESSION['user_id']; ?></p>
    <p>Username: <?php echo $_SESSION['username'] ?? 'Not set'; ?></p>
    <p>Email: <?php echo $_SESSION['email'] ?? 'Not set'; ?></p>
    
    <h3>Actions</h3>
    <a href="settings.php" class="btn btn-primary">Go to Settings</a>
    <a href="logout.php" class="btn btn-secondary">Logout</a>
<?php else: ?>
    <p>Please <a href="login.php">login</a> to continue.</p>
<?php endif; ?>

<style>
body { padding: 20px; font-family: Arial, sans-serif; }
pre { background: #f5f5f5; padding: 15px; border-radius: 5px; }
</style>

<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Include config
require_once 'config.php';

// Function to print array in a readable format
function print_array($array, $title = '') {
    echo "<h4>$title</h4>";
    echo "<pre>";
    print_r($array);
    echo "</pre>";
}

// Function to run and display SQL query
function run_query($conn, $sql, $params = []) {
    echo "<div class='query-result'>";
    echo "<h5>Query: <code>" . htmlspecialchars($sql) . "</code></h5>";
    
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($results)) {
            echo "<p>No results found.</p>";
        } else {
            echo "<div class='table-responsive'><table class='table table-bordered table-striped'>";
            
            // Table header
            echo "<tr>";
            foreach (array_keys($results[0]) as $column) {
                echo "<th>" . htmlspecialchars($column) . "</th>";
            }
            echo "</tr>";
            
            // Table rows
            foreach ($results as $row) {
                echo "<tr>";
                foreach ($row as $value) {
                    echo "<td>" . htmlspecialchars($value) . "</td>";
                }
                echo "</tr>";
            }
            
            echo "</table></div>";
            echo "<p>Found " . count($results) . " rows.</p>";
        }
        
        return $results;
        
    } catch (PDOException $e) {
        echo "<div class='alert alert-danger'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
        return false;
    }
    
    echo "</div>";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart Diagnostic</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; }
        .section { margin-bottom: 30px; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 5px; }
        .query-result { margin-bottom: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Cart Diagnostic Tool</h1>
        
        <div class="section">
            <h2>1. Session Information</h2>
            <?php 
            if (empty($_SESSION)) {
                echo "<div class='alert alert-warning'>No session data found. Are you logged in?</div>";
            } else {
                print_array($_SESSION, 'Session Data');
            }
            ?>
        </div>
        
        <?php if (isset($_SESSION['user_id'])): ?>
            <?php $user_id = $_SESSION['user_id']; ?>
            
            <div class="section">
                <h2>2. Cart Table Structure</h2>
                <?php 
                $tables = $conn->query("SHOW TABLES LIKE 'cart'")->fetchAll(PDO::FETCH_COLUMN);
                if (empty($tables)) {
                    echo "<div class='alert alert-danger'>The 'cart' table does not exist in the database.</div>";
                } else {
                    echo "<p>Cart table exists.</p>";
                    $structure = $conn->query("DESCRIBE cart")->fetchAll(PDO::FETCH_ASSOC);
                    print_array($structure, 'Cart Table Structure');
                }
                ?>
            </div>
            
            <div class="section">
                <h2>3. Cart Items for User ID: <?php echo htmlspecialchars($user_id); ?></h2>
                <?php
                // Check with user_id as integer
                $sql = "SELECT * FROM cart WHERE user_id = ?";
                $results = run_query($conn, $sql, [$user_id]);
                
                // If no results, try with string user_id
                if (empty($results)) {
                    echo "<div class='alert alert-warning'>No cart items found with user_id as integer. Trying with string...</div>";
                    $sql = "SELECT * FROM cart WHERE user_id = ?";
                    $results = run_query($conn, $sql, [(string)$user_id]);
                }
                ?>
            </div>
            
            <div class="section">
                <h2>4. Sample Cart Items (All Users)</h2>
                <?php
                $sql = "SELECT * FROM cart ORDER BY created_at DESC LIMIT 5";
                run_query($conn, $sql);
                ?>
            </div>
            
            <div class="section">
                <h2>5. Products in Cart</h2>
                <?php
                $sql = "
                    SELECT c.*, p.name, p.price, p.image 
                    FROM cart c
                    LEFT JOIN products p ON c.product_id = p.id
                    WHERE c.user_id = ?
                    ORDER BY c.created_at DESC
                ";
                run_query($conn, $sql, [$user_id]);
                ?>
            </div>
            
            <div class="section">
                <h2>6. Database Connection Test</h2>
                <?php
                try {
                    $conn->query("SELECT 1");
                    echo "<div class='alert alert-success'>✓ Database connection is working</div>";
                } catch (PDOException $e) {
                    echo "<div class='alert alert-danger'>✗ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</div>";
                }
                ?>
            </div>
            
        <?php else: ?>
            <div class="alert alert-warning">
                You are not logged in. Please <a href="login.php">log in</a> to view your cart.
            </div>
        <?php endif; ?>
        
        <div class="section">
            <h2>Debug Information</h2>
            <h4>Server Info:</h4>
            <pre>PHP Version: <?php echo phpversion(); ?>
Server Software: <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'N/A'; ?>
Request Time: <?php echo date('Y-m-d H:i:s'); ?></pre>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

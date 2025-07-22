<?php
require_once 'config.php';

// Check if products with IDs 1 and 2 exist
$productIds = [1, 2];
$placeholders = rtrim(str_repeat('?,', count($productIds)), ',');

// Get product details
$stmt = $conn->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
$stmt->execute($productIds);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get cart items for user 2
$userId = 2;
$cartStmt = $conn->prepare("SELECT * FROM cart WHERE user_id = ?");
$cartStmt->execute([$userId]);
$cartItems = $cartStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Check</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>Product Check</h1>
        
        <div class="card mt-4">
            <div class="card-header">
                <h2>Products in Cart</h2>
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Cart ID</th>
                            <th>Product ID</th>
                            <th>Product Name</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Exists in DB</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cartItems as $item): 
                            $productExists = false;
                            $productName = 'Not Found';
                            $productPrice = 'N/A';
                            
                            foreach ($products as $product) {
                                if ($product['id'] == $item['product_id']) {
                                    $productExists = true;
                                    $productName = htmlspecialchars($product['name']);
                                    $productPrice = '₹' . number_format($product['price'], 2);
                                    break;
                                }
                            }
                        ?>
                            <tr class="<?php echo $productExists ? '' : 'table-warning'; ?>">
                                <td><?php echo $item['id']; ?></td>
                                <td><?php echo $item['product_id']; ?></td>
                                <td><?php echo $productName; ?></td>
                                <td><?php echo $productPrice; ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td>
                                    <?php if ($productExists): ?>
                                        <span class="badge bg-success">Yes</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">No</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h2>Products Table Structure</h2>
            </div>
            <div class="card-body">
                <?php 
                $stmt = $conn->query("DESCRIBE products");
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Column</th>
                            <th>Type</th>
                            <th>Null</th>
                            <th>Key</th>
                            <th>Default</th>
                            <th>Extra</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($columns as $col): ?>
                            <tr>
                                <td><?php echo $col['Field']; ?></td>
                                <td><?php echo $col['Type']; ?></td>
                                <td><?php echo $col['Null']; ?></td>
                                <td><?php echo $col['Key']; ?></td>
                                <td><?php echo $col['Default'] ?? 'NULL'; ?></td>
                                <td><?php echo $col['Extra']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h2>Sample Products</h2>
            </div>
            <div class="card-body">
                <?php 
                $sampleProducts = $conn->query("SELECT * FROM products LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
                if (empty($sampleProducts)): 
                ?>
                    <div class="alert alert-warning">No products found in the database!</div>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Price</th>
                                <th>Image</th>
                                <th>Created At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sampleProducts as $product): ?>
                                <tr>
                                    <td><?php echo $product['id']; ?></td>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td>₹<?php echo number_format($product['price'], 2); ?></td>
                                    <td>
                                        <?php if (!empty($product['image'])): ?>
                                            <img src="admin/uploads/<?php echo htmlspecialchars($product['image']); ?>" 
                                                 style="max-width: 50px;" 
                                                 alt="<?php echo htmlspecialchars($product['name']); ?>">
                                        <?php else: ?>
                                            No image
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $product['created_at'] ?? 'N/A'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

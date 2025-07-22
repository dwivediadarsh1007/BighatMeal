<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include config
require_once 'config.php';

// Check if products exist
$products = $conn->query("SELECT * FROM products")->fetchAll(PDO::FETCH_ASSOC);

// Check cart items
$cart_items = [];
if (isset($_GET['user_id'])) {
    $user_id = (int)$_GET['user_id'];
    $cart_items = $conn->prepare("SELECT * FROM cart WHERE user_id = ?")->execute([$user_id])->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Products</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>Product Check</h1>
        
        <div class="card mt-4">
            <div class="card-header">
                <h2>Products in Database</h2>
            </div>
            <div class="card-body">
                <?php if (empty($products)): ?>
                    <div class="alert alert-warning">No products found in the database!</div>
                <?php else: ?>
                    <p>Found <?php echo count($products); ?> products in the database.</p>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Price</th>
                                <th>Image</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($products, 0, 5) as $product): ?>
                                <tr>
                                    <td><?php echo $product['id']; ?></td>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td>₹<?php echo number_format($product['price'], 2); ?></td>
                                    <td>
                                        <?php if (!empty($product['image'])): ?>
                                            <img src="admin/uploads/<?php echo htmlspecialchars($product['image']); ?>" 
                                                 style="max-width: 50px; max-height: 50px;" 
                                                 alt="<?php echo htmlspecialchars($product['name']); ?>">
                                        <?php else: ?>
                                            No image
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if (count($products) > 5): ?>
                        <p>... and <?php echo (count($products) - 5); ?> more products</p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (!empty($cart_items)): ?>
            <div class="card mt-4">
                <div class="card-header">
                    <h2>Cart Items for User ID: <?php echo $user_id; ?></h2>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Cart ID</th>
                                <th>Product ID</th>
                                <th>Quantity</th>
                                <th>Product Exists</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart_items as $item): 
                                $product_exists = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE id = ?")->execute([$item['product_id']])->fetch()['count'] > 0;
                            ?>
                                <tr class="<?php echo $product_exists ? '' : 'table-warning'; ?>">
                                    <td><?php echo $item['id']; ?></td>
                                    <td><?php echo $item['product_id']; ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td>
                                        <?php if ($product_exists): ?>
                                            <span class="badge bg-success">Yes</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">No</span>
                                            (Product not found in database)
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="card mt-4">
            <div class="card-header">
                <h2>Check Cart for User</h2>
            </div>
            <div class="card-body">
                <form method="get" class="row g-3">
                    <div class="col-auto">
                        <label for="user_id" class="form-label">User ID:</label>
                        <input type="number" class="form-control" id="user_id" name="user_id" required 
                               value="<?php echo $_GET['user_id'] ?? ''; ?>">
                    </div>
                    <div class="col-auto align-self-end">
                        <button type="submit" class="btn btn-primary">Check Cart</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

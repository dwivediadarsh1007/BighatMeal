<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Handle product actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                // Handle file upload
                $imagePath = '';
                if(isset($_FILES['image_upload']) && $_FILES['image_upload']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = '../uploads/products/';
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    
                    $fileExtension = pathinfo($_FILES['image_upload']['name'], PATHINFO_EXTENSION);
                    $fileName = uniqid() . '.' . $fileExtension;
                    $targetPath = $uploadDir . $fileName;
                    
                    if(move_uploaded_file($_FILES['image_upload']['tmp_name'], $targetPath)) {
                        $imagePath = 'uploads/products/' . $fileName;
                    }
                } else if (!empty($_POST['existing_image'])) {
                    $imagePath = $_POST['existing_image'];
                }
                
                $stmt = $conn->prepare("INSERT INTO products (category_id, name, description, price, image_url, is_available) 
                                      VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['category_id'],
                    $_POST['name'],
                    $_POST['description'],
                    $_POST['price'],
                    $imagePath,
                    isset($_POST['is_available']) ? 1 : 0
                ]);
                break;
            case 'edit':
                // Handle file upload for edit
                $imagePath = '';
                if(isset($_FILES['image_upload']) && $_FILES['image_upload']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = '../uploads/products/';
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    
                    $fileExtension = pathinfo($_FILES['image_upload']['name'], PATHINFO_EXTENSION);
                    $fileName = uniqid() . '.' . $fileExtension;
                    $targetPath = $uploadDir . $fileName;
                    
                    if(move_uploaded_file($_FILES['image_upload']['tmp_name'], $targetPath)) {
                        $imagePath = 'uploads/products/' . $fileName;
                        // Delete old image if it exists and is not the default image
                        if (!empty($_POST['existing_image']) && strpos($_POST['existing_image'], 'default') === false) {
                            @unlink('../' . $_POST['existing_image']);
                        }
                    }
                } else if (!empty($_POST['existing_image'])) {
                    $imagePath = $_POST['existing_image'];
                }
                
                $stmt = $conn->prepare("UPDATE products SET 
                                        category_id = ?, 
                                        name = ?, 
                                        description = ?, 
                                        price = ?, 
                                        image_url = ?, 
                                        is_available = ? 
                                        WHERE id = ?");
                $stmt->execute([
                    $_POST['category_id'],
                    $_POST['name'],
                    $_POST['description'],
                    $_POST['price'],
                    $imagePath,
                    isset($_POST['is_available']) ? 1 : 0,
                    $_POST['id']
                ]);
                break;
            case 'delete':
                $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                break;
        }
        header('Location: products.php');
        exit();
    }
}

// Get all products and categories
$stmt = $conn->query("SELECT p.*, c.name as category_name 
                     FROM products p 
                     JOIN categories c ON p.category_id = c.id");
$products = $stmt->fetchAll();

$stmt = $conn->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Manage Products</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                        <i class="bi bi-plus-lg"></i> Add New Product
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-sm">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?php echo $product['id']; ?></td>
                                <td>
                                    <img src="<?php echo $product['image_url']; ?>" 
                                         alt="<?php echo $product['name']; ?>" 
                                         class="product-image" 
                                         style="width: 50px; height: 50px; object-fit: cover;">
                                </td>
                                <td><?php echo $product['name']; ?></td>
                                <td><?php echo $product['category_name']; ?></td>
                                <td>$<?php echo number_format($product['price'], 2); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $product['is_available'] ? 'success' : 'danger'; ?>">
                                        <?php echo $product['is_available'] ? 'Available' : 'Not Available'; ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-info" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editProductModal" 
                                            onclick="editProduct(<?php echo json_encode($product); ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form action="" method="POST" class="d-inline" 
                                          onsubmit="return confirm('Are you sure you want to delete this product?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price</label>
                            <input type="number" name="price" class="form-control" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Image URL</label>
                            <input type="file" name="image_upload" class="form-control" accept="image/*">
                            <small class="text-muted">Leave empty to keep current image</small>
                            <input type="hidden" name="existing_image" id="existingImage">
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="is_available" class="form-check-input" checked>
                                <label class="form-check-label">Available</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div class="modal fade" id="editProductModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="editProductId">
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-select" id="editCategory" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" id="editName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" id="editDescription" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price</label>
                            <input type="number" name="price" class="form-control" id="editPrice" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Image URL</label>
                            <div class="mb-2">
                                <img src="../<?php echo $product['image_url']; ?>" id="currentImagePreview" style="max-height: 100px;" class="img-thumbnail mb-2">
                            </div>
                            <input type="file" name="image_upload" class="form-control" accept="image/*" id="editImageUpload">
                            <small class="text-muted">Leave empty to keep current image</small>
                            <input type="hidden" name="existing_image" id="editExistingImage">
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="is_available" class="form-check-input" id="editIsAvailable">
                                <label class="form-check-label">Available</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function editProduct(product) {
        document.getElementById('editProductId').value = product.id;
        document.getElementById('editCategory').value = product.category_id;
        document.getElementById('editName').value = product.name;
        document.getElementById('editDescription').value = product.description;
        document.getElementById('editPrice').value = product.price;
        document.getElementById('currentImagePreview').src = '../' + product.image_url;
        document.getElementById('editExistingImage').value = product.image_url;
        document.getElementById('editIsAvailable').checked = product.is_available === 1;
    }
    </script>
</body>
</html>

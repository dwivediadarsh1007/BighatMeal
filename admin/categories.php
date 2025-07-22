<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Handle category actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            // Handle file upload
            $image_url = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $max_size = 2 * 1024 * 1024; // 2MB
                
                if ($_FILES['image']['size'] > $max_size) {
                    throw new Exception('Image size too large. Maximum allowed size is 2MB.');
                }
                
                if (!in_array($_FILES['image']['type'], $allowed_types)) {
                    throw new Exception('Invalid image format. Only JPG, PNG, and GIF are allowed.');
                }
                
                // Generate unique filename
                $filename = uniqid() . '_' . basename($_FILES['image']['name']);
                $upload_dir = '../uploads/categories/';
                
                // Create upload directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Move uploaded file
                $target_path = $upload_dir . $filename;
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                    throw new Exception('Failed to upload image.');
                }
                
                // Store relative path in database
                $image_url = 'uploads/categories/' . $filename;
            }

            switch ($_POST['action']) {
                case 'add':
                    $stmt = $conn->prepare("INSERT INTO categories (name, description, image_url) VALUES (?, ?, ?)");
                    $stmt->execute([
                        $_POST['name'],
                        $_POST['description'],
                        $image_url
                    ]);
                    break;
                
                case 'edit':
                    // Delete old image if new one is uploaded
                    if ($image_url && isset($_POST['current_image_url'])) {
                        $old_image = '../' . $_POST['current_image_url'];
                        if (file_exists($old_image)) {
                            unlink($old_image);
                        }
                    }
                    
                    $stmt = $conn->prepare("UPDATE categories SET 
                                            name = ?, 
                                            description = ?, 
                                            image_url = ? 
                                            WHERE id = ?");
                    $stmt->execute([
                        $_POST['name'],
                        $_POST['description'],
                        $image_url,
                        $_POST['id']
                    ]);
                    break;
                
                case 'delete':
                    // Get image URL before deletion
                    $stmt = $conn->prepare("SELECT image_url FROM categories WHERE id = ?");
                    $stmt->execute([$_POST['id']]);
                    $category = $stmt->fetch();
                    
                    // Delete image file if it exists
                    if ($category && $category['image_url']) {
                        $image_path = '../' . $category['image_url'];
                        if (file_exists($image_path)) {
                            unlink($image_path);
                        }
                    }
                    
                    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
                    $stmt->execute([$_POST['id']]);
                    break;
            }
            
            $_SESSION['success_message'] = 'Category ' . ($_POST['action'] === 'delete' ? 'deleted' : 'updated') . ' successfully!';
        }
        
        header('Location: categories.php');
        exit();
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
        header('Location: categories.php');
        exit();
    }
}

// Get all categories
$stmt = $conn->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - Admin</title>
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
                    <h1 class="h2">Manage Categories</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        <i class="bi bi-plus-lg"></i> Add New Category
                    </button>
                </div>

                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success">
                        <?php 
                            echo $_SESSION['success_message']; 
                            unset($_SESSION['success_message']); 
                        ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger">
                        <?php 
                            echo $_SESSION['error_message']; 
                            unset($_SESSION['error_message']); 
                        ?>
                    </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-striped table-sm">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><?php echo $category['id']; ?></td>
                                <td>
                                    <img src="<?php echo $category['image_url']; ?>" 
                                         alt="<?php echo $category['name']; ?>" 
                                         class="category-image" 
                                         style="width: 50px; height: 50px; object-fit: cover;">
                                </td>
                                <td><?php echo $category['name']; ?></td>
                                <td><?php echo $category['description']; ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editCategoryModal" 
                                            onclick="editCategory(<?php echo json_encode($category); ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form action="./categories.php" method="POST" class="d-inline" 
                                          onsubmit="return confirm('Are you sure you want to delete this category?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
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

    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="./categories.php" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Image</label>
                            <input type="file" name="image" class="form-control" accept="image/*" required>
                            <div class="form-text">Supported formats: JPG, PNG, GIF. Maximum size: 2MB</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="./categories.php" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="editCategoryId">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" id="editCategoryName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" id="editCategoryDescription" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Image</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                            <div class="form-text">Supported formats: JPG, PNG, GIF. Maximum size: 2MB</div>
                        </div>
                        <div class="mb-3">
                            <img id="editCategoryPreview" src="" alt="Current Image" class="img-thumbnail" style="max-width: 200px; display: none;">
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
    function editCategory(category) {
        document.getElementById('editCategoryId').value = category.id;
        document.getElementById('editCategoryName').value = category.name;
        document.getElementById('editCategoryDescription').value = category.description;
        
        // Show current image preview
        const preview = document.getElementById('editCategoryPreview');
        preview.src = category.image_url;
        preview.style.display = 'block';
        
        // Store current image URL for deletion if needed
        document.getElementById('editCategoryForm').insertAdjacentHTML('beforeend', 
            '<input type="hidden" name="current_image_url" value="' + category.image_url + '">');
    }
    
    // Preview image when selected
    document.getElementById('editCategoryForm').addEventListener('change', function(e) {
        if (e.target.name === 'image' && e.target.files.length > 0) {
            const preview = document.getElementById('editCategoryPreview');
            preview.src = URL.createObjectURL(e.target.files[0]);
            preview.style.display = 'block';
        }
    });
    </script>
</body>
</html>

<?php
session_start();
require_once '../config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                try {
                    // Validate required fields
                    if (empty($_POST['name'])) {
                        throw new Exception('Item name is required');
                    }
                    
                    $sql = "INSERT INTO customize_items (
                        name, description, price, calories, protein, carbs, fat, fiber, sugar, 
                        is_vegetarian, is_vegan, is_gluten_free, image_url, is_available
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    
                    $stmt = $conn->prepare($sql);
                    
                    // Prepare parameters
                    $params = [
                        $_POST['name'] ?? '',
                        $_POST['description'] ?? '',
                        floatval($_POST['price'] ?? 0),
                        intval($_POST['calories'] ?? 0),
                        floatval($_POST['protein'] ?? 0),
                        floatval($_POST['carbs'] ?? 0),
                        floatval($_POST['fat'] ?? 0),
                        floatval($_POST['fiber'] ?? 0),
                        floatval($_POST['sugar'] ?? 0),
                        isset($_POST['is_vegetarian']) ? 1 : 0,
                        isset($_POST['is_vegan']) ? 1 : 0,
                        isset($_POST['is_gluten_free']) ? 1 : 0,
                        $_POST['image_url'] ?? '',
                        isset($_POST['is_available']) ? 1 : 0
                    ];
                    
                    $stmt->execute($params);
                    $_SESSION['success'] = 'Item added successfully!';
                    
                } catch (Exception $e) {
                    $_SESSION['error'] = 'Error: ' . $e->getMessage();
                }
                break;
                
            case 'edit':
                try {
                    if (empty($_POST['id'])) {
                        throw new Exception('Item ID is required');
                    }
                    
                    $sql = "UPDATE customize_items SET 
                        name = ?, description = ?, price = ?, calories = ?, protein = ?, 
                        carbs = ?, fat = ?, fiber = ?, sugar = ?, is_vegetarian = ?, 
                        is_vegan = ?, is_gluten_free = ?, image_url = ?, is_available = ? 
                        WHERE id = ?";
                    
                    $stmt = $conn->prepare($sql);
                    
                    // Prepare parameters
                    $params = [
                        $_POST['name'] ?? '',
                        $_POST['description'] ?? '',
                        floatval($_POST['price'] ?? 0),
                        intval($_POST['calories'] ?? 0),
                        floatval($_POST['protein'] ?? 0),
                        floatval($_POST['carbs'] ?? 0),
                        floatval($_POST['fat'] ?? 0),
                        floatval($_POST['fiber'] ?? 0),
                        floatval($_POST['sugar'] ?? 0),
                        isset($_POST['is_vegetarian']) ? 1 : 0,
                        isset($_POST['is_vegan']) ? 1 : 0,
                        isset($_POST['is_gluten_free']) ? 1 : 0,
                        $_POST['image_url'] ?? '',
                        isset($_POST['is_available']) ? 1 : 0,
                        intval($_POST['id'])
                    ];
                    
                    $stmt->execute($params);
                    $_SESSION['success'] = 'Item updated successfully!';
                    
                } catch (Exception $e) {
                    $_SESSION['error'] = 'Error: ' . $e->getMessage();
                }
                break;
                
            case 'delete':
                try {
                    if (empty($_POST['id'])) {
                        throw new Exception('Item ID is required');
                    }
                    
                    $stmt = $conn->prepare("DELETE FROM customize_items WHERE id = ?");
                    $stmt->execute([intval($_POST['id'])]);
                    $_SESSION['success'] = 'Item deleted successfully!';
                    
                } catch (Exception $e) {
                    $_SESSION['error'] = 'Error: ' . $e->getMessage();
                }
                break;
        }
        
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Check if table exists and create if not
try {
    $tableExists = $conn->query("SELECT 1 FROM customize_items LIMIT 1") !== false;
} catch (Exception $e) {
    $tableExists = false;
}

if (!$tableExists) {
    try {
        $createTableSQL = "CREATE TABLE IF NOT EXISTS `customize_items` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(255) NOT NULL,
            `description` TEXT,
            `price` DECIMAL(10,2) NOT NULL DEFAULT '0.00',
            `calories` INT(11) DEFAULT 0,
            `protein` DECIMAL(5,1) DEFAULT 0.0,
            `carbs` DECIMAL(5,1) DEFAULT 0.0,
            `fat` DECIMAL(5,1) DEFAULT 0.0,
            `fiber` DECIMAL(5,1) DEFAULT 0.0,
            `sugar` DECIMAL(5,1) DEFAULT 0.0,
            `is_vegetarian` TINYINT(1) DEFAULT 0,
            `is_vegan` TINYINT(1) DEFAULT 0,
            `is_gluten_free` TINYINT(1) DEFAULT 0,
            `image_url` VARCHAR(255) DEFAULT '',
            `is_available` TINYINT(1) DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        $conn->exec($createTableSQL);
        
    } catch (PDOException $e) {
        die('Error creating table: ' . $e->getMessage());
    }
}

// Get all customize items
try {
    $stmt = $conn->query("SELECT * FROM customize_items ORDER BY name");
    $customizeItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $customizeItems = [];
    $_SESSION['error'] = 'Error loading items: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Customize Items - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container mt-4">
        <h2>Manage Customize Items</h2>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <div class="mb-3">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">
                <i class="fas fa-plus"></i> Add New Item
            </button>
        </div>
        
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Calories</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customizeItems as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['id']) ?></td>
                            <td><?= htmlspecialchars($item['name']) ?></td>
                            <td>$<?= number_format($item['price'], 2) ?></td>
                            <td><?= $item['calories'] ?> cal</td>
                            <td>
                                <span class="badge <?= $item['is_available'] ? 'bg-success' : 'bg-secondary' ?>">
                                    <?= $item['is_available'] ? 'Available' : 'Unavailable' ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-warning edit-btn" 
                                        data-id="<?= $item['id'] ?>"
                                        data-name="<?= htmlspecialchars($item['name']) ?>"
                                        data-description="<?= htmlspecialchars($item['description']) ?>"
                                        data-price="<?= $item['price'] ?>"
                                        data-calories="<?= $item['calories'] ?>"
                                        data-protein="<?= $item['protein'] ?>"
                                        data-carbs="<?= $item['carbs'] ?>"
                                        data-fat="<?= $item['fat'] ?>"
                                        data-fiber="<?= $item['fiber'] ?>"
                                        data-sugar="<?= $item['sugar'] ?>"
                                        data-is-vegetarian="<?= $item['is_vegetarian'] ?>"
                                        data-is-vegan="<?= $item['is_vegan'] ?>"
                                        data-is-gluten-free="<?= $item['is_gluten_free'] ?>"
                                        data-image-url="<?= htmlspecialchars($item['image_url']) ?>"
                                        data-is-available="<?= $item['is_available'] ?>">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <form action="" method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this item?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($customizeItems)): ?>
                        <tr>
                            <td colspan="6" class="text-center">No customize items found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Item Modal -->
    <div class="modal fade" id="addItemModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="" method="post">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Customize Item</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Name *</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Price *</label>
                                <input type="number" class="form-control" name="price" step="0.01" min="0" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="2"></textarea>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Calories</label>
                                <input type="number" class="form-control" name="calories" min="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Protein (g)</label>
                                <input type="number" class="form-control" name="protein" step="0.1" min="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Carbs (g)</label>
                                <input type="number" class="form-control" name="carbs" step="0.1" min="0">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Fat (g)</label>
                                <input type="number" class="form-control" name="fat" step="0.1" min="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Fiber (g)</label>
                                <input type="number" class="form-control" name="fiber" step="0.1" min="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Sugar (g)</label>
                                <input type="number" class="form-control" name="sugar" step="0.1" min="0">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_vegetarian" id="is_vegetarian">
                                    <label class="form-check-label" for="is_vegetarian">Vegetarian</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_vegan" id="is_vegan">
                                    <label class="form-check-label" for="is_vegan">Vegan</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_gluten_free" id="is_gluten_free">
                                    <label class="form-check-label" for="is_gluten_free">Gluten Free</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Image URL</label>
                            <input type="url" class="form-control" name="image_url" placeholder="https://example.com/image.jpg">
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="is_available" id="is_available" checked>
                            <label class="form-check-label" for="is_available">Available</label>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Item Modal -->
    <div class="modal fade" id="editItemModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="" method="post">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit-id">
                    
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Customize Item</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Name *</label>
                                <input type="text" class="form-control" id="edit-name" name="name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Price *</label>
                                <input type="number" class="form-control" id="edit-price" name="price" step="0.01" min="0" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" id="edit-description" name="description" rows="2"></textarea>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Calories</label>
                                <input type="number" class="form-control" id="edit-calories" name="calories" min="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Protein (g)</label>
                                <input type="number" class="form-control" id="edit-protein" name="protein" step="0.1" min="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Carbs (g)</label>
                                <input type="number" class="form-control" id="edit-carbs" name="carbs" step="0.1" min="0">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Fat (g)</label>
                                <input type="number" class="form-control" id="edit-fat" name="fat" step="0.1" min="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Fiber (g)</label>
                                <input type="number" class="form-control" id="edit-fiber" name="fiber" step="0.1" min="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Sugar (g)</label>
                                <input type="number" class="form-control" id="edit-sugar" name="sugar" step="0.1" min="0">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_vegetarian" id="edit-is-vegetarian">
                                    <label class="form-check-label" for="edit-is-vegetarian">Vegetarian</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_vegan" id="edit-is-vegan">
                                    <label class="form-check-label" for="edit-is-vegan">Vegan</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_gluten_free" id="edit-is-gluten-free">
                                    <label class="form-check-label" for="edit-is-gluten-free">Gluten Free</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Image URL</label>
                            <input type="url" class="form-control" id="edit-image-url" name="image_url" placeholder="https://example.com/image.jpg">
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="is_available" id="edit-is-available">
                            <label class="form-check-label" for="edit-is-available">Available</label>
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
        // Handle edit button click
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                const modal = new bootstrap.Modal(document.getElementById('editItemModal'));
                
                // Populate form fields
                document.getElementById('edit-id').value = this.dataset.id;
                document.getElementById('edit-name').value = this.dataset.name;
                document.getElementById('edit-description').value = this.dataset.description;
                document.getElementById('edit-price').value = this.dataset.price;
                document.getElementById('edit-calories').value = this.dataset.calories;
                document.getElementById('edit-protein').value = this.dataset.protein;
                document.getElementById('edit-carbs').value = this.dataset.carbs;
                document.getElementById('edit-fat').value = this.dataset.fat;
                document.getElementById('edit-fiber').value = this.dataset.fiber;
                document.getElementById('edit-sugar').value = this.dataset.sugar;
                document.getElementById('edit-is-vegetarian').checked = this.dataset.isVegetarian === '1';
                document.getElementById('edit-is-vegan').checked = this.dataset.isVegan === '1';
                document.getElementById('edit-is-gluten-free').checked = this.dataset.isGlutenFree === '1';
                document.getElementById('edit-image-url').value = this.dataset.imageUrl;
                document.getElementById('edit-is-available').checked = this.dataset.isAvailable === '1';
                
                modal.show();
            });
        });
        
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>
</html>

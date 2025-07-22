<?php
session_start();
require_once '../config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'add':
                $sql = "INSERT INTO customize_items (name, description, price, is_available) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $result = $stmt->execute([
                    $_POST['name'] ?? '',
                    $_POST['description'] ?? '',
                    floatval($_POST['price'] ?? 0),
                    isset($_POST['is_available']) ? 1 : 0
                ]);
                if ($result) $_SESSION['success'] = 'Item added!';
                else throw new Exception('Failed to add item');
                break;
                
            case 'edit':
                $sql = "UPDATE customize_items SET name = ?, description = ?, price = ?, is_available = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $result = $stmt->execute([
                    $_POST['name'] ?? '',
                    $_POST['description'] ?? '',
                    floatval($_POST['price'] ?? 0),
                    isset($_POST['is_available']) ? 1 : 0,
                    $_POST['id']
                ]);
                if ($result) $_SESSION['success'] = 'Item updated!';
                else throw new Exception('Failed to update item');
                break;
                
            case 'delete':
                $stmt = $conn->prepare("DELETE FROM customize_items WHERE id = ?");
                $result = $stmt->execute([$_POST['id']]);
                if ($result) $_SESSION['success'] = 'Item deleted!';
                else throw new Exception('Failed to delete item');
                break;
        }
        header('Location: customize.php');
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error: ' . $e->getMessage();
        header('Location: customize.php');
        exit();
    }
}

// Create table if not exists
try {
    $conn->exec("CREATE TABLE IF NOT EXISTS `customize_items` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(255) NOT NULL,
        `description` TEXT,
        `price` DECIMAL(10,2) NOT NULL DEFAULT '0.00',
        `is_available` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (PDOException $e) {
    die('Error creating table: ' . $e->getMessage());
}

// Get all items
try {
    $stmt = $conn->query("SELECT * FROM customize_items ORDER BY name");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $items = [];
    $_SESSION['error'] = 'Error loading items: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Customize Items</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container mt-4">
        <h1>Manage Customize Items</h1>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addItemModal">
            <i class="fas fa-plus"></i> Add New Item
        </button>
        
        <div class="row">
            <?php if (empty($items)): ?>
                <div class="col-12">
                    <div class="alert alert-info">No items found. Add your first item!</div>
                </div>
            <?php else: ?>
                <?php foreach ($items as $item): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($item['name']) ?></h5>
                                <p class="card-text"><?= nl2br(htmlspecialchars($item['description'])) ?></p>
                                <p class="h5">$<?= number_format($item['price'], 2) ?></p>
                                <span class="badge bg-<?= $item['is_available'] ? 'success' : 'secondary' ?>">
                                    <?= $item['is_available'] ? 'Available' : 'Unavailable' ?>
                                </span>
                            </div>
                            <div class="card-footer">
                                <button class="btn btn-sm btn-outline-primary edit-item" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editItemModal"
                                        data-item='<?= htmlspecialchars(json_encode($item), ENT_QUOTES, 'UTF-8') ?>'>
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                
                                <form action="" method="POST" class="d-inline" 
                                      onsubmit="return confirm('Delete this item?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                                
                                <form action="" method="POST" class="d-inline ms-2">
                                    <input type="hidden" name="action" value="edit">
                                    <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                    <input type="hidden" name="name" value="<?= htmlspecialchars($item['name']) ?>">
                                    <input type="hidden" name="description" value="<?= htmlspecialchars($item['description']) ?>">
                                    <input type="hidden" name="price" value="<?= $item['price'] ?>">
                                    <input type="hidden" name="is_available" value="<?= $item['is_available'] ? 0 : 1 ?>">
                                    <button type="submit" class="btn btn-sm btn-<?= $item['is_available'] ? 'warning' : 'success' ?>">
                                        <?= $item['is_available'] ? 'Make Unavailable' : 'Make Available' ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Item Modal -->
    <div class="modal fade" id="addItemModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="" method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Item</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Name *</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price</label>
                            <input type="number" class="form-control" name="price" step="0.01" min="0" value="0.00" required>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_available" value="1" checked>
                            <label class="form-check-label">Available</label>
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
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="" method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Item</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Name *</label>
                            <input type="text" class="form-control" name="name" id="edit_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="edit_description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price</label>
                            <input type="number" class="form-control" name="price" id="edit_price" step="0.01" min="0" required>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_available" id="edit_is_available" value="1">
                            <label class="form-check-label">Available</label>
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
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Handle edit modal population
        document.querySelectorAll('.edit-item').forEach(button => {
            button.addEventListener('click', function() {
                const item = JSON.parse(this.getAttribute('data-item'));
                document.getElementById('edit_id').value = item.id;
                document.getElementById('edit_name').value = item.name;
                document.getElementById('edit_description').value = item.description;
                document.getElementById('edit_price').value = item.price;
                document.getElementById('edit_is_available').checked = item.is_available == 1;
            });
        });

        // Handle availability toggle
        document.querySelectorAll('.availability-toggle').forEach(button => {
            button.addEventListener('change', function() {
                this.closest('form').submit();
            });
        });
    </script>
</body>
</html>

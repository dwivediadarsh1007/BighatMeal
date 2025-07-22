<?php
require_once '../config.php';
require_once 'includes/admin_auth.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_locality'])) {
        $name = trim($_POST['name']);
        if (!empty($name)) {
            $stmt = $conn->prepare("INSERT INTO localities (name) VALUES (?)");
            $stmt->execute([$name]);
            $_SESSION['success'] = 'Locality added successfully';
        }
    } elseif (isset($_POST['add_area'])) {
        $locality_id = (int)$_POST['locality_id'];
        $name = trim($_POST['area_name']);
        $delivery_charge = (float)$_POST['delivery_charge'];
        $min_order_amount = (float)$_POST['min_order_amount'];
        
        if (!empty($name) && $locality_id > 0) {
            $stmt = $conn->prepare("
                INSERT INTO areas (locality_id, name, delivery_charge, min_order_amount) 
                VALUES (?, ?, ?, ?)
            ")->execute([$locality_id, $name, $delivery_charge, $min_order_amount]);
            $_SESSION['success'] = 'Area added successfully';
        }
    }
    
    header('Location: manage_localities.php');
    exit();
}

// Get all localities with their areas
$localities = $conn->query("
    SELECT l.*, 
           GROUP_CONCAT(a.name ORDER BY a.name SEPARATOR ', ') as area_names
    FROM localities l
    LEFT JOIN areas a ON l.id = a.locality_id
    GROUP BY l.id
    ORDER BY l.name
")->fetchAll(PDO::FETCH_ASSOC);

$all_localities = $conn->query("SELECT * FROM localities ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Manage Localities & Areas</h1>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <div class="row">
                <!-- Add New Locality -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Add New Locality</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Locality Name</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                <button type="submit" name="add_locality" class="btn btn-primary">Add Locality</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Add New Area -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Add New Area</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="locality_id" class="form-label">Select Locality</label>
                                    <select class="form-select" id="locality_id" name="locality_id" required>
                                        <option value="">-- Select Locality --</option>
                                        <?php foreach ($all_localities as $locality): ?>
                                            <option value="<?= $locality['id'] ?>"><?= htmlspecialchars($locality['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="area_name" class="form-label">Area Name</label>
                                    <input type="text" class="form-control" id="area_name" name="area_name" required>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="delivery_charge" class="form-label">Delivery Charge (₹)</label>
                                        <input type="number" step="0.01" class="form-control" id="delivery_charge" name="delivery_charge" value="0" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="min_order_amount" class="form-label">Min. Order Amount (₹)</label>
                                        <input type="number" step="0.01" class="form-control" id="min_order_amount" name="min_order_amount" value="0" required>
                                    </div>
                                </div>
                                <button type="submit" name="add_area" class="btn btn-primary">Add Area</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- List of Localities and Areas -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Localities & Areas</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($localities)): ?>
                        <div class="alert alert-info">No localities found. Add your first locality above.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Locality</th>
                                        <th>Areas</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($localities as $locality): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($locality['name']) ?></strong>
                                            </td>
                                            <td>
                                                <?= !empty($locality['area_names']) ? htmlspecialchars($locality['area_names']) : '<span class="text-muted">No areas added</span>' ?>
                                            </td>
                                            <td>
                                                <a href="edit_locality.php?id=<?= $locality['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

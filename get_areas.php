<?php
require_once 'config.php';

header('Content-Type: application/json');

// Check if locality_id is provided
if (!isset($_GET['locality_id']) || !is_numeric($_GET['locality_id'])) {
    echo json_encode(['error' => 'Invalid locality ID']);
    exit();
}

$localityId = (int)$_GET['locality_id'];

try {
    // Fetch active areas for the selected locality
    $stmt = $conn->prepare("
        SELECT id, name, delivery_charge, min_order_amount 
        FROM areas 
        WHERE locality_id = ? AND is_active = 1 
        ORDER BY name
    ");
    $stmt->execute([$localityId]);
    $areas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($areas);
    
} catch (Exception $e) {
    error_log("Error fetching areas: " . $e->getMessage());
    echo json_encode(['error' => 'Error loading areas']);
}
?>

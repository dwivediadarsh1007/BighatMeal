<?php
// Function to get status color
function getStatusColor($status) {
    $colors = [
        'processing' => 'primary',
        'confirmed' => 'info',
        'preparing' => 'warning',
        'out_for_delivery' => 'success',
        'delivered' => 'success',
        'cancelled' => 'danger'
    ];
    return $colors[$status] ?? 'secondary';
}
?>

<?php
session_start();
require_once '../config.php';

// Get all orders
$stmt = $conn->query("SELECT id, status FROM orders ORDER BY id DESC LIMIT 10");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Status Update</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Test Status Update</h2>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Current Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?php echo $order['id']; ?></td>
                        <td><span class="badge bg-secondary"><?php echo $order['status']; ?></span></td>
                        <td>
                            <button class="btn btn-sm btn-primary status-update-btn" 
                                    data-order-id="<?php echo $order['id']; ?>"
                                    data-status="confirmed">
                                Confirm
                            </button>
                            <button class="btn btn-sm btn-warning status-update-btn" 
                                    data-order-id="<?php echo $order['id']; ?>"
                                    data-status="out_for_delivery">
                                Out for Delivery
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.querySelectorAll('.status-update-btn').forEach(button => {
            button.addEventListener('click', async function(e) {
                e.preventDefault();
                
                const orderId = this.dataset.orderId;
                const status = this.dataset.status;
                
                try {
                    const response = await fetch('./test-status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            order_id: orderId,
                            status: status
                        })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        // Update the status badge
                        const statusBadge = this.closest('tr').querySelector('.badge');
                        if (statusBadge) {
                            statusBadge.textContent = result.status;
                        }
                        
                        alert('Status updated successfully');
                    } else {
                        alert('Error: ' + result.message);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error updating status');
                }
            });
        });
    </script>
</body>
</html>

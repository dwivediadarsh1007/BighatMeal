<?php
session_start();
require_once 'config.php';
require_once 'includes/promo_functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get cart items
$stmt = $conn->prepare("SELECT * FROM cart WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Initialize promo code variables
$promo_code = '';
$promo_discount = 0;
$promo_message = '';
$promo_info = [];

// Process cart items
$processed_items = [];
$subtotal = 0;
$delivery_fee = 0; // Free delivery

// Process each cart item
foreach ($cart_items as $item) {
    // Skip invalid items
    if (!isset($item['items'])) {
        continue;
    }

    // Decode the items array
    $items = json_decode($item['items'], true);
    if (!is_array($items)) {
        continue;
    }

    // If items is a single item, convert it to an array with one element
    if (isset($items['name'])) {
        $items = [$items];
    }

    foreach ($items as $cartItem) {
        if (!is_array($cartItem)) {
            continue;
        }

        // Get quantity from cart item, ensure it's a positive integer
        $quantity = isset($cartItem['quantity']) ? max(1, (int)$cartItem['quantity']) : 1;
        
        // Get the raw price from cart item (price per item)
        $raw_price = isset($cartItem['price']) ? (float)$cartItem['price'] : 0;
        
        // For custom meals, the price is already in rupees
        $price_per_item = $raw_price;
        
        // If the price is less than 1, assume it's in paise and convert to rupees
        if ($price_per_item > 0 && $price_per_item < 1) {
            $price_per_item = $raw_price * 100; // Convert paise to rupees
        }
        
        // Ensure we have a minimum price of 10 rupees per item
        if ($price_per_item < 10) {
            $price_per_item = 10;
        }
        
        // Round to 2 decimal places
        $price_per_item = round($price_per_item, 2);
        
        // Calculate item total (price per item * quantity)
        $item_total = $price_per_item * $quantity;
        
        // Debug log
        error_log(sprintf(
            'Item: %s - Price: ₹%.2f - Raw Price: %.2f - Quantity: %d',
            $cartItem['name'] ?? 'Unknown',
            $price_per_item,
            $raw_price,
            $quantity
        ));
        
        // Add item to processed items
        $processed_items[] = [
            'name' => $cartItem['name'] ?? 'Custom Meal',
            'quantity' => $quantity, // Use the actual quantity from cart
            'price' => $price_per_item, // Price per item
            'total_price' => $item_total, // Total price for all quantities
            'price_in_paise' => (int)($price_per_item * 100), // Store price per item in paise as integer
            'product_id' => $cartItem['id'] ?? $cartItem['product_id'] ?? null,
            'meal_type' => $item['meal_type'] ?? 'standard',
            'calories' => $cartItem['calories'] ?? 0,
            'protein' => $cartItem['protein'] ?? 0,
            'carbs' => $cartItem['carbs'] ?? 0,
            'fat' => $cartItem['fat'] ?? 0,
            'fiber' => $cartItem['fiber'] ?? 0
        ];
        
        // Add to subtotal (in rupees) - multiply price by quantity
        $subtotal += $item_total;
        
        // Debug log
        error_log(sprintf(
            'Item: %s - Price: ₹%.2f - Quantity: %d - Subtotal: ₹%.2f',
            $cartItem['name'] ?? 'Unknown',
            $price_per_item,
            $price_in_rupees,
            $quantity,
            $subtotal
        ));
    }
}

// Calculate totals
$delivery_fee = 0; // Free delivery
$total = $subtotal + $delivery_fee;
$delivery_fee_rupees = $delivery_fee; // For consistency

// Round to 2 decimal places
$subtotal = round($subtotal, 2);
$total = round($total, 2);

// Get user addresses
$stmt = $conn->prepare("SELECT * FROM addresses WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$addresses = $stmt->fetchAll();

if (empty($addresses)) {
    header('Location: address.php?redirect=checkout');
    exit();
}

// Process checkout
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $address_id = $_POST['address_id'] ?? '';
    $payment_method = $_POST['payment_method'] ?? 'cod';
    
    // Validate form data
    if (empty($address_id)) {
        $error = "Please select a delivery address";
    } else if (!in_array($payment_method, ['cod', 'online'])) {
        $error = "Invalid payment method";
    }
    
    if (empty($error)) {
        try {
            // Debug log before starting transaction
            error_log('Starting order transaction');
            error_log('User ID: ' . $_SESSION['user_id']);
            error_log('Address ID: ' . $address_id);
            error_log('Total Amount: ' . $total);
            error_log('Payment Method: ' . $payment_method);
            error_log('Number of items: ' . count($processed_items));
            
            $conn->beginTransaction();
            
            // Insert order
            $stmt = $conn->prepare("INSERT INTO orders (user_id, address_id, total_amount, payment_method, status) 
                VALUES (?, ?, ?, ?, 'processing')");
            $stmt->execute([
                $_SESSION['user_id'], 
                $address_id, 
                $total, 
                $payment_method
            ]);
            
            $order_id = $conn->lastInsertId();
            error_log('Order created with ID: ' . $order_id);
            
            // Insert order items
            foreach ($processed_items as $index => $item) {
                // Debug log item details
                error_log(sprintf('Processing item %d: %s', $index, print_r($item, true)));
                
                // Get product ID by name if not set
                $product_id = $item['product_id'] ?? null;
                if (!$product_id && !empty($item['name'])) {
                    $productStmt = $conn->prepare("SELECT id FROM products WHERE name = ? LIMIT 1");
                    $productStmt->execute([$item['name']]);
                    $product = $productStmt->fetch(PDO::FETCH_ASSOC);
                    if ($product) {
                        $product_id = $product['id'];
                        error_log(sprintf('Found product ID %d for %s', $product_id, $item['name']));
                    } else {
                        error_log(sprintf('No product found for name: %s', $item['name']));
                    }
                }
                
                // Calculate price in paise
                $price_in_paise = $item['price_in_paise'] ?? ($item['price'] * 100);
                $quantity = $item['quantity'] ?? 1;
                
                // Debug log before inserting order item
                error_log(sprintf('Inserting order item - Order ID: %d, Product ID: %s, Name: %s, Qty: %d, Price: %d paise', 
                    $order_id, 
                    $product_id ?? 'NULL', 
                    $item['name'] ?? 'Custom Item', 
                    $quantity,
                    $price_in_paise
                ));
                
                // Prepare item name - use custom name if available, otherwise use product name or 'Custom Meal'
                $item_name = $item['name'] ?? 'Custom Meal';
                
                // For custom meals, include the details if available
                if (isset($item['is_custom']) && $item['is_custom'] && isset($item['custom_details'])) {
                    $item_name = $item['custom_details']['name'] ?? $item_name;
                }
                
                // Insert order item with product name
                $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, price, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->execute([
                    $order_id,
                    $product_id,
                    $item_name,
                    $quantity,
                    $price_in_paise
                ]);
                
                error_log('Order item inserted successfully');
            }
            
            // Clear cart
            $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            error_log('Cart cleared for user ID: ' . $_SESSION['user_id']);
            
            $conn->commit();
            error_log('Order transaction committed successfully');
            
            // Redirect to success page
            header('Location: order-success.php?order_id=' . $order_id);
            exit();
            
        } catch (Exception $e) {
            // Rollback any transaction
            if ($conn->inTransaction()) {
                $conn->rollBack();
                error_log('Transaction rolled back');
            }
            
            // Log detailed error
            $error_msg = 'Order Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine();
            error_log($error_msg);
            
            // Log the backtrace
            error_log('Backtrace: ' . print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), true));
            
            // Set user-friendly error message
            $error = "Error processing order: " . $e->getMessage() . ". Please try again or contact support.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - BighatMeal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container mt-4">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Delivery Address</h5>
                        
                        <form method="POST" action="">
                            <div class="mb-4">
                                <label class="form-label fw-bold">Select Delivery Address</label>
                                <select name="address_id" class="form-select" required>
                                    <option value="">Select an address</option>
                                    <?php foreach ($addresses as $address): ?>
                                        <option value="<?php echo $address['id']; ?>" <?php echo (isset($_POST['address_id']) && $_POST['address_id'] == $address['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($address['address_line1']); ?>, 
                                            <?php echo htmlspecialchars($address['city']); ?>, 
                                            <?php echo htmlspecialchars($address['state']); ?> - 
                                            <?php echo htmlspecialchars($address['pincode']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">
                                    <a href="address.php?redirect=checkout" class="text-decoration-none">+ Add new address</a>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold">Payment Method</label>
                                <div class="border rounded p-3">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="payment_method" id="cod" value="cod" <?php echo (!isset($_POST['payment_method']) || $_POST['payment_method'] === 'cod') ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="cod">
                                            <span class="fw-medium">Cash on Delivery</span>
                                            <div class="text-muted small">Pay with cash upon delivery</div>
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method" id="online" value="online" <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] === 'online') ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="online">
                                            <span class="fw-medium">Online Payment</span>
                                            <div class="text-muted small">Pay securely with UPI, cards, or wallets</div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Order Total Summary -->
                            <div class="card bg-light border-0 mt-4 mb-4">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted">Subtotal</span>
                                        <span class="fw-medium" id="left-subtotal-amount">₹<?php echo number_format($subtotal, 2, '.', ','); ?></span>
                                    </div>
                                    <?php if ($promo_discount > 0): ?>
                                    <div class="d-flex justify-content-between align-items-center mb-2 text-success">
                                        <span>Promo Discount</span>
                                        <span id="left-promo-discount">-₹<?php echo number_format($promo_discount, 2); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted">Delivery Fee</span>
                                        <span id="left-delivery-fee">₹<?php echo number_format($delivery_fee, 2); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                                        <h6 class="mb-0 fw-bold">Total Amount</h6>
                                        <h5 class="mb-0 fw-bold text-primary" id="left-total-amount">₹<?php echo number_format($total, 2, '.', ','); ?></h5>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-lg w-100 py-3 fw-bold">
                                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                Place Order
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <div class="p-3 border-bottom">
                            <h5 class="card-title mb-0 d-flex align-items-center">
                                <i class="bi bi-cart-check me-2"></i>
                                <span>Order Summary</span>
                                <span class="badge bg-primary rounded-pill ms-auto"><?php echo count($processed_items); ?> items</span>
                            </h5>
                        </div>
                        
                        <div class="p-3 border-bottom" style="max-height: 300px; overflow-y: auto;">
                            <?php 
                            // First, get the cart items with their IDs from the database
                            $cart_stmt = $conn->prepare("SELECT id, items FROM cart WHERE user_id = ? ORDER BY created_at ASC");
                            $cart_stmt->execute([$_SESSION['user_id']]);
                            $db_cart_items = $cart_stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            // Now loop through the processed items and match them with database cart items
                            foreach ($processed_items as $index => $item): 
                                // Get the corresponding cart item from the database
                                $cart_item_id = null;
                                $db_cart_item = $db_cart_items[$index] ?? null;
                                if ($db_cart_item) {
                                    $cart_item_id = $db_cart_item['id'];
                                }
                                
                                // Get price in rupees and quantity
                                $price = isset($item['price']) ? (float)$item['price'] : 0;
                                $quantity = isset($item['quantity']) ? (int)$item['quantity'] : 1;
                                $itemTotal = $price * $quantity; // Calculate total based on quantity
                                
                                // Skip if we don't have a valid cart item ID
                                if (!$cart_item_id) continue;
                                
                                // Debug log
                                error_log(sprintf(
                                    'Displaying item: %s - Cart ID: %d - Price: ₹%.2f - Qty: %d',
                                    $item['name'] ?? 'Unknown',
                                    $cart_item_id,
                                    $price,
                                    $quantity
                                ));
                            ?>
                                <div class="d-flex justify-content-between align-items-start mb-3 pb-3 <?php echo $index < count($processed_items) - 1 ? 'border-bottom' : ''; ?>" id="item-<?php echo $cart_item_id; ?>">
                                    <div class="d-flex flex-grow-1">
                                        <div class="flex-shrink-0 me-3">
                                            <div class="bg-light rounded-3 d-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                                                <i class="bi bi-<?php echo $item['meal_type'] === 'custom-meal' ? 'egg-fried' : 'box-seam'; ?> text-muted" style="font-size: 1.5rem;"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start mb-1">
                                                <h6 class="mb-0 fw-semibold"><?php echo htmlspecialchars($item['name']); ?></h6>
                                                <button class="btn btn-sm btn-outline-danger ms-2 remove-item" data-item-id="<?php echo $cart_item_id; ?>">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                            <div class="d-flex align-items-center mb-2">
                                                <div class="input-group input-group-sm" style="width: 120px;">
                                                    <button class="btn btn-outline-secondary quantity-btn" data-action="decrease" data-item-id="<?php echo $cart_item_id; ?>">-</button>
                                                    <input type="number" class="form-control text-center quantity-input" 
                                                        value="<?php echo $quantity; ?>" min="1" 
                                                        data-item-id="<?php echo $cart_item_id; ?>"
                                                        data-price="<?php echo $price; ?>">
                                                    <button class="btn btn-outline-secondary quantity-btn" data-action="increase" data-item-id="<?php echo $cart_item_id; ?>">+</button>
                                                </div>
                                                <span class="ms-3 text-muted small">
                                                    ₹<?php echo number_format($price, 2); ?> each
                                                </span>
                                            </div>
                                            <?php if (($item['calories'] ?? 0) > 0): ?>
                                            <div class="d-flex flex-wrap gap-1">
                                                <span class="badge bg-light text-dark border small">
                                                    <i class="bi bi-fire me-1"></i><?php echo round($item['calories']); ?> cal
                                                </span>
                                                <span class="badge bg-light text-dark border small">
                                                    <i class="bi bi-droplet me-1"></i>P: <?php echo round($item['protein'] ?? 0); ?>g
                                                </span>
                                                <span class="badge bg-light text-dark border small">
                                                    <i class="bi bi-egg-fried me-1"></i>C: <?php echo round($item['carbs'] ?? 0); ?>g
                                                </span>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="text-end ms-3" style="min-width: 80px;">
                                        <div class="fw-semibold item-total">₹<?php echo number_format($itemTotal, 2); ?></div>
                                        <div class="text-muted small item-quantity"><?php echo $quantity; ?> × ₹<?php echo number_format($price, 2); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="p-3 bg-light">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">Subtotal (<span id="item-count"><?php echo count($processed_items); ?></span> items)</span>
                                <span class="fw-medium" id="subtotal-amount">₹<?php echo number_format($subtotal, 2, '.', ','); ?></span>
                            </div>
                            <?php if ($promo_discount > 0): ?>
                            <div class="d-flex justify-content-between align-items-center mb-2 text-success">
                                <span>Promo Discount (<?php echo htmlspecialchars($promo_info['discount_display'] ?? ''); ?>)</span>
                                <span id="promo-discount">-₹<?php echo number_format($promo_discount, 2); ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">Delivery Fee</span>
                                <span id="delivery-fee">₹<?php echo number_format($delivery_fee, 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">Taxes & Charges</span>
                                <span>₹0.00</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                                <div>
                                    <h6 class="mb-0">Total Amount</h6>
                                    <?php if ($promo_discount > 0): ?>
                                    <small class="text-success">You saved ₹<span id="savings-amount"><?php echo number_format($promo_discount, 2); ?></span>!</small>
                                    <?php endif; ?>
                                </div>
                                <h5 class="mb-0" id="total-amount">₹<?php echo number_format($total, 2); ?></h5>
                            </div>
                            
                            <!-- Promo Code Form -->
                            <div class="mt-3">
                                <form method="POST" class="input-group">
                                    <input type="text" name="promo_code" class="form-control" placeholder="Promo code" value="<?php echo htmlspecialchars($promo_code); ?>" <?php echo $promo_discount > 0 ? 'disabled' : ''; ?>>
                                    <?php if ($promo_discount > 0): ?>
                                        <button type="submit" name="remove_promo" class="btn btn-outline-danger">Remove</button>
                                    <?php else: ?>
                                        <button type="submit" name="apply_promo" class="btn btn-primary">Apply</button>
                                    <?php endif; ?>
                                </form>
                                <?php if (!empty($promo_message)): ?>
                                    <div class="mt-2 small <?php echo $promo_discount > 0 ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo htmlspecialchars($promo_message); ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                        </div>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted">Need help?</h6>
                        <p class="small text-muted mb-0">Contact our customer support for any questions about your order.</p>
                        <a href="contact.php" class="small">Contact Support</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Function to update the price display for a specific item
        function updateItemPrice(itemId) {
            const itemElement = $(`#item-${itemId}`);
            if (!itemElement.length) return;
            
            const input = $(`.quantity-input[data-item-id="${itemId}"]`);
            const quantity = parseInt(input.val()) || 0;
            const price = parseFloat(input.data('price')) || 0;
            const total = quantity * price;
            
            // Update the item's total price display
            itemElement.find('.item-total').text('₹' + total.toFixed(2));
            
            // Update the quantity display
            itemElement.find('.item-quantity').text(quantity + ' × ₹' + price.toFixed(2));
            
            return total;
        }
        
        // Function to update cart item quantity
        function updateCartItem(itemId, newQuantity) {
            if (newQuantity < 1) return;
            
            // Show loading state
            const input = $(`.quantity-input[data-item-id="${itemId}"]`);
            const buttons = $(`.quantity-btn[data-item-id="${itemId}"]`);
            input.prop('disabled', true);
            buttons.prop('disabled', true);
            
            // Store the current value before updating
            const lastValue = input.val();
            
            // Update the UI immediately for better UX
            input.val(newQuantity);
            updateItemPrice(itemId);
            updateOrderTotals();
            
            $.ajax({
                url: 'api/update-cart.php',
                method: 'POST',
                data: {
                    item_id: itemId,
                    quantity: newQuantity
                },
                dataType: 'json',
                success: function(response) {
                    // Re-enable inputs
                    input.prop('disabled', false);
                    buttons.prop('disabled', false);
                    
                    if (response.success) {
                        // Update the displayed quantity and total
                        input.val(newQuantity);
                        updateItemPrice(itemId);
                        updateOrderTotals();
                        showToast('Cart updated successfully', 'success');
                    } else {
                        // Revert to previous value on error
                        input.val(lastValue);
                        updateItemPrice(itemId);
                        updateOrderTotals();
                        showToast(response.message || 'Error updating cart', 'danger');
                    }
                },
                error: function() {
                    // Re-enable inputs and revert value on error
                    input.prop('disabled', false);
                    buttons.prop('disabled', false);
                    input.val(lastValue);
                    updateItemPrice(itemId);
                    updateOrderTotals();
                    showToast('Error updating cart. Please try again.', 'danger');
                }
            });
        }
        
        function removeCartItem(itemId) {
            if (!confirm('Are you sure you want to remove this item from your cart?')) {
                return;
            }
            
            // Show loading state
            const removeBtn = $(`button.remove-item[data-item-id="${itemId}"]`);
            removeBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
            
            $.ajax({
                url: 'api/remove-from-cart.php',
                method: 'POST',
                data: { item_id: itemId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Remove the item from the UI
                        $(`#item-${itemId}`).fadeOut(300, function() {
                            $(this).remove();
                            updateOrderTotals();
                            
                            // If no more items, redirect to cart
                            if ($('.remove-item').length === 0) {
                                window.location.href = 'cart.php';
                            }
                        });
                        
                        showToast('Item removed from cart', 'success');
                    } else {
                        removeBtn.prop('disabled', false).html('<i class="bi bi-trash"></i>');
                        showToast(response.message || 'Error removing item', 'danger');
                    }
                },
                error: function() {
                    removeBtn.prop('disabled', false).html('<i class="bi bi-trash"></i>');
                    showToast('Error removing item. Please try again.', 'danger');
                }
            });
        }
        
        // Function to update order totals
        function updateOrderTotals() {
            let subtotal = 0;
            let itemCount = 0;
            
            // Calculate new subtotal and count items
            $('.quantity-input').each(function() {
                const quantity = parseInt($(this).val()) || 0;
                const price = parseFloat($(this).data('price')) || 0;
                subtotal += quantity * price;
                itemCount += quantity;
            });
            
            // Round to 2 decimal places to avoid floating point errors
            subtotal = Math.round(subtotal * 100) / 100;
            
            // Get promo discount if any
            const promoDiscount = 0; // Promo code total is not shown in the UI
            const deliveryFee = parseFloat($('#delivery-fee').text().replace(/[^0-9.]+/g,"")) || 0;
            
            // Calculate total
            let total = subtotal + deliveryFee - Math.abs(promoDiscount);
            total = Math.max(0, total); // Ensure total is not negative
            
            // Update the displayed values in the right column
            $('#item-count').text(itemCount + (itemCount === 1 ? ' item' : ' items'));
            $('#subtotal-amount, #left-subtotal-amount').text('₹' + subtotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            
            // Update total amount in both locations
            $('#total-amount, #left-total-amount').text('₹' + total.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            
            // Update other elements in the left column
            if (promoDiscount > 0) {
                $('#left-promo-discount').text('-₹' + promoDiscount.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            }
            $('#left-delivery-fee').text('₹' + deliveryFee.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            
            // Update the form's hidden total field if it exists
            $('input[name="total_amount"]').val(total);
            
            // Update the place order button text
            $('button[type="submit"]').html('<span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span> Place Order');
            
            return {
                subtotal: subtotal,
                total: total,
                itemCount: itemCount
            };
        }
        
        // Show toast message
        function showToast(message, type = 'info') {
            const toast = $(`
                <div class="toast align-items-center text-white bg-${type} border-0 position-fixed bottom-0 end-0 m-3" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>
            `);
            
            $('body').append(toast);
            const bsToast = new bootstrap.Toast(toast[0]);
            bsToast.show();
            
            // Remove toast after it's hidden
            toast.on('hidden.bs.toast', function() {
                $(this).remove();
            });
            
            // Auto-hide after 3 seconds
            setTimeout(() => {
                bsToast.hide();
            }, 3000);
        }
        
        // Event handlers
        $(document).ready(function() {
            // Initialize item prices and quantities
            $('.quantity-input').each(function() {
                const itemId = $(this).data('item-id');
                $(this).data('last-value', $(this).val());
                updateItemPrice(itemId);
            });
            
            // Initialize order totals
            updateOrderTotals();
            
            // Quantity button click
            $(document).on('click', '.quantity-btn', function() {
                const action = $(this).data('action');
                const input = $(this).siblings('.quantity-input');
                let quantity = parseInt(input.val());
                
                if (isNaN(quantity)) {
                    quantity = 1;
                }
                
                if (action === 'increase') {
                    quantity++;
                } else if (action === 'decrease') {
                    quantity = Math.max(1, quantity - 1);
                }
                
                // Store the current value before updating
                input.data('last-value', quantity);
                input.val(quantity);
                updateCartItem(input.data('item-id'), quantity);
            });
            
            // Manual quantity input change
            $(document).on('change', '.quantity-input', function() {
                const quantity = Math.max(1, parseInt($(this).val()) || 1);
                $(this).data('last-value', quantity);
                $(this).val(quantity);
                updateCartItem($(this).data('item-id'), quantity);
            });
            
            // Remove item button click
            $(document).on('click', '.remove-item', function(e) {
                e.preventDefault();
                const itemId = $(this).data('item-id');
                removeCartItem(itemId);
            });
            
            // Prevent form submission on enter key in quantity input
            $(document).on('keydown', '.quantity-input', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    $(this).trigger('change');
                }
            });
        });
    </script>
    <style>
        .quantity-input {
            max-width: 50px;
            text-align: center;
        }
        .quantity-btn {
            width: 30px;
            padding: 0.25rem 0.5rem;
        }
        .toast {
            z-index: 1090;
        }
    </style>
        // Auto-scroll to any error messages
        document.addEventListener('DOMContentLoaded', function() {
            const errorAlert = document.querySelector('.alert-danger');
            if (errorAlert) {
                errorAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    </script>
</body>
</html>

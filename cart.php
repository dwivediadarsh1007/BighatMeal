<?php
// Initialize variables to avoid undefined variable errors
$display_items = [];
$subtotal = 0;
$delivery_fee = 0; // Delivery fee set to 0 rupees
$total = 0;
$error_message = '';
$has_error = false;

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    // Helper function to get price from items JSON
function getPriceFromItems($itemsJson) {
    if (empty($itemsJson)) return 0;
    
    $items = json_decode($itemsJson, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('Error decoding items JSON: ' . json_last_error_msg());
        return 0;
    }
    
    $total = 0;
    if (is_array($items)) {
        foreach ($items as $item) {
            if (isset($item['price'])) {
                $total += (float)$item['price'] * (isset($item['quantity']) ? (int)$item['quantity'] : 1);
            }
        }
    }
    
    return $total;
}

// Include config file
    if (!@include_once 'config.php') {
        throw new Exception('Failed to load configuration');
    }

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }

    // Verify database connection
    if (!isset($conn) || !($conn instanceof PDO)) {
        throw new Exception('Database connection failed');
    }

    $user_id = $_SESSION['user_id'];
    
    // Get all cart items for the user in a single section
    $query = "
        SELECT 
            c.id, 
            c.user_id, 
            c.meal_type,
            c.items,
            c.total_calories as calories,
            c.total_protein as protein,
            c.total_carbs as carbs,
            c.total_fat as fat,
            c.total_fiber as fiber,
            c.total_price as price,
            c.total_price as total_price,
            c.created_at, 
            c.updated_at
        FROM cart c
        WHERE c.user_id = ?
        ORDER BY c.created_at ASC
    ";
    

    
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        $error = $conn->errorInfo();
        error_log("Prepare failed: " . print_r($error, true));
        throw new Exception('Failed to prepare statement: ' . implode(' ', $error));
    }
    
    $executed = $stmt->execute([$user_id]);
    
    if (!$executed) {
        $error = $stmt->errorInfo();
        error_log("Execute failed: " . print_r($error, true));
        throw new Exception('Failed to execute query: ' . implode(' ', $error));
    }
    
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    
    if (empty($cart_items)) {
        // Check if there are any items in the cart table for this user
        $check = $conn->prepare("SELECT * FROM cart WHERE user_id = ?");
        $check->execute([$user_id]);
        $user_cart_items = $check->fetchAll(PDO::FETCH_ASSOC);
        

        
        // Log product IDs that should be in the cart
        $product_ids = array_column($user_cart_items, 'product_id');
        if (!empty($product_ids)) {
            $placeholders = rtrim(str_repeat('?,', count($product_ids)), ',');
            $product_check = $conn->prepare("SELECT id, name FROM products WHERE id IN ($placeholders)");
            $product_check->execute($product_ids);
            $existing_products = $product_check->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("Products that should be in cart: " . print_r($existing_products, true));
            
            // If no products found, we'll create display items directly from cart
            if (empty($existing_products)) {
                error_log("No products found in database, creating basic display items");
                foreach ($user_cart_items as $cart_item) {
                    $cart_items[] = [
                        'id' => $cart_item['id'],
                        'user_id' => $cart_item['user_id'],
                        'product_id' => $cart_item['product_id'],
                        'quantity' => $cart_item['quantity'],
                        'name' => 'Product #' . $cart_item['product_id'],
                        'price' => 0,
                        'image' => 'default-food.jpg',
                        'calories' => 0,
                        'protein' => 0,
                        'carbs' => 0,
                        'fat' => 0,
                        'fiber' => 0,
                        'created_at' => $cart_item['created_at'],
                        'updated_at' => $cart_item['updated_at']
                    ];
                }
            }
        }
    }

    // Process cart items - all in one section
    $display_items = [];
    $subtotal = 0;
    $delivery_fee = 0; // Delivery fee set to 0 rupees
    $processed_items = []; // Track processed items to avoid duplicates
    
    // Debug logging
    error_log('Raw cart items: ' . print_r($cart_items, true));
    
    error_log("Processing " . count($cart_items) . " cart items");
    
    if (empty($cart_items)) {
        error_log("No items found in cart");
    } else {
        // First, try to get product prices
        $product_ids = [];
        foreach ($cart_items as $item) {
            if (!empty($item['product_id'])) {
                $product_ids[] = $item['product_id'];
            }
        }
        
        $product_prices = [];
        if (!empty($product_ids)) {
            $placeholders = rtrim(str_repeat('?,', count($product_ids)), ',');
            $stmt = $conn->prepare("SELECT id, price FROM products WHERE id IN ($placeholders)");
            $stmt->execute($product_ids);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $product_prices[$row['id']] = $row['price'];
            }
        }
        foreach ($cart_items as $item) {
            // Skip if we've already processed this item
            if (in_array($item['id'], $processed_items)) {
                continue;
            }
            
            // Mark this item as processed
            $processed_items[] = $item['id'];
            
            // Get the price based on availability
            $price = 0;
            
            // 1. First priority: Use the item's price if set
            if (!empty($item['price'])) {
                $price = (float)$item['price'];
            } 
            // 2. Second priority: Check product_prices array
            elseif (!empty($item['product_id']) && !empty($product_prices[$item['product_id']])) {
                $price = (float)$product_prices[$item['product_id']];
            }
            // 3. Third priority: Try to get from items JSON
            elseif (!empty($item['items'])) {
                $price = getPriceFromItems($item['items']);
            }
            
            // Ensure we have a valid quantity
            $quantity = isset($item['quantity']) ? max(1, (int)$item['quantity']) : 1;
            
            // Create display item
            $display_item = [
                'id' => $item['id'],
                'user_id' => $item['user_id'] ?? 0,
                'product_id' => $item['product_id'] ?? 0,
                'name' => $item['name'] ?? 'Unnamed Item',
                'price' => $price,
                'quantity' => $quantity,
                'image' => 'default-food.jpg',
                'calories' => $item['calories'] ?? 0,
                'protein' => $item['protein'] ?? 0,
                'carbs' => $item['carbs'] ?? 0,
                'fat' => $item['fat'] ?? 0,
                'fiber' => $item['fiber'] ?? 0,
                'total_price' => $price * $quantity
            ];
            
            // Handle items stored in JSON format
            if (!empty($item['items'])) {
                $items_data = json_decode($item['items'], true);
                
                if (json_last_error() === JSON_ERROR_NONE && is_array($items_data) && !empty($items_data)) {
                    // Process all items in the JSON data
                    foreach ($items_data as $cart_item) {
                        $display_item_copy = $display_item; // Create a copy for each item
                        
                        $display_item_copy['name'] = $cart_item['name'] ?? $display_item_copy['name'];
                        $display_item_copy['price'] = $cart_item['price'] ?? $display_item_copy['price'];
                        $display_item_copy['quantity'] = $cart_item['quantity'] ?? $display_item_copy['quantity'];
                        $display_item_copy['image'] = $cart_item['image_url'] ?? $display_item_copy['image'];
                        $display_item_copy['calories'] = $cart_item['calories'] ?? $display_item_copy['calories'];
                        $display_item_copy['protein'] = $cart_item['protein'] ?? $display_item_copy['protein'];
                        $display_item_copy['carbs'] = $cart_item['carbs'] ?? $display_item_copy['carbs'];
                        $display_item_copy['fat'] = $cart_item['fat'] ?? $display_item_copy['fat'];
                        $display_item_copy['fiber'] = $cart_item['fiber'] ?? $display_item_copy['fiber'];
                        
                        // Add custom meal details if available
                        if (isset($cart_item['is_custom']) && $cart_item['is_custom']) {
                            $display_item_copy['is_custom'] = true;
                            $display_item_copy['custom_details'] = [
                                'name' => $cart_item['name'] ?? 'Custom Meal',
                                'ingredients' => $cart_item['ingredients'] ?? [],
                                'nutrition' => [
                                    'calories' => $cart_item['calories'] ?? 0,
                                    'protein' => $cart_item['protein'] ?? 0,
                                    'carbs' => $cart_item['carbs'] ?? 0,
                                    'fat' => $cart_item['fat'] ?? 0,
                                    'fiber' => $cart_item['fiber'] ?? 0
                                ]
                            ];
                        }
                        
                        // Calculate total price for this item
                        $display_item_copy['total_price'] = round($display_item_copy['price'] * $display_item_copy['quantity'], 2);
                        $subtotal += $display_item_copy['total_price'];
                        
                        // Add this item to display items
                        $display_items[] = $display_item_copy;
                    }
                    continue; // Skip adding the main item since we've processed its components
                }
            }
            
            // Calculate total price for non-JSON items
            $display_item['total_price'] = round($display_item['price'] * $display_item['quantity'], 2);
            $subtotal += $display_item['total_price'];
            
            // Handle image paths
            if (!empty($item['image'])) {
                $image_path = 'admin/uploads/' . $item['image'];
                if (file_exists($image_path)) {
                    $display_item['image'] = $image_path;
                }
            } elseif (!empty($item['image_url'])) {
                $display_item['image'] = $item['image_url'];
            }
            
            // Add the display item to our list
            $display_items[] = $display_item;
            error_log("Added to display items: " . print_r($display_item, true));
        }
    }
    
    // Calculate subtotal from all display items
    $subtotal = array_reduce($display_items, function($sum, $item) {
        return $sum + ($item['total_price'] ?? 0);
    }, 0);
    
    // Calculate total
    $total = $subtotal + $delivery_fee;
    
    // Debug log the final amounts
    error_log(sprintf('Cart totals - Subtotal: ₹%.2f, Delivery: ₹%.2f, Total: ₹%.2f', 
        $subtotal, $delivery_fee, $total));
    
} catch (Exception $e) {
    error_log('Cart Error: ' . $e->getMessage());
    // Set error state
    if (!isset($has_error)) {
        $has_error = true;
        $error_message = 'An error occurred while loading your cart. Please try again later.';
        if (ini_get('display_errors')) {
            $error_message .= '\nError: ' . htmlspecialchars($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - BighatMeal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container mt-4">
        <?php if ($has_error): ?>
            <div class="alert alert-danger">
                <h4>An error occurred</h4>
                <p><?php echo nl2br(htmlspecialchars($error_message)); ?></p>
            </div>
        <?php endif; ?>
        
        <h2 class="mb-4">Shopping Cart</h2>
        
        <?php if (empty($display_items)): ?>
            <div class="alert alert-info text-center">
                <h4><i class="bi bi-cart-x"></i> Your cart is empty</h4>
                <p class="mt-3">Looks like you haven't added any items to your cart yet.</p>
                <a href="index.php" class="btn btn-primary mt-2">
                    <i class="bi bi-arrow-left"></i> Continue shopping
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 50%;">Product</th>
                                            <th class="text-center">Price</th>
                                            <th class="text-center">Quantity</th>
                                            <th class="text-end">Total</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($display_items as $item): ?>
                                            <tr data-item-id="<?php echo $item['id']; ?>">
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="img-fluid rounded" style="width: 60px; height: 60px; object-fit: cover;">
                                                        <div class="ms-3">
                                                            <h6 class="mb-0"><?php echo htmlspecialchars($item['name']); ?></h6>
                                                            <small class="text-muted">
                                                                <?php 
                                                                $details = [];
                                                                if ($item['calories'] > 0) $details[] = $item['calories'] . ' cal';
                                                                if ($item['protein'] > 0) $details[] = $item['protein'] . 'g protein';
                                                                if ($item['carbs'] > 0) $details[] = $item['carbs'] . 'g carbs';
                                                                if ($item['fat'] > 0) $details[] = $item['fat'] . 'g fat';
                                                                if ($item['fiber'] > 0) $details[] = $item['fiber'] . 'g fiber';
                                                                echo implode(' • ', $details);
                                                                ?>
                                                            </small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-center align-middle">
                                                    <?php 
                                                    // Ensure price is properly formatted
                                                    $price = is_numeric($item['price']) ? (float)$item['price'] : 0;
                                                    ?>
                                                    <span class="item-price" data-price="<?php echo number_format($price, 2, '.', ''); ?>">        
                                                        ₹<?php echo number_format($price, 2); ?>
                                                    </span>
                                                </td>
                                                <td class="text-center align-middle">
                                                    <div class="input-group input-group-sm" style="width: 100px;">
                                                        <button type="button" class="btn btn-outline-secondary btn-sm quantity-decrease">-</button>
                                                        <input type="number" class="form-control text-center quantity-input" 
                                                               value="<?php echo $item['quantity']; ?>" 
                                                               min="1" 
                                                               data-item-id="<?php echo $item['id']; ?>">
                                                        <button type="button" class="btn btn-outline-secondary btn-sm quantity-increase">+</button>
                                                    </div>
                                                </td>
                                                <td class="text-center align-middle item-total" data-amount="<?php echo $item['total_price']; ?>">
                                                    ₹<?php echo number_format($item['total_price'], 2); ?>
                                                </td>
                                                <td class="text-center align-middle">
                                                    <button type="button" class="btn btn-link text-danger remove-item" data-item-id="<?php echo $item['id']; ?>">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 mt-4 mt-lg-0">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-4">Order Summary</h5>
                            
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal:</span>
                                <span id="subtotal">₹<?php echo number_format($subtotal, 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Delivery Fee:</span>
                                <span id="delivery-fee">₹<?php echo number_format($delivery_fee, 2); ?></span>
                            </div>
                            
                            <hr>
                            
                            <div class="d-flex justify-content-between mb-4">
                                <h5>Total:</h5>
                                <h5 id="total">₹<?php echo number_format($total, 2); ?></h5>
                            </div>
                            
                            <a href="checkout.php" class="btn btn-primary w-100 py-2">
                                Proceed to Checkout
                            </a>
                            
                            <div class="text-center mt-3">
                                <a href="index.php" class="text-muted">
                                    <i class="bi bi-arrow-left"></i> Continue Shopping
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <!-- Load jQuery first, then Bootstrap -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // Format currency on page load
        function formatCurrency(amount) {
            return '₹' + parseFloat(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }
        
        // Update price display
        $('.item-price').each(function() {
            const price = parseFloat($(this).data('price'));
            if (!isNaN(price)) {
                $(this).html(formatCurrency(price));
            }
        });
        
        // Update totals display
        function updateTotals() {
            let subtotal = 0;
            
            // Calculate subtotal from all items
            $('tr[data-item-id]').each(function() {
                const $row = $(this);
                const price = parseFloat($row.find('.item-price').data('price'));
                const quantity = parseInt($row.find('.quantity-input').val()) || 0;
                const itemTotal = price * quantity;
                
                // Update item total
                $row.find('.item-total').data('amount', itemTotal).html(formatCurrency(itemTotal));
                
                // Add to subtotal
                subtotal += itemTotal;
            });
            
            // Update subtotal
            $('#subtotal').html(formatCurrency(subtotal));
            
            // Calculate total (subtotal + delivery fee)
            const deliveryFee = parseFloat($('#delivery-fee').text().replace('₹', '').replace(/,/g, '')) || 0;
            const total = subtotal + deliveryFee;
            
            // Update total
            $('#total').html(formatCurrency(total));
        }
        
        // Initial update
        updateTotals();
        
        // Update cart total on page load
        updateCartTotal();

        // Handle quantity changes
        $(document).on('change', '.quantity-input', function() {
            const input = $(this);
            let quantity = parseInt(input.val()) || 1;
            
            // Ensure quantity is at least 1
            if (quantity < 1) {
                quantity = 1;
                input.val(1);
            }
            
            // Update the cart via AJAX
        });

        $('body').on('click', '.quantity-decrease', function() {
            const $input = $(this).siblings('.quantity-input');
            const newVal = parseInt($input.val()) - 1;
            $input.val(newVal > 0 ? newVal : 1).trigger('change');
        });

        // Handle quantity increase
        $('body').on('click', '.quantity-increase', function() {
            const $input = $(this).siblings('.quantity-input');
            const newVal = parseInt($input.val()) + 1;
            $input.val(newVal).trigger('change');
        });

        $('body').on('change', '.quantity-input', function() {
            const $input = $(this);
            const newVal = parseInt($input.val());
            
            // Ensure valid quantity
            if (isNaN(newVal) || newVal < 1) {
                $input.val(1);
                return;
            }
            
            updateItemQuantity(this);
        });

        // Handle remove item
        $(document).on('click', '.remove-item', function(e) {
            e.preventDefault();
            
            const button = $(this);
            const itemId = button.data('item-id'); // Changed from data('id') to data('item-id')
            const row = button.closest('tr');
            
            if (confirm('Are you sure you want to remove this item from your cart?')) {
                // Show loading state
                const originalHtml = button.html();
                button.prop('disabled', true).html('<i class="bi bi-arrow-repeat fa-spin"></i>');
                
                // Remove from database via AJAX
                $.ajax({
                    url: 'api/remove-from-cart.php',
                    type: 'POST',
                    data: { 
                        item_id: itemId  // Changed from 'id' to 'item_id' to match the API expectation
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Remove the row from the table with animation
                            row.fadeOut(300, function() {
                                $(this).remove();
                                updateCartTotal();
                                
                                // If cart is empty, show empty cart message
                                if ($('tbody tr[data-item-id]').length === 0) {
                                    $('tbody').html('<tr><td colspan="5" class="text-center py-5"><div class="alert alert-info">Your cart is empty</div></td></tr>');
                                    $('.cart-summary').hide();
                                }
                            });
                            
                            // Show success message
                            showAlert('Item removed from cart', 'success');
                            
                            // Update cart count in header
                            updateCartCount();
                        } else {
                            showAlert(response.message || 'Failed to remove item', 'error');
                            button.prop('disabled', false).html(originalHtml);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error removing item:', status, error);
                        showAlert('An error occurred while removing the item: ' + (xhr.responseJSON?.message || error), 'error');
                        button.prop('disabled', false).html(originalHtml);
                    }
                });
            }
        });
        
        // Function to update item quantity
        function updateItemQuantity(input) {
            const $input = $(input);
            const $row = $input.closest('tr');
            const itemId = $row.data('item-id');
            const quantity = parseInt($input.val()) || 1;
            
            // Update the UI immediately for better UX
            updateTotals();
            
            // Update the cart via AJAX
            updateCartItem(itemId, quantity);
        }
        
        // Function to update cart total
        function updateCartTotal() {
            let subtotal = 0;
            
            // Calculate subtotal from item prices and quantities
            $('tr[data-item-id]').each(function() {
                const $row = $(this);
                const price = parseFloat($row.find('.item-price').data('price')) || 0;
                const quantity = parseInt($row.find('.quantity-input').val()) || 1;
                const itemTotal = Math.round((price * quantity) * 100) / 100; // Round to 2 decimal places
                
                // Update item total display
                $row.find('.item-total').text('₹' + itemTotal.toFixed(2))
                    .data('total', itemTotal);
                
                subtotal = Math.round((subtotal + itemTotal) * 100) / 100;
            });
            
            // Set delivery fee to 0
            const deliveryFee = 0;
            
            // Calculate total with proper rounding
            const total = Math.round((subtotal + deliveryFee) * 100) / 100;
            
            // Update the display with proper formatting
            $('#subtotal').text('₹' + subtotal.toFixed(2))
                .data('amount', subtotal);
            $('#delivery-fee').text('₹' + deliveryFee.toFixed(2))
                .data('amount', deliveryFee);
            $('#total').text('₹' + total.toFixed(2))
                .data('amount', total);
            
            // Format all currency values
            formatCurrencyValues();
        }
        
        // Function to format currency values with proper formatting
        function formatCurrencyValues() {
            $('.item-price, .item-total, #subtotal, #delivery-fee, #total').each(function() {
                const $el = $(this);
                let num = $el.data('amount') || parseFloat($el.text().replace(/[^0-9.-]+/g,""));
                
                if (!isNaN(num)) {
                    $el.text('₹' + num.toLocaleString('en-IN', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }));
                }
            });
        }
        
        // Function to update cart item via AJAX
        function updateCartItem(id, quantity) {
            const $row = $(`tr[data-item-id="${id}"]`);
            const $input = $row.find('.quantity-input');
            const originalQuantity = parseInt($input.data('original-quantity')) || 1;
            
            // Show loading state
            $input.prop('disabled', true);
            
            $.ajax({
                url: 'api/update-cart-item.php',
                type: 'POST',
                data: {
                    item_id: id,
                    quantity: quantity
                },
                dataType: 'json',
                success: function(response) {
                    $input.prop('disabled', false);
                    
                    if (response.success) {
                        // Update the original quantity
                        $input.data('original-quantity', quantity);
                        
                        // If we have updated item data, use it
                        if (response.item) {
                            const item = response.item;
                            const price = parseFloat(item.price || $row.find('.item-price').data('price'));
                            const quantity = parseInt(item.quantity || quantity);
                            const total = price * quantity;
                            
                            // Update the display
                            $row.find('.item-price')
                                .data('price', price)
                                .html(formatCurrency(price));
                                
                            $row.find('.item-total')
                                .data('amount', total)
                                .html(formatCurrency(total));
                        }
                        
                        // Update all totals
                        updateTotals();
                        
                        // Show success message
                        showAlert('Cart updated successfully', 'success');
                    } else {
                        // Revert to original quantity on error
                        $input.val(originalQuantity);
                        showAlert(response.message || 'Failed to update cart', 'error');
                    }
                },
                error: function(xhr) {
                    $input.prop('disabled', false);
                    $input.val(originalQuantity);
                    
                    let errorMsg = 'An error occurred while updating your cart';
                    try {
                        const errorResponse = JSON.parse(xhr.responseText);
                        if (errorResponse && errorResponse.message) {
                            errorMsg = errorResponse.message;
                        }
                    } catch (e) {
                        console.error('Error parsing error response:', e);
                    }
                    
                    showAlert(errorMsg, 'error');
                }
            });
        }
        
        // Function to update cart count in header
        function updateCartCount() {
            $.get('api/get-cart-count.php', function(response) {
                if (response.success) {
                    $('.cart-count').text(response.count);
                }
            }, 'json');
        }
        
        // Function to show alert messages
        function showAlert(message, type = 'success') {
            // Remove any existing alerts
            $('.alert-dismissible').remove();
            
            // Create alert element
            const alertClass = type === 'error' ? 'alert-danger' : 'alert-success';
            const alertHtml = `
                <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            
            // Add alert to the page
            $('.container.mt-4').prepend(alertHtml);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                $('.alert-dismissible').fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        }
    });
    </script>
</body>
</html>

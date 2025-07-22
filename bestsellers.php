<?php
session_start();
require_once 'config.php';

// Get top selling items
$query = "SELECT p.*, COUNT(oi.product_id) as order_count 
          FROM products p 
          JOIN order_items oi ON p.id = oi.product_id 
          GROUP BY p.id 
          ORDER BY order_count DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$bestsellers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Bestsellers - BighatMeal</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Additional styles for bestsellers page */
        .bestseller-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: linear-gradient(45deg, #ff9a9e 0%, #fad0c4 99%, #fad0c4 100%);
            color: white;
            padding: 5px 15px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(255, 154, 158, 0.3);
            z-index: 2;
        }
        
        .product-card {
            transition: all 0.3s ease;
            border: 1px solid #eee;
            border-radius: 10px;
            overflow: hidden;
            height: 100%;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .product-img-container {
            height: 200px;
            overflow: hidden;
        }
        
        .product-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .product-card:hover .product-img {
            transform: scale(1.05);
        }
        
        .product-price {
            color: #fd7e14;
            font-weight: 700;
            font-size: 1.25rem;
        }
        
        .rating {
            color: #ffc107;
            margin-bottom: 10px;
        }
        
        .hero-section {
            background: linear-gradient(135deg, rgba(253, 126, 20, 0.1) 0%, rgba(255, 193, 7, 0.1) 100%);
            padding: 80px 0;
            margin-bottom: 50px;
        }
    </style>
</head>
<body>
<?php include 'includes/header.php'; ?>

<!-- Hero Section -->
<section class="bg-light py-5">
    <div class="container text-center py-4">
        <div class="hero-content">
            <h1 class="display-4 fw-bold mb-3 text-dark">Our Bestselling Dishes</h1>
            <p class="lead text-muted mb-4">Customer favorites that keep them coming back for more</p>
            <nav aria-label="breadcrumb" class="d-inline-block">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Bestsellers</li>
                </ol>
            </nav>
        </div>
    </div>
</section>

<!-- Bestsellers Grid -->
<section class="py-5 position-relative">
    <div class="container position-relative">
        <div class="text-center mb-5">
            <h2 class="mb-3">Customer Favorites</h2>
            <p class="lead text-muted">Discover our most loved dishes</p>
        </div>
    <div class="container">
        <div class="row g-4">
            <?php if (count($bestsellers) > 0): ?>
                <?php foreach ($bestsellers as $item): ?>
                    <div class="col-md-6 col-lg-4 col-xl-3 mb-4">
                        <div class="card product-card h-100">
                            <div class="position-relative">
                                <img src="<?php echo htmlspecialchars($item['image_url']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                <span class="bestseller-badge">Bestseller</span>
                            </div>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo htmlspecialchars($item['name']); ?></h5>
                                <p class="card-text text-muted flex-grow-1"><?php echo substr(htmlspecialchars($item['description']), 0, 100); ?>...</p>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="product-price">₹<?php echo number_format($item['price'], 2); ?></span>
                                    <span class="text-muted"><?php echo $item['order_count']; ?> sold</span>
                                </div>
                                <div class="rating-stars mb-2">
                                    <?php 
                                    $rating = rand(4, 5);
                                    for ($i = 0; $i < 5; $i++) {
                                        echo $i < $rating ? '★' : '☆';
                                    }
                                    ?>
                                    <span class="rating-count ms-2">(<?php echo rand(50, 200); ?>)</span>
                                </div>
                                
                                <?php if ($item['is_available'] == 1): ?>
                                    <button 
                                        class="btn btn-primary w-100 add-to-cart-btn" 
                                        data-product-id="<?php echo $item['id']; ?>"
                                        data-product-name="<?php echo htmlspecialchars($item['name']); ?>"
                                        data-product-price="<?php echo htmlspecialchars($item['price']); ?>"
                                        <?php echo !isset($_SESSION['user_id']) ? 'data-bs-toggle="modal" data-bs-target="#loginModal"' : ''; ?>>
                                        <i class="bi bi-cart-plus me-2"></i>Add to Cart
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-outline-secondary w-100" disabled>Out of Stock</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <div class="alert alert-info d-inline-block">
                        <i class="bi bi-info-circle me-2"></i> No bestsellers found. Check back soon!
                    </div>
                    <div class="mt-3">
                        <a href="menu.php" class="btn btn-primary">
                            <i class="bi bi-arrow-left me-2"></i> Back to Menu
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="py-5 bg-light-cta">
    <div class="container text-center py-4">
        <div class="cta-content">
            <h2 class="mb-3">Can't Find What You're Looking For?</h2>
            <p class="lead text-muted mb-4">Explore our full menu for more delicious options</p>
            <a href="menu.php" class="btn btn-primary btn-lg px-5 py-3">
                <i class="bi bi-utensils me-2"></i> View Full Menu
            </a>
        </div>
    </div>
</section>

    <!-- Login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="loginModalLabel">Login Required</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Please login to add items to your cart.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn btn-primary">Go to Login</a>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 for beautiful alerts -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Check for success message to show after page reload
        document.addEventListener('DOMContentLoaded', function() {
            // Show success message if it was set before page reload
            if (sessionStorage.getItem('showCartSuccess') === 'true') {
                const productName = sessionStorage.getItem('productName') || 'Item';
                
                // Show success message
                Swal.fire({
                    icon: 'success',
                    title: 'Added to Cart!',
                    text: `${productName} has been added to your cart`,
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true
                });
                
                // Clear the flag
                sessionStorage.removeItem('showCartSuccess');
                sessionStorage.removeItem('productName');
            }
            
            // Rest of the code
            // Add to cart functionality
            document.querySelectorAll('.add-to-cart-btn').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const productId = this.getAttribute('data-product-id');
                    const productName = this.getAttribute('data-product-name');
                    const button = this;
                    
                    // Show loading state
                    const originalText = button.innerHTML;
                    button.disabled = true;
                    button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Adding...';
                    
                    // Log the request data
                    console.log('Preparing to add to cart:', {
                        productId: productId,
                        productName: productName,
                        price: button.getAttribute('data-product-price')
                    });
                    
                    // Log the request
                    console.log('Sending add to cart request for product:', productId);
                    
                    // First, check if user is logged in
                    fetch('check-auth.php')
                    .then(response => response.json())
                    .then(authData => {
                        if (!authData.loggedIn) {
                            throw { status: 401, message: 'Please login to add items to cart' };
                        }
                        
                        // If logged in, proceed with add to cart using the new API
                        return fetch('api/add-to-cart.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                type: 'standard',
                                items: [{
                                    id: productId,
                                    name: productName,
                                    quantity: 1,
                                    price: button.getAttribute('data-product-price') || 0
                                }]
                            }),
                            credentials: 'same-origin' // Include cookies for session
                        });
                    })
                    .then(async response => {
                        const contentType = response.headers.get('content-type');
                        let data;
                        
                        try {
                            if (contentType && contentType.includes('application/json')) {
                                data = await response.json();
                            } else {
                                const text = await response.text();
                                console.error('Non-JSON response:', text);
                                throw new Error('Invalid response from server');
                            }
                            
                            if (!response.ok) {
                                throw new Error(data.message || `HTTP error! status: ${response.status}`);
                            }
                            
                            return data;
                        } catch (error) {
                            console.error('Error parsing response:', error);
                            throw error;
                        }
                    })
                    .then(data => {
                        // Check if data is valid
                        if (!data || typeof data !== 'object') {
                            console.error('Invalid response data:', data);
                            throw new Error('Invalid response from server');
                        }
                        
                        // Check for success in different response formats
                        if (data.success === true || data.status === 'success') {
                            // Update cart count in header
                            const cartCount = document.querySelector('.cart-count');
                            let newCount = 0;
                            
                            // Handle different response formats
                            if (data.cart_count !== undefined) {
                                newCount = data.cart_count;
                            } else if (data.debug && data.debug.item_count !== undefined) {
                                newCount = data.debug.item_count;
                            } else {
                                // If we can't get the count, just increment the current count
                                newCount = cartCount ? parseInt(cartCount.textContent || '0') + 1 : 1;
                            }
                            
                            // Store success message in sessionStorage to show after page reload
                            sessionStorage.setItem('showCartSuccess', 'true');
                            sessionStorage.setItem('productName', productName);
                            
                            // Update cart count in header
                            if (cartCount) {
                                cartCount.textContent = newCount;
                                cartCount.classList.remove('d-none');
                            }
                            
                            // Reload the page to update the cart display
                            window.location.href = window.location.href;
                            return; // Exit the promise chain
                        } else {
                            // If we get here, the API returned an error
                            const errorMsg = data.message || data.error || 'Failed to add to cart';
                            
                            // If the item was actually added but the response format is unexpected
                            if (errorMsg.toLowerCase().includes('success')) {
                                // Still treat as success but log the issue
                                console.warn('Unexpected success message format:', errorMsg);
                                
                                // Update cart count in header
                                const cartCount = document.querySelector('.cart-count');
                                if (cartCount) {
                                    const currentCount = parseInt(cartCount.textContent || '0');
                                    cartCount.textContent = currentCount + 1;
                                    cartCount.classList.remove('d-none');
                                }
                                
                                // Store success message in sessionStorage to show after page reload
                                sessionStorage.setItem('showCartSuccess', 'true');
                                sessionStorage.setItem('productName', productName);
                                
                                // Reload the page to update the cart display
                                window.location.href = window.location.href;
                                return;
                            }
                            
                            // If it's a real error, throw it
                            const error = new Error(errorMsg);
                            error.status = data.status || 500;
                            error.response = data;
                            throw error;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        let errorMessage = 'Failed to add item to cart. Please try again.';
                        let showLogin = false;
                        
                        // More detailed error messages
                        if (error.status === 401) {
                            errorMessage = 'Please login to add items to cart';
                            showLogin = true;
                        } else if (error.status === 404) {
                            errorMessage = 'Product not found or out of stock';
                        } else if (error.status === 500) {
                            errorMessage = 'Server error. Please try again later.';
                            console.error('Server error details:', error.response);
                        } else if (error.message) {
                            errorMessage = error.message;
                        }
                        
                        // Log detailed error information
                        console.error('Add to cart error:', {
                            message: error.message,
                            status: error.status,
                            response: error.response,
                            stack: error.stack
                        });
                        
                        // Show error to user
                        const swalOptions = {
                            icon: 'error',
                            title: 'Error',
                            text: errorMessage,
                            footer: '<a href="javascript:location.reload()">Refresh page and try again</a>'
                        };
                        
                        // Show login modal if unauthorized
                        if (showLogin) {
                            const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
                            loginModal.show();
                            
                            // Update the error message to be more specific
                            swalOptions.title = 'Login Required';
                            swalOptions.footer = 'Please login to add items to your cart';
                        }
                        
                        Swal.fire(swalOptions);
                    })
                    .finally(() => {
                        // Reset button state
                        button.disabled = false;
                        button.innerHTML = originalText;
                    });
                });
            });
            
            // Check if user is logged in and update cart count
            function updateCartCount() {
                fetch('api/get-cart-count.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            const cartCount = document.querySelector('.cart-count');
                            if (cartCount) {
                                cartCount.textContent = data.cart_count;
                                if (data.cart_count > 0) {
                                    cartCount.classList.remove('d-none');
                                }
                            }
                        }
                    })
                    .catch(error => console.error('Error fetching cart count:', error));
            }
            
            // Update cart count on page load if user is logged in
            <?php if (isset($_SESSION['user_id'])): ?>
            updateCartCount();
            <?php endif; ?>
        });
    </script>
</body>
</html>

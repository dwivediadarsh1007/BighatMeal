<?php
session_start();
require_once 'config.php';

// Get all categories
$stmt = $conn->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();

// Get products based on category filter
$category_id = isset($_GET['category']) ? $_GET['category'] : null;

if ($category_id) {
    $stmt = $conn->prepare("SELECT p.*, c.name as category_name 
                           FROM products p 
                           JOIN categories c ON p.category_id = c.id 
                           WHERE p.category_id = ? AND p.is_available = 1");
    $stmt->execute([$category_id]);
} else {
    $stmt = $conn->query("SELECT p.*, c.name as category_name 
                         FROM products p 
                         JOIN categories c ON p.category_id = c.id 
                         WHERE p.is_available = 1");
}
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu - BighatMeal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Category Filter - Moved to top -->
    <div class="bg-white border-bottom py-3 sticky-top" style="top: 72px; z-index: 1020;">
        <div class="container">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <span class="fw-bold text-muted d-none d-md-block">Filter by:</span>
                <div class="d-flex flex-wrap gap-2" id="category-filter">
                    <a href="menu.php" 
                       class="category-btn btn btn-sm btn-outline-success rounded-pill px-3 <?php echo empty($category_id) ? 'active' : ''; ?>"
                       data-category-id="">
                        <i class="bi bi-grid me-1"></i>All Items
                    </a>
                    <?php foreach ($categories as $cat): ?>
                        <a href="?category=<?php echo $cat['id']; ?>" 
                           class="category-btn btn btn-sm btn-outline-success rounded-pill px-3 <?php echo ($category_id == $cat['id']) ? 'active' : ''; ?>"
                           data-category-id="<?php echo $cat['id']; ?>">
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Page Header -->
    <div class="bg-light py-5">
        <div class="container">
            <div class="text-center">
                <h1 class="display-5 fw-bold text-success">Our Healthy Menu</h1>
                <p class="lead text-muted">Fresh, nutritious meals made with love and organic ingredients</p>
            </div>
        </div>
    </div>

    <div class="container py-5">
        <div class="row">
            <!-- Categories Sidebar - Hidden on mobile -->
            <div class="col-lg-3 d-none d-lg-block">
                <div class="card border-0 shadow-sm sticky-top" style="top: 100px;">
                    <div class="card-body">
                        <h5 class="card-title mb-4 d-flex align-items-center">
                            <i class="bi bi-filter-left me-2 text-success"></i>Categories
                        </h5>
                        <div class="d-flex flex-column gap-2">
                            <a href="menu.php" 
                               class="text-decoration-none d-flex align-items-center p-2 rounded-3 <?php echo empty($category_id) ? 'bg-success text-white' : 'text-muted'; ?>">
                                <i class="bi bi-grid me-2"></i> All Categories
                                <span class="ms-auto badge bg-light text-dark"><?php echo count($products); ?></span>
                            </a>
                            <?php foreach ($categories as $cat): ?>
                                <?php 
                                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ? AND is_available = 1");
                                    $stmt->execute([$cat['id']]);
                                    $count = $stmt->fetch()['count'];
                                ?>
                                <a href="?category=<?php echo $cat['id']; ?>" 
                                   class="text-decoration-none d-flex align-items-center p-2 rounded-3 <?php echo ($category_id == $cat['id']) ? 'bg-success text-white' : 'text-muted'; ?>">
                                    <i class="bi bi-<?php echo $cat['icon'] ?? 'circle'; ?> me-2"></i> 
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                    <span class="ms-auto badge bg-light text-dark"><?php echo $count; ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="mt-4">
                            <h6 class="text-muted mb-3">Dietary Preferences</h6>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" value="" id="vegan">
                                <label class="form-check-label" for="vegan">
                                    <i class="bi bi-leaf text-success me-1"></i> Vegan
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" value="" id="vegetarian">
                                <label class="form-check-label" for="vegetarian">
                                    <i class="bi bi-egg text-success me-1"></i> Vegetarian
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="" id="gluten-free">
                                <label class="form-check-label" for="gluten-free">
                                    <i class="bi bi-check-circle text-success me-1"></i> Gluten Free
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Products Grid -->
            <div class="col-lg-9">
                <?php if (empty($products)): ?>
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <i class="bi bi-emoji-frown display-1 text-muted"></i>
                        </div>
                        <h3 class="mb-3">No items found</h3>
                        <p class="text-muted">We couldn't find any items matching your selection.</p>
                        <a href="menu.php" class="btn btn-success px-4">
                            <i class="bi bi-arrow-left me-2"></i>Back to Menu
                        </a>
                    </div>
                <?php else: ?>
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                        <?php foreach ($products as $product): ?>
                            <div class="col">
                                <div class="card h-100 border-0 shadow-sm hover-lift">
                                    <div class="position-relative w-100 d-flex align-items-center justify-content-center" style="min-height: 200px; max-height: 250px; background-color: #f8f9fa; padding: 10px 0;">
                                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                             class="img-fluid" 
                                             style="max-height: 230px; width: auto; max-width: 100%;"
                                             alt="<?php echo htmlspecialchars($product['name']); ?>">
                                        <?php if (isset($product['is_vegetarian']) && $product['is_vegetarian']): ?>
                                            <div class="position-absolute top-2 start-2 bg-success text-white px-2 py-1 rounded-pill" style="font-size: 0.7rem;">
                                                <i class="bi bi-egg me-1"></i>Veg
                                            </div>
                                        <?php endif; ?>
                                        <?php if (isset($product['is_vegan']) && $product['is_vegan']): ?>
                                            <div class="position-absolute top-2 start-2 bg-success text-white px-2 py-1 rounded-pill" style="font-size: 0.7rem;">
                                                <i class="bi bi-leaf me-1"></i>Vegan
                                            </div>
                                        <?php endif; ?>
                                        <?php if (isset($product['is_gluten_free']) && $product['is_gluten_free']): ?>
                                            <div class="position-absolute top-2 end-2 bg-info text-white px-2 py-1 rounded-pill" style="font-size: 0.7rem;">
                                                <i class="bi bi-check-circle me-1"></i>GF
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h5 class="card-title mb-1"><?php echo htmlspecialchars($product['name']); ?></h5>
                                            <span class="badge bg-success bg-opacity-10 text-success">
                                                <?php echo htmlspecialchars($product['category_name']); ?>
                                            </span>
                                        </div>
                                        <div class="menu-description mb-3">
                                            <p class="card-text text-muted small mb-0" style="line-height: 1.5;">
                                                <?php 
                                                $description = htmlspecialchars($product['description']);
                                                $shortDesc = strlen($description) > 120 ? substr($description, 0, 120) . '...' : $description;
                                                $fullDesc = $description;
                                                ?>
                                                <span class="short-desc"><?php echo $shortDesc; ?></span>
                                                <?php if (strlen($description) > 120): ?>
                                                    <span class="full-desc d-none"><?php echo $fullDesc; ?></span>
                                                    <a href="#" class="text-primary read-more" style="font-size: 0.8rem; text-decoration: none;">Read more</a>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <span class="h5 mb-0 text-success fw-bold">₹<?php echo number_format($product['price'], 2); ?></span>
                                                <?php if (isset($product['original_price']) && $product['original_price'] > $product['price']): ?>
                                                    <small class="text-decoration-line-through text-muted ms-2">₹<?php echo number_format($product['original_price'], 2); ?></small>
                                                <?php endif; ?>
                                            </div>
                                            <?php 
                                            // Ensure price is a valid number
                                            $price = is_numeric($product['price']) ? (float)$product['price'] : 0.00;
                                            ?>
                                            <div class="d-flex gap-2">
                                                <button class="btn btn-outline-success btn-sm order-now" 
                                                        data-product-id="<?php echo $product['id']; ?>"
                                                        data-product-name="<?php echo htmlspecialchars($product['name']); ?>"
                                                        data-product-price="<?php echo $price; ?>"
                                                        style="white-space: nowrap;"
                                                        title="Order Now">
                                                    <i class="bi bi-lightning-charge"></i> Order Now
                                                </button>
                                                <button class="btn btn-success btn-sm rounded-circle add-to-cart" 
                                                        data-product-id="<?php echo $product['id']; ?>"
                                                        data-product-name="<?php echo htmlspecialchars($product['name']); ?>"
                                                        data-product-price="<?php echo $price; ?>"
                                                        style="width: 40px; height: 40px;"
                                                        title="Add to Cart"
                                                        onclick="console.log('Adding to cart:', {id: <?php echo $product['id']; ?>, name: '<?php echo addslashes($product['name']); ?>', price: <?php echo $price; ?>})">
                                                    <i class="bi bi-plus-lg"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Pagination -->
                <nav class="mt-5">
                    <ul class="pagination justify-content-center">
                        <li class="page-item disabled">
                            <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Previous</a>
                        </li>
                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                        <li class="page-item"><a class="page-link" href="#">3</a></li>
                        <li class="page-item">
                            <a class="page-link" href="#">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function() {
        // Handle category filter clicks
        $('.category-btn').on('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all buttons
            $('.category-btn').removeClass('active');
            
            // Add active class to clicked button
            $(this).addClass('active');
            
            // Get the category ID from the data attribute
            const categoryId = $(this).data('category-id');
            
            // Show loading state
            const productsContainer = $('.row-cols-1');
            productsContainer.html(`
                <div class="col-12 text-center py-5">
                    <div class="spinner-border text-success" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading products...</p>
                </div>
            `);
            
            // Navigate to the new URL
            let url = 'menu.php';
            if (categoryId) {
                url += `?category=${categoryId}`;
            }
            window.location.href = url;
        });
        
        // Function to add item to cart
        function addToCart(button, redirectAfterAdd) {
            e = window.event || e;
            e.preventDefault();
            
            const productId = button.data('product-id');
            const productName = button.data('product-name');
            let productPrice = parseFloat(button.data('product-price'));
            
            // Debug: Log the raw data
            console.log('Add to cart clicked:', {
                id: productId,
                name: productName,
                price: productPrice,
                rawPrice: button.data('product-price'),
                redirectAfterAdd: redirectAfterAdd
            });
            
            // Ensure we have a valid price
            if (isNaN(productPrice) || productPrice <= 0) {
                console.error('Invalid price detected:', button.data('product-price'));
                alert('Error: This item has an invalid price. Please try again or contact support.');
                return;
            }
            
            // Check if user is logged in
            $.get('api/check-auth.php', function(response) {
                if (!response.loggedIn) {
                    // Store the current URL in sessionStorage to redirect back after login
                    sessionStorage.setItem('redirectAfterLogin', window.location.href);
                    // Store the item in localStorage to add after login
                    const pendingItem = {
                        id: productId,
                        name: productName,
                        price: productPrice,
                        quantity: 1,
                        timestamp: new Date().getTime()
                    };
                    let pendingItems = JSON.parse(localStorage.getItem('pendingCartItems') || '[]');
                    pendingItems.push(pendingItem);
                    localStorage.setItem('pendingCartItems', JSON.stringify(pendingItems));
                    
                    // Redirect to login page
                    window.location.href = 'login.php';
                    return;
                }
                
                // Disable button and show loading state
                const originalHtml = button.html();
                button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
                
                // Debug: Log the data being sent to the server
                const cartData = {
                    type: 'standard',
                    items: [{
                        id: productId,
                        name: productName,
                        price: productPrice, // Price in rupees
                        quantity: 1,
                        // Default nutrition values
                        calories: 0,
                        protein: 0,
                        carbs: 0,
                        fat: 0,
                        fiber: 0
                    }]
                };
                
                console.log('Sending to server:', cartData);
                
                // Add to cart via API
                $.ajax({
                    url: 'api/add-to-cart.php',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(cartData),
                    success: function(response) {
                        if (response.success) {
                            // Update cart count
                            const cartCount = $('.cart-count');
                            if (cartCount.length) {
                                cartCount.text(parseInt(cartCount.text() || '0') + 1);
                            }
                            
                            if (redirectAfterAdd) {
                                // Redirect to checkout after a short delay
                                setTimeout(() => {
                                    window.location.href = 'checkout.php';
                                }, 500);
                            } else {
                                // Show success message only for add to cart
                                alert('Item added to cart successfully!');
                            }
                        } else {
                            alert('Error: ' + (response.message || 'Failed to add item to cart'));
                        }
                    },
                    error: function(xhr, status, error) {
                        // If unauthorized (401), redirect to login
                        if (xhr.status === 401) {
                            sessionStorage.setItem('redirectAfterLogin', window.location.href);
                            window.location.href = 'login.php';
                        } else {
                            console.error('Error:', error);
                            alert('An error occurred while adding the item to cart. Please try again.');
                        }
                    },
                    complete: function() {
                        // Reset button state only if still on the same page
                        if (!button.closest('body').length) return;
                        button.prop('disabled', false).html(originalHtml);
                    }
                });
            });
        }
        
        // Handle add to cart button clicks
        $('.add-to-cart').click(function(e) {
            addToCart($(this), false);
        });
        
        // Handle order now button clicks
        $('.order-now').click(function(e) {
            addToCart($(this), true);
        });
    });
    </script>
    
    <script>
        // Handle Read More functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Read more/less functionality
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('read-more')) {
                    e.preventDefault();
                    const container = e.target.closest('.menu-description');
                    const shortDesc = container.querySelector('.short-desc');
                    const fullDesc = container.querySelector('.full-desc');
                    
                    if (e.target.textContent === 'Read more') {
                        shortDesc.classList.add('d-none');
                        fullDesc.classList.remove('d-none');
                        e.target.textContent = 'Show less';
                    } else {
                        shortDesc.classList.remove('d-none');
                        fullDesc.classList.add('d-none');
                        e.target.textContent = 'Read more';
                    }
                }
            });
        });
    </script>
</body>
</html>

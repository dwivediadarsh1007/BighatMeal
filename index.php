<?php
session_start();
require_once 'config.php';

// Get featured products
$stmt = $conn->query("SELECT * FROM products WHERE is_available = 1 LIMIT 8");
$featured_products = $stmt->fetchAll();

// Get categories
$stmt = $conn->query("SELECT * FROM categories");
$categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BighatMeal - Order Healthy Meals Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center min-vh-75 py-5">
                <div class="col-lg-6 mb-5 mb-lg-0">
                    <span class="badge bg-success bg-opacity-10 text-success mb-3">100% Organic & Fresh</span>
                    <h1 class="display-4 fw-bold mb-4">Eat Healthy, Stay Healthy</h1>
                    <p class="lead text-muted mb-4">Fresh, nutritious meals prepared daily by expert chefs using locally-sourced, organic ingredients. Delivered to your door for a healthier you.</p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="menu.php" class="btn btn-primary btn-lg px-4">
                            <i class="bi bi-basket me-2"></i>Order Now
                        </a>
                        <a href="customize-meal.php" class="btn btn-outline-success btn-lg px-4">
                            <i class="bi bi-sliders me-2"></i>Customize Your Meal
                        </a>
                    </div>
                    <!-- Review section removed -->
                </div>
                <div class="col-lg-6">
                    <div class="position-relative">
                        <style>
    @keyframes rotate {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    .rotating-image {
        animation: rotate 10s linear infinite;
        transform-origin: center center;
    }
</style>
<div class="position-relative d-inline-block">
    <img src="images/home.png" alt="Delicious Food" class="img-fluid rounded-4 shadow-lg rotating-image" style="max-height: 500px; width: 100%; object-fit: cover; max-width: 500px; display: block; margin: 0 auto; border: 4px solid #4CAF50; padding: 10px; background: white; box-shadow: 0 0 20px rgba(0,0,0,0.1) !important;">
</div>
                        <div class="position-absolute top-0 start-0 translate-middle bg-white p-3 rounded-circle shadow d-none d-md-block">
                            <div class="bg-success bg-opacity-10 rounded-circle p-3">
                                <i class="bi bi-egg-fried text-success fs-4"></i>
                            </div>
                        </div>
                        <div class="position-absolute top-50 start-100 translate-middle bg-white p-3 rounded-circle shadow d-none d-lg-block">
                            <div class="bg-warning bg-opacity-10 rounded-circle p-3">
                                <i class="bi bi-egg text-warning fs-4"></i>
                            </div>
                        </div>
                        <div class="position-absolute bottom-0 end-0 translate-middle bg-white p-3 rounded-circle shadow d-none d-md-block">
                            <div class="bg-danger bg-opacity-10 rounded-circle p-3">
                                <i class="bi bi-cup-hot text-danger fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card border-0 bg-white rounded-4 p-4 h-100">
                        <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                            <i class="bi bi-truck text-success fs-4"></i>
                        </div>
                        <h5 class="mb-2">Fast Delivery</h5>
                        <p class="text-muted mb-0">Get your healthy meals delivered to your doorstep in under 30 minutes.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 bg-white rounded-4 p-4 h-100">
                        <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                            <i class="bi bi-shield-check text-primary fs-4"></i>
                        </div>
                        <h5 class="mb-2">100% Organic</h5>
                        <p class="text-muted mb-0">All ingredients are carefully selected and 100% organic.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 bg-white rounded-4 p-4 h-100">
                        <div class="bg-warning bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                            <i class="bi bi-heart text-warning fs-4"></i>
                        </div>
                        <h5 class="mb-2">Healthy & Tasty</h5>
                        <p class="text-muted mb-0">Delicious meals that are both healthy and satisfying.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Enhanced Special Offers Carousel with Custom Animation -->
    <section class="mb-5 carousel-section">
        <div id="offersCarousel" class="carousel slide carousel-slide-animation" data-bs-ride="carousel" data-bs-interval="3000" data-bs-pause="hover">
            <!-- Indicators -->
            <div class="carousel-indicators">
                <button type="button" data-bs-target="#offersCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Offer 1"></button>
                <button type="button" data-bs-target="#offersCarousel" data-bs-slide-to="1" aria-label="Offer 2"></button>
                <button type="button" data-bs-target="#offersCarousel" data-bs-slide-to="2" aria-label="Offer 3"></button>
            </div>
            
            <!-- Slides -->
            <div class="carousel-inner rounded-4 overflow-hidden shadow-lg">
                <!-- Slide 1 - Banner (Full Width) -->
                <div class="carousel-item active">
                    <div class="offer-slide position-relative" style="background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('images/banner.png') center/cover no-repeat;">
                        <div class="container h-100 d-flex flex-column justify-content-center text-white text-center py-5">
                            <div class="slide-content" data-aos="fade-up" data-aos-duration="1000">
                                <span class="badge bg-warning text-dark mb-3 px-3 py-2 rounded-pill">Special Offer</span>
                                <h2 class="display-3 fw-bold mb-3 text-shadow">Explore Our Menu</h2>
                                <p class="lead mb-4 text-light">Discover delicious and healthy meals made with love</p>
                                <div class="d-flex justify-content-center gap-3 flex-wrap">
                                    <a href="menu.php" class="btn btn-warning btn-lg px-4 py-3 fw-bold rounded-pill shadow mb-2 mb-md-0">
                                        <i class="bi bi-utensils me-2"></i>View Full Menu
                                    </a>
                                    <a href="bestsellers.php" class="btn btn-outline-light btn-lg px-4 py-3 fw-bold rounded-pill shadow border-2">
                                        <i class="bi bi-star-fill text-warning me-2"></i>Our Bestsellers
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="scroll-indicator">
                            <a href="#reels" class="scroll-down-btn">
                                <i class="bi bi-chevron-down"></i>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Slide 2 - Organic -->
                <div class="carousel-item">
                    <div class="offer-slide d-flex align-items-center" style="background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('images/organic.avif') center/cover no-repeat; min-height: 80vh;">
                        <div class="container text-white text-center py-5">
                            <div class="slide-content" data-aos="zoom-in" data-aos-duration="1000">
                                <span class="badge bg-success bg-opacity-25 text-white mb-3 px-3 py-2 rounded-pill">100% Natural</span>
                                <h2 class="display-4 fw-bold mb-3 text-shadow">Fresh & Organic</h2>
                                <p class="lead mb-4 text-light">Sourced from local farms for the freshest ingredients</p>
                                <a href="https://www.loveandlemons.com/salad-recipes/" target="_blank" rel="noopener noreferrer" class="btn btn-success btn-lg px-5 py-3 fw-bold rounded-pill shadow">
                                    <i class="bi bi-leaf me-2"></i>Learn More
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Slide 3 - Healthy -->
                <div class="carousel-item">
                    <div class="offer-slide d-flex align-items-center" style="background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('images/healthy.jpg') center/cover no-repeat; min-height: 80vh;">
                        <div class="container text-white text-center py-5">
                            <div class="slide-content" data-aos="fade-up" data-aos-duration="1000">
                                <span class="badge bg-primary bg-opacity-25 text-white mb-3 px-3 py-2 rounded-pill">Healthy Living</span>
                                <h2 class="display-4 fw-bold mb-3 text-shadow">Nourish Your Body</h2>
                                <p class="lead mb-4 text-light">Delicious meals crafted for your well-being</p>
                                <a href="menu.php" class="btn btn-primary btn-lg px-5 py-3 fw-bold rounded-pill shadow">
                                    <i class="bi bi-heart-pulse me-2"></i>View Healthy Menu
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Controls -->
            <button class="carousel-control-prev" type="button" data-bs-target="#offersCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#offersCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>
        
        <style>
            /* Carousel Custom Styles */
            .carousel-section {
                margin: 2rem 0;
                position: relative;
            }
            
            /* Custom Slide Animation */
            .carousel-slide-animation.carousel-slide .carousel-item {
                opacity: 0;
                transition: opacity 0.5s ease-in-out;
            }
            
            .carousel-slide-animation.carousel-slide .active.carousel-item-start,
            .carousel-slide-animation.carousel-slide .active.carousel-item-end {
                opacity: 0;
            }
            
            .carousel-slide-animation.carousel-slide .active,
            .carousel-slide-animation.carousel-slide .carousel-item-next.carousel-item-start,
            .carousel-slide-animation.carousel-slide .carousel-item-prev.carousel-item-end {
                opacity: 1;
                transform: none;
            }
            
            /* Slide in from right */
            .carousel-slide-animation.carousel-slide .carousel-item-next:not(.carousel-item-start) {
                transform: translateX(100%);
            }
            
            /* Slide out to left */
            .carousel-slide-animation.carousel-slide .active.carousel-item-start ~ .carousel-item-next:not(.carousel-item-start) {
                transform: translateX(100%);
            }
            
            /* Slide in from left */
            .carousel-slide-animation.carousel-slide .carousel-item-prev:not(.carousel-item-end) {
                transform: translateX(-100%);
            }
            
            /* Slide out to right */
            .carousel-slide-animation.carousel-slide .active.carousel-item-end ~ .carousel-item-prev:not(.carousel-item-end) {
                transform: translateX(-100%);
            }
            
            .offer-slide {
                min-height: 80vh;
                background-size: cover !important;
                background-position: center !important;
                transition: transform 0.6s cubic-bezier(0.25, 1, 0.5, 1), opacity 0.6s ease-out;
                position: relative;
                overflow: hidden;
                backface-visibility: hidden;
                perspective: 1000px;
            }
            
            .slide-content {
                position: relative;
                z-index: 2;
                padding: 2rem;
                background: rgba(0, 0, 0, 0.2);
                border-radius: 1rem;
                backdrop-filter: blur(5px);
                border: 1px solid rgba(255, 255, 255, 0.1);
                box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            }
            
            .text-shadow {
                text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
            }
            
            .carousel-control-prev,
            .carousel-control-next {
                width: 5%;
                opacity: 0;
                transition: all 0.3s ease;
            }
            
            .carousel:hover .carousel-control-prev,
            .carousel:hover .carousel-control-next {
                opacity: 1;
            }
            
            .carousel-control-prev-icon,
            .carousel-control-next-icon {
                background-color: rgba(0, 0, 0, 0.5);
                border-radius: 50%;
                width: 3rem;
                height: 3rem;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.3s ease;
            }
            
            .carousel-control-prev:hover .carousel-control-prev-icon,
            .carousel-control-next:hover .carousel-control-next-icon {
                background-color: rgba(0, 0, 0, 0.8);
                transform: scale(1.1);
            }
            
            .carousel-indicators button {
                width: 12px !important;
                height: 12px !important;
                border-radius: 50%;
                margin: 0 5px;
                border: 2px solid white;
                background: transparent;
                opacity: 0.7;
                transition: all 0.3s ease;
            }
            
            .carousel-indicators button.active {
                background: white;
                transform: scale(1.2);
                opacity: 1;
            }
            
            .scroll-indicator {
                position: absolute;
                bottom: 2rem;
                left: 50%;
                transform: translateX(-50%);
                z-index: 5;
                animation: bounce 2s infinite;
            }
            
            .scroll-down-btn {
                color: white;
                font-size: 2rem;
                text-decoration: none;
                display: flex;
                align-items: center;
                justify-content: center;
                width: 50px;
                height: 50px;
                border: 2px solid rgba(255, 255, 255, 0.5);
                border-radius: 50%;
                transition: all 0.3s ease;
            }
            
            .scroll-down-btn:hover {
                background: rgba(255, 255, 255, 0.1);
                transform: translateY(5px);
            }
            
            @keyframes bounce {
                0%, 20%, 50%, 80%, 100% { transform: translateY(0) translateX(-50%); }
                40% { transform: translateY(-20px) translateX(-50%); }
                60% { transform: translateY(-10px) translateX(-50%); }
            }
            
            /* Add subtle scale effect on active slide */
            .carousel-item.active .offer-slide {
                animation: subtleScale 8s infinite alternate ease-in-out;
            }
            
            @keyframes subtleScale {
                0% { transform: scale(1); }
                100% { transform: scale(1.02); }
            }
            
            /* Responsive adjustments */
            @media (max-width: 768px) {
                .offer-slide {
                    min-height: 60vh;
                }
                
                .slide-content {
                    padding: 1.5rem;
                }
                
                .carousel-control-prev,
                .carousel-control-next {
                    opacity: 1;
                }
            }
        </style>
    </section>
    
    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Initialize AOS animation
        document.addEventListener('DOMContentLoaded', function() {
            AOS.init({
                once: true,
                duration: 1000,
                easing: 'ease-in-out',
            });
            
            // Add slide animation class after page load
            const carousel = document.getElementById('offersCarousel');
            if (carousel) {
                // Remove fade class and add slide animation class
                carousel.classList.remove('carousel-fade');
                carousel.classList.add('carousel-slide');
                
                // Force reflow to ensure smooth initial animation
                carousel.offsetHeight;
            }
        });
    </script>

    <!-- Categories Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-4">Food Categories</h2>
            <div class="row">
                <?php foreach ($categories as $category): ?>
                    <div class="col-md-3 mb-4">
                        <div class="card h-100">
                            <img src="<?php echo $category['image_url']; ?>" class="card-img-top" alt="<?php echo $category['name']; ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $category['name']; ?></h5>
                                <p class="card-text"><?php echo substr($category['description'], 0, 50) . '...'; ?></p>
                                <a href="menu.php?category=<?php echo $category['id']; ?>" class="btn btn-primary">View Menu</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Instagram Reels Section -->
    <section class="py-5 bg-white" id="reels">
        <div class="container">
            <h4 class="text-center mb-4">Follow Our Journey</h4>
            
            <div class="row justify-content-center g-4 mx-0">
                <div class="col-12 col-md-6 px-0">
                    <div class="instagram-reel-container" style="width: 100%; overflow: hidden;">
                        <blockquote class="instagram-media" data-instgrm-permalink="https://www.instagram.com/reel/DLzIHQghxJ_/" data-instgrm-version="14" style="background:#FFF; border:0; border-radius:3px; box-shadow:0 0 1px 0 rgba(0,0,0,0.5),0 1px 10px 0 rgba(0,0,0,0.15); margin: 0; padding: 0; width: 100%; display: block;">
                            <div style="padding:16px;">
                            <a href="https://www.instagram.com/reel/DLzIHQghxJ_/?utm_source=ig_embed&amp;utm_campaign=loading" style="background:#FFFFFF; line-height:0; padding:0 0; text-align:center; text-decoration:none; width:100%;" target="_blank">
                                <div style="display: flex; flex-direction: row; align-items: center;">
                                    <div style="background-color: #F4F4F4; border-radius: 50%; flex-grow: 0; height: 40px; margin-right: 14px; width: 40px;"></div>
                                    <div style="display: flex; flex-direction: column; flex-grow: 1; justify-content: center;">
                                        <div style="background-color: #F4F4F4; border-radius: 4px; flex-grow: 0; height: 14px; margin-bottom: 6px; width: 100px;"></div>
                                        <div style="background-color: #F4F4F4; border-radius: 4px; flex-grow: 0; height: 14px; width: 60px;"></div>
                                    </div>
                                </div>
                                <div style="padding: 19% 0;"></div>
                                <div style="display:block; height:50px; margin:0 auto 12px; width:50px;">
                                    <svg width="50px" height="50px" viewBox="0 0 60 60" version="1.1" xmlns="https://www.w3.org/2000/svg" xmlns:xlink="https://www.w3.org/1999/xlink">
                                        <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                            <g transform="translate(-511.000000, -20.000000)" fill="#000000">
                                                <g>
                                                    <path d="M556.869,30.41 C554.814,30.41 553.148,32.076 553.148,34.131 C553.148,36.186 554.814,37.852 556.869,37.852 C558.924,37.852 560.59,36.186 560.59,34.131 C560.59,32.076 558.924,30.41 556.869,30.41 M541,60.657 C535.114,60.657 530.342,55.887 530.342,50 C530.342,44.114 535.114,39.342 541,39.342 C546.887,39.342 551.658,44.114 551.658,50 C551.658,55.887 546.887,60.657 541,60.657 M541,33.886 C532.1,33.886 524.886,41.1 524.886,50 C524.886,58.899 532.1,66.113 541,66.113 C549.9,66.113 557.115,58.899 557.115,50 C557.115,41.1 549.9,33.886 541,33.886 M565.378,62.101 C565.244,65.022 564.756,66.606 564.346,67.663 C563.803,69.06 563.154,70.057 562.106,71.106 C561.058,72.155 560.06,72.803 558.662,73.347 C557.607,73.757 556.021,74.244 553.102,74.378 C549.944,74.521 548.997,74.552 541,74.552 C533.003,74.552 532.056,74.521 528.898,74.378 C525.979,74.244 524.393,73.757 523.338,73.347 C521.94,72.803 520.942,72.155 519.894,71.106 C518.846,70.057 518.197,69.06 517.654,67.663 C517.244,66.606 516.755,65.022 516.623,62.101 C516.479,58.943 516.448,57.996 516.448,50 C516.448,42.003 516.479,41.056 516.623,37.899 C516.755,34.978 517.244,33.391 517.654,32.338 C518.197,30.938 518.846,29.942 519.894,28.894 C520.942,27.846 521.94,27.196 523.338,26.654 C524.393,26.244 525.979,25.756 528.898,25.623 C532.057,25.479 533.004,25.448 541,25.448 C548.997,25.448 549.943,25.479 553.102,25.623 C556.021,25.756 557.607,26.244 558.662,26.654 C560.06,27.196 561.058,27.846 562.106,28.894 C563.154,29.942 563.803,30.938 564.346,32.338 C564.756,33.391 565.244,34.978 565.378,37.899 C565.522,41.056 565.552,42.003 565.552,50 C565.552,57.996 565.522,58.943 565.378,62.101 M570.82,37.631 C570.674,34.438 570.167,32.258 569.425,30.349 C568.659,28.377 567.633,26.702 565.965,25.035 C564.297,23.368 562.623,22.342 560.652,21.575 C558.743,20.834 556.562,20.326 553.369,20.18 C550.169,20.033 549.148,20 541,20 C532.853,20 531.831,20.033 528.631,20.18 C525.438,20.326 523.257,20.834 521.349,21.575 C519.376,22.342 517.703,23.368 516.035,25.035 C514.368,26.702 513.342,28.377 512.574,30.349 C511.834,32.258 511.326,34.438 511.181,37.631 C511.035,40.831 511,41.851 511,50 C511,58.147 511.035,59.17 511.181,62.369 C511.326,65.562 511.834,67.743 512.574,69.651 C513.342,71.625 514.368,73.296 516.035,74.965 C517.703,76.634 519.376,77.658 521.349,78.425 C523.257,79.167 525.438,79.673 528.631,79.82 C531.831,79.965 532.853,80.001 541,80.001 C549.148,80.001 550.169,79.965 553.369,79.82 C556.562,79.673 558.743,79.167 560.652,78.425 C562.623,77.658 564.297,76.634 565.965,74.965 C567.633,73.296 568.659,71.625 569.425,69.651 C570.167,67.743 570.674,65.562 570.82,62.369 C570.966,59.17 571,58.147 571,50 C571,41.851 570.966,40.831 570.82,37.631"></path>
                                                </g>
                                            </g>
                                        </g>
                                    </svg>
                                </div>
                                <div style="padding-top: 8px;">
                                    <div style="color:#3897f0; font-family:Arial,sans-serif; font-size:14px; font-style:normal; font-weight:550; line-height:18px;">View this post on Instagram</div>
                                </div>
                                <div style="padding: 12.5% 0;"></div>
                            </a>
                            <p style="color:#c9c8cd; font-family:Arial,sans-serif; font-size:14px; line-height:17px; margin-bottom:0; margin-top:8px; overflow:hidden; padding:8px 0 7px; text-align:center; text-overflow:ellipsis; white-space:nowrap;">
                                <a href="https://www.instagram.com/reel/DLzIHQghxJ_/?utm_source=ig_embed&amp;utm_campaign=loading" style="color:#c9c8cd; font-family:Arial,sans-serif; font-size:14px; font-style:normal; font-weight:normal; line-height:17px; text-decoration:none;" target="_blank">A post shared by 🥗 BigHat Meal (@bighatmeal)</a>
                            </p>
                            </div>
                        </blockquote>
                        <script async src="//www.instagram.com/embed.js"></script>
                    </div>
                </div>
                <div class="col-12 col-md-6 px-0">
                    <div class="instagram-reel-container" style="width: 100%; overflow: hidden;">
                        <blockquote class="instagram-media" data-instgrm-permalink="https://www.instagram.com/reel/DLsG-bdNhxU/" data-instgrm-version="14" style="background:#FFF; border:0; border-radius:3px; box-shadow:0 0 1px 0 rgba(0,0,0,0.5),0 1px 10px 0 rgba(0,0,0,0.15); margin: 0; padding: 0; width: 100%; display: block;">
                            <div style="padding:16px;">
                                <a href="https://www.instagram.com/reel/DLsG-bdNhxU/" style="display:block; text-decoration:none;" target="_blank">
                                    <div style="display:block; height:50px; margin:0 auto; width:50px;">
                                        <svg width="50px" height="50px" viewBox="0 0 60 60" version="1.1" xmlns="https://www.w3.org/2000/svg" xmlns:xlink="https://www.w3.org/1999/xlink">
                                            <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                                <g transform="translate(-511.000000, -20.000000)" fill="#000000">
                                                    <g>
                                                        <path d="M556.869,30.41 C554.814,30.41 553.148,32.076 553.148,34.131 C553.148,36.186 554.814,37.852 556.869,37.852 C558.924,37.852 560.59,36.186 560.59,34.131 C560.59,32.076 558.924,30.41 556.869,30.41 M541,60.657 C535.114,60.657 530.342,55.887 530.342,50 C530.342,44.114 535.114,39.342 541,39.342 C546.887,39.342 551.658,44.114 551.658,50 C551.658,55.887 546.887,60.657 541,60.657 M541,33.886 C532.1,33.886 524.886,41.1 524.886,50 C524.886,58.899 532.1,66.113 541,66.113 C549.9,66.113 557.115,58.899 557.115,50 C557.115,41.1 549.9,33.886 541,33.886 M565.378,62.101 C565.244,65.022 564.756,66.606 564.346,67.663 C563.803,69.06 563.154,70.057 562.106,71.106 C561.058,72.155 560.06,72.803 558.662,73.347 C557.607,73.757 556.021,74.244 553.102,74.378 C549.944,74.521 548.997,74.552 541,74.552 C533.003,74.552 532.056,74.521 528.898,74.378 C525.979,74.244 524.393,73.757 523.338,73.347 C521.94,72.803 520.942,72.155 519.894,71.106 C518.846,70.057 518.197,69.06 517.654,67.663 C517.244,66.606 516.755,65.022 516.623,62.101 C516.479,58.943 516.448,57.996 516.448,50 C516.448,42.003 516.479,41.056 516.623,37.899 C516.755,34.978 517.244,33.391 517.654,32.338 C518.197,30.938 518.846,29.942 519.894,28.894 C520.942,27.846 521.94,27.196 523.338,26.654 C524.393,26.244 525.979,25.756 528.898,25.623 C532.057,25.479 533.004,25.448 541,25.448 C548.997,25.448 549.943,25.479 553.102,25.623 C556.021,25.756 557.607,26.244 558.662,26.654 C560.06,27.196 561.058,27.846 562.106,28.894 C563.154,29.942 563.803,30.938 564.346,32.338 C564.756,33.391 565.244,34.978 565.378,37.899 C565.522,41.056 565.552,42.003 565.552,50 C565.552,57.996 565.522,58.943 565.378,62.101 M570.82,37.631 C570.674,34.438 570.167,32.258 569.425,30.349 C568.659,28.377 567.633,26.702 565.965,25.035 C564.297,23.368 562.623,22.342 560.652,21.575 C558.743,20.834 556.562,20.326 553.369,20.18 C550.169,20.033 549.148,20 541,20 C532.853,20 531.831,20.033 528.631,20.18 C525.438,20.326 523.257,20.834 521.349,21.575 C519.376,22.342 517.703,23.368 516.035,25.035 C514.368,26.702 513.342,28.377 512.574,30.349 C511.834,32.258 511.326,34.438 511.181,37.631 C511.035,40.831 511,41.851 511,50 C511,58.147 511.035,59.17 511.181,62.369 C511.326,65.562 511.834,67.743 512.574,69.651 C513.342,71.625 514.368,73.296 516.035,74.965 C517.703,76.634 519.376,77.658 521.349,78.425 C523.257,79.167 525.438,79.673 528.631,79.82 C531.831,79.965 532.853,80.001 541,80.001 C549.148,80.001 550.169,79.965 553.369,79.82 C556.562,79.673 558.743,79.167 560.652,78.425 C562.623,77.658 564.297,76.634 565.965,74.965 C567.633,73.296 568.659,71.625 569.425,69.651 C570.167,67.743 570.674,65.562 570.82,62.369 C570.966,59.17 571,58.147 571,50 C571,41.851 570.966,40.831 570.82,37.631"></path>
                                                    </g>
                                                </g>
                                            </g>
                                        </svg>
                                    </div>
                                </a>
                            </div>
                        </blockquote>
                        <script async src="//www.instagram.com/embed.js"></script>
                    </div>
                </div>
            </div>
            
                </div>
            </div>
            <div class="text-center mt-3">
                <a href="https://www.instagram.com/bighatmeal?utm_source=ig_web_button_share_sheet&igsh=ODF1bGw4cmd6Z2Uy" target="_blank" class="btn btn-sm btn-instagram">
                    <i class="bi bi-instagram me-1"></i> @bighatmeal
                </a>
    
    <script>
    // Initialize Carousel with Hover Pause
    document.addEventListener('DOMContentLoaded', function() {
        const carousel = document.getElementById('offersCarousel');
        const carouselInstance = new bootstrap.Carousel(carousel, {
            interval: 2500,
            pause: 'hover',
            wrap: true
        });
        
        // Pause on hover
        carousel.addEventListener('mouseenter', function() {
            carouselInstance.pause();
        });
        
        // Resume when mouse leaves
        carousel.addEventListener('mouseleave', function() {
            carouselInstance.cycle();
        });
    });
    
    // Load Instagram embed script
    (function() {
        var instagramScript = document.createElement('script');
        instagramScript.src = '//www.instagram.com/embed.js';
        instagramScript.async = true;
        document.body.appendChild(instagramScript);
        
        // Process embeds once script is loaded
        instagramScript.onload = function() {
            if (window.instgrm) {
                window.instgrm.Embeds.process();
            }
        };
    })();
    </script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Function to simulate click on Instagram iframe
        function simulateClick(element) {
            const event = new MouseEvent('click', {
                view: window,
                bubbles: true,
                cancelable: true
            });
            element.dispatchEvent(event);
        }

        // Function to handle intersection
        function handleIntersection(entries, observer) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const iframe = entry.target;
                    // Post a message to the iframe to try to trigger play
                    if (iframe.contentWindow) {
                        // Try to find and click the play button after a short delay
                        setTimeout(() => {
                            try {
                                // This is a best-effort approach as we can't directly access iframe content
                                const rect = iframe.getBoundingClientRect();
                                const x = rect.left + (rect.width / 2);
                                const y = rect.top + (rect.height / 2);
                                
                                // Simulate click in the center of the iframe
                                simulateClick(iframe);
                                
                                // Try to focus the iframe (might help with some browsers)
                                iframe.focus();
                                
                                // Post a message to the iframe (if it's listening)
                                iframe.contentWindow.postMessage(JSON.stringify({
                                    event: 'command',
                                    func: 'playVideo',
                                    args: []
                                }), '*');
                            } catch (e) {
                                console.log('Could not interact with iframe:', e);
                            }
                        }, 500);
                    }
                }
            });
        }

        // Set up intersection observer
        const observer = new IntersectionObserver(handleIntersection, {
            root: null,
            rootMargin: '0px',
            threshold: 0.5
        });

        // Check for Instagram embeds periodically
        const checkForEmbeds = setInterval(function() {
            const iframes = document.querySelectorAll('iframe[src*="instagram.com"]');
            
            if (iframes.length > 0) {
                clearInterval(checkForEmbeds);
                
                // Observe all Instagram iframes
                iframes.forEach(iframe => {
                    observer.observe(iframe);
                    
                    // Add click handler to parent container
                    const container = iframe.closest('.instagram-embed');
                    if (container) {
                        container.style.cursor = 'pointer';
                        container.addEventListener('click', function() {
                            simulateClick(iframe);
                        });
                    }
                });
            }
        }, 1000);
    });
    </script>


    <!-- How It Works -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-4">Customize Your Meal</h2>
            <div class="row">
                <div class="col-md-4 text-center">
                    <i class="bi bi-search display-1 text-primary"></i>
                    <h3>Find Food</h3>
                    <p>Discover amazing dishes from local restaurants.</p>
                </div>
                <div class="col-md-4 text-center">
                    <i class="bi bi-cart display-1 text-primary"></i>
                    <h3>Order Online</h3>
                    <p>Place your order with just a few clicks.</p>
                </div>
                <div class="col-md-4 text-center">
                    <i class="bi bi-truck display-1 text-primary"></i>
                    <h3>Get Delivered</h3>
                    <p>Enjoy your food delivered right to your doorstep.</p>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-play reels when they come into view
        document.addEventListener('DOMContentLoaded', function() {
            const reelVideos = document.querySelectorAll('.reel-video');
            
            // Set up intersection observer for each video
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    const video = entry.target;
                    if (entry.isIntersecting) {
                        video.play().catch(e => console.log('Auto-play prevented:', e));
                    } else {
                        video.pause();
                        video.currentTime = 0; // Reset to start
                    }
                });
            }, {
                threshold: 0.5 // Trigger when 50% of the video is visible
            });

            // Observe each video
            reelVideos.forEach(video => {
                // Preload the video metadata for better performance
                video.load();
                observer.observe(video);
                
                // Toggle play/pause on click
                video.addEventListener('click', function() {
                    if (video.paused) {
                        video.play();
                    } else {
                        video.pause();
                    }
                });
            });
        });
    </script>
    <script src="js/main.js"></script>
</body>
</html>

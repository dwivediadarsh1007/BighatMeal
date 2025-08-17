<?php
if (!isset($_SESSION['username'])) {
    $user = 'Login';
    $user_link = 'login.php';
} else {
    $user = $_SESSION['username'];
    $user_link = 'profile.php';
}
?>
<nav class="navbar navbar-expand-lg navbar-light sticky-top" style="padding: 0 !important; margin: 0 !important; min-height: 70px !important; background-color: white !important; box-shadow: 0 2px 10px rgba(0,0,0,0.1) !important;">
    <div class="container-fluid" style="padding: 0 15px 0 25px !important; height: 100% !important;">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="index.php" style="margin: 0; padding: 0; height: 70px; display: flex; align-items: center;">
            <div style="width: 52px; height: 52px; min-width: 52px; min-height: 52px; border-radius: 50%; border: 2px solid #4CAF50; box-shadow: 0 0 10px rgba(0,0,0,0.1); margin-right: 10px; background: white; display: flex; align-items: center; justify-content: center; overflow: hidden; padding: 2px;">
                <img src="images/logo.jpg" alt="BighatMeal" style="width: 110%; height: 110%; object-fit: cover; object-position: center; display: block; margin: -5%;">
            </div>
            <span style="line-height: 1.2; white-space: nowrap;">
                BighatMeal
            </span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php"><i class="bi bi-house-door me-1"></i> Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="menu.php"><i class="bi bi-menu-app me-1"></i> Menu</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="customize-meal.php"><i class="bi bi-sliders me-1"></i> Customize Meal</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="about.php"><i class="bi bi-info-circle me-1"></i> About</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="https://wa.me/916261148989" target="_blank"><i class="bi bi-whatsapp me-1"></i> Contact</a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item me-2">
                    <a class="btn btn-outline-success btn-sm rounded-pill" href="menu.php">
                        <i class="bi bi-basket"></i> Order Now
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle me-1"></i>
                        <span class="d-none d-lg-inline"><?php echo $user; ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="<?php echo $user_link; ?>"><i class="bi bi-person me-2"></i>Profile</a></li>
                        <li><a class="dropdown-item" href="orders.php"><i class="bi bi-bag-check me-2"></i>My Orders</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link position-relative" href="cart.php">
                        <i class="bi bi-cart3 fs-5"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success cart-count d-none">0</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Back to top button -->
<a href="#" class="back-to-top" id="backToTop">
    <i class="bi bi-arrow-up"></i>
</a>

<footer class="footer">
    <div class="container">
        <div class="row g-4">
            <!-- About Us -->
            <div class="col-lg-4 col-md-6">
                <div class="footer-widget">
                    <h5>About BighatMeal</h5>
                    <p>Delivering delicious and healthy meals to your doorstep since 2025. We partner with local restaurants and chefs to bring you the best dining experience with fresh, organic ingredients.</p>
                    <div class="trust-badges mt-3">
                        <span class="badge bg-success bg-opacity-10 text-success me-2 mb-2"><i class="bi bi-check-circle-fill me-1"></i>100% Organic</span>
                        <span class="badge bg-primary bg-opacity-10 text-primary me-2 mb-2"><i class="bi bi-shield-check me-1"></i>Safe & Secure</span>
                        <span class="badge bg-warning bg-opacity-10 text-warning mb-2"><i class="bi bi-truck me-1"></i>Fast Delivery</span>
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-2 col-md-6">
                <div class="footer-widget">
                    <h5>Quick Links</h5>
                    <ul class="footer-links">
                        <li><a href="index.php"><i class="bi bi-house-door me-2"></i>Home</a></li>
                        <li><a href="about.php"><i class="bi bi-info-circle me-2"></i>About Us</a></li>
                        <li><a href="menu.php"><i class="bi bi-menu-button-wide me-2"></i>Our Menu</a></li>
                        <li><a href="gallery.php"><i class="bi bi-images me-2"></i>Gallery</a></li>
                        <li><a href="contact.php"><i class="bi bi-envelope me-2"></i>Contact Us</a></li>
                    </ul>
                </div>
            </div>

            <!-- Contact Info -->
            <div class="col-lg-3 col-md-6">
                <div class="footer-widget">
                    <h5>Contact Info</h5>
                    <ul class="contact-info">
                        <li><i class="bi bi-geo-alt-fill me-2"></i>Adhartal, Jabalpur, Madhya Pradesh, 482004</li>
                        <li><i class="bi bi-telephone-fill me-2"></i>+91 88174 88082</li>
                        <li><i class="bi bi-envelope-fill me-2"></i>bighatpvtltd@gmail.com</li>
                        <li><i class="bi bi-clock-fill me-2"></i>Mon-Sun: 8:00 AM - 10:00 PM</li>
                    </ul>
                </div>
            </div>

            <!-- Newsletter & Social -->
            <div class="col-lg-3 col-md-6">
                <div class="footer-widget">
                    <h5>Newsletter</h5>
                    <p>Subscribe to get updates on new menu items and special offers!</p>
                    <form class="newsletter-form">
                        <div class="input-group mb-3">
                            <input type="email" class="form-control" placeholder="Your email" required>
                            <button class="btn btn-success" type="submit"><i class="bi bi-send"></i></button>
                        </div>
                    </form>
                    <h5 class="mt-4">Follow Us</h5>
                    <div class="social-links">
                        <a href="#" class="facebook" title="Facebook"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="twitter" title="Twitter"><i class="bi bi-twitter"></i></a>
                        <a href="https://www.instagram.com/bighatmeal?utm_source=ig_web_button_share_sheet&igsh=ODF1bGw4cmd6Z2Uy" class="instagram" title="Instagram" target="_blank" rel="noopener noreferrer"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="youtube" title="YouTube"><i class="bi bi-youtube"></i></a>
                        <a href="#" class="linkedin" title="LinkedIn"><i class="bi bi-linkedin"></i></a>
                    </div>
                </div>
            </div>
        </div>

        <hr class="footer-divider">

        <!-- Copyright & Payment Methods -->
        <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> BighatMeal. All rights reserved.</p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <div class="payment-methods">
                    <i class="bi bi-credit-card me-2" title="Credit Card"></i>
                    <i class="bi bi-paypal me-2" title="PayPal"></i>
                    <i class="bi bi-google-pay me-2" title="Google Pay"></i>
                    <i class="bi bi-apple" title="Apple Pay"></i>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Include Footer CSS -->
<link rel="stylesheet" href="css/footer.css">

<!-- Back to Top Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Back to top button
    const backToTop = document.getElementById('backToTop');
    
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            backToTop.classList.add('active');
        } else {
            backToTop.classList.remove('active');
        }
    });
    
    backToTop.addEventListener('click', function(e) {
        e.preventDefault();
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
    
    // Add animation to social icons on hover
    const socialIcons = document.querySelectorAll('.social-links a');
    socialIcons.forEach(icon => {
        icon.addEventListener('mouseenter', function() {
            this.querySelector('i').style.transform = 'rotate(360deg)';
        });
        icon.addEventListener('mouseleave', function() {
            this.querySelector('i').style.transform = 'rotate(0deg)';
        });
    });
    
    // Add animation to quick links on hover
    const quickLinks = document.querySelectorAll('.footer-links a');
    quickLinks.forEach(link => {
        link.addEventListener('mouseover', function() {
            const icon = this.querySelector('i');
            if (icon) {
                icon.style.transform = 'scale(1.2)';
                icon.style.transition = 'transform 0.3s ease';
            }
        });
        link.addEventListener('mouseout', function() {
            const icon = this.querySelector('i');
            if (icon) {
                icon.style.transform = 'scale(1)';
            }
        });
    });
});
</script>

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })

    // Cart functionality
    const removeItemButtons = document.querySelectorAll('.remove-item');
    removeItemButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const itemId = this.dataset.id;
            removeItemFromCart(itemId);
        });
    });

    function removeItemFromCart(itemId) {
        fetch('api/remove_from_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: itemId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error removing item');
            }
        });
    }

    // Quantity change handler
    document.querySelectorAll('.quantity-input').forEach(input => {
        input.addEventListener('change', function() {
            const itemId = this.dataset.itemId;
            updateQuantity(itemId, this.value);
        });
    });

    function updateQuantity(itemId, quantity) {
        fetch('api/update_quantity.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: itemId, quantity: quantity })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateCartTotal();
            } else {
                alert('Error updating quantity');
            }
        });
    }

    // Update cart total
    function updateCartTotal() {
        fetch('api/get_cart_total.php')
        .then(response => response.json())
        .then(data => {
            document.getElementById('cart-total').textContent = `$${data.total.toFixed(2)}`;
        });
    }

    // Add smooth scrolling for navigation links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            document.querySelector(this.getAttribute('href')).scrollIntoView({
                behavior: 'smooth'
            });
        });
    });

    // Initialize carousel
    const carousel = document.querySelector('.carousel');
    if (carousel) {
        new bootstrap.Carousel(carousel, {
            interval: 5000,
            wrap: true
        });
    }
});

// Add to cart animation
function addToCartAnimation(button) {
    button.classList.add('animate__animated', 'animate__pulse');
    setTimeout(() => {
        button.classList.remove('animate__animated', 'animate__pulse');
    }, 1000);
}

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Add to Cart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { padding: 20px; }
        .product-card { margin-bottom: 20px; }
        #debugOutput {
            margin-top: 30px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
            font-family: monospace;
            white-space: pre-wrap;
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Test Add to Cart</h1>
        
        <div class="row">
            <div class="col-md-4">
                <div class="card product-card">
                    <img src="https://via.placeholder.com/300x200" class="card-img-top" alt="Product Image">
                    <div class="card-body">
                        <h5 class="card-title">Test Product</h5>
                        <p class="card-text">A test product for cart functionality.</p>
                        <p class="h5">$9.99</p>
                        <button class="btn btn-primary" onclick="addToCart(1)">
                            <span id="btnText">Add to Cart</span>
                            <span id="btnSpinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-4">
            <h3>Debug Information</h3>
            <button class="btn btn-secondary mb-2" onclick="clearDebug()">Clear Debug</button>
            <div id="debugOutput"></div>
        </div>
    </div>

    <script>
        function logDebug(message) {
            const debugOutput = document.getElementById('debugOutput');
            const timestamp = new Date().toISOString().substr(11, 12);
            debugOutput.textContent += `[${timestamp}] ${message}\n`;
            debugOutput.scrollTop = debugOutput.scrollHeight;
        }

        function clearDebug() {
            document.getElementById('debugOutput').textContent = '';
        }

        function addToCart(productId) {
            const btn = event.target.closest('button');
            const btnText = btn.querySelector('#btnText');
            const btnSpinner = btn.querySelector('#btnSpinner');
            
            // Show loading state
            btn.disabled = true;
            btnText.textContent = 'Adding...';
            btnSpinner.classList.remove('d-none');
            
            logDebug(`Sending request to add product ${productId} to cart...`);
            
            // Send request to our simple test endpoint
            fetch('test-add-to-cart-simple.php')
                .then(async response => {
                    const text = await response.text();
                    logDebug(`Response status: ${response.status}`);
                    logDebug(`Response body: ${text}`);
                    
                    let data;
                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        throw new Error('Invalid JSON response');
                    }
                    
                    if (!response.ok) {
                        throw new Error(data.message || 'Failed to add to cart');
                    }
                    
                    return data;
                })
                .then(data => {
                    logDebug(`Success: ${data.message}`);
                    logDebug(`Cart count: ${data.cart_count}`);
                    
                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: `Item ${data.action} to cart successfully`,
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2000
                    });
                })
                .catch(error => {
                    console.error('Error:', error);
                    logDebug(`Error: ${error.message}`);
                    
                    // Show error message
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message || 'Failed to add item to cart',
                        footer: 'Check the debug output for more details'
                    });
                })
                .finally(() => {
                    // Reset button state
                    btn.disabled = false;
                    btnText.textContent = 'Add to Cart';
                    btnSpinner.classList.add('d-none');
                });
        }
        
        // Initial debug info
        logDebug('Page loaded. Click "Add to Cart" to test.');
    </script>
</body>
</html>

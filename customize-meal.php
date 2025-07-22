<?php
require_once 'config.php';
require_once 'includes/header.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Initialize items array with all categories
$items = [
    'fruits' => [],
    'vegetables' => [],
    'proteins' => [],
    'grains' => [],
    'dairy' => [],
    'extras' => []
];

try {
    // Check database connection
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
    
    // Fetch all available customize items from database
    $stmt = $conn->query("SELECT * FROM customize_items WHERE is_available = 1 ORDER BY name");
    if ($stmt === false) {
        throw new Exception("Failed to fetch items: " . implode(" ", $conn->errorInfo()));
    }
    
    $customizeItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Categorize items
    foreach ($customizeItems as $item) {
        $category = !empty($item['category']) ? $item['category'] : 'extras';
        if (!isset($items[$category])) {
            $items[$category] = [];
        }
        
        // Set default image if not provided
        $item['image_url'] = !empty($item['image_url']) ? $item['image_url'] : 'images/default-food.jpg';
        
        $items[$category][] = [
            'id' => (int)$item['id'],
            'name' => $item['name'],
            'description' => $item['description'] ?? '',
            'calories' => (float)$item['calories'],
            'protein' => (float)$item['protein'],
            'carbs' => (float)$item['carbs'],
            'fat' => (float)$item['fat'],
            'fiber' => (float)($item['fiber'] ?? 0),
            'sugar' => (float)($item['sugar'] ?? 0),
            'price' => (float)$item['price'],
            'is_vegetarian' => (bool)$item['is_vegetarian'],
            'is_vegan' => (bool)$item['is_vegan'],
            'is_gluten_free' => (bool)$item['is_gluten_free'],
            'image_url' => $item['image_url']
        ];
    }
    
} catch (Exception $e) {
    $error_message = 'Error: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customize Your Meal - BighatMeal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .food-category {
            margin-bottom: 2rem;
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            padding: 1.5rem;
            background-color: #fff;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        .food-category h4 {
            color: #2c3e50;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #f8f9fa;
        }
        .food-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            margin-bottom: 1rem;
            background-color: #f8f9fa;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }
        .food-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        }
        .food-item img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 0.5rem;
            margin-right: 1.5rem;
        }
        .food-details {
            flex: 1;
        }
        .food-details h5 {
            margin: 0 0 0.5rem 0;
            color: #2c3e50;
        }
        .food-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 0.5rem;
        }
        .food-meta span {
            display: inline-flex;
            align-items: center;
        }
        .food-dietary {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        .dietary-tag {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 1rem;
            background-color: #e9ecef;
            color: #495057;
        }
        .veg { background-color: #d4edda; color: #155724; }
        .vegan { background-color: #d1ecf1; color: #0c5460; }
        .gf { background-color: #fff3cd; color: #856404; }
        .add-to-meal {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.25rem;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .add-to-meal:hover {
            background-color: #218838;
        }
        #selected-items {
            max-height: 500px;
            overflow-y: auto;
        }
        .selected-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            background-color: #f8f9fa;
            border-radius: 0.5rem;
        }
        .selected-item img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 0.25rem;
            margin-right: 1rem;
        }
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #dee2e6;
        }
        .summary-total {
            font-weight: bold;
            font-size: 1.1rem;
            margin-top: 1rem;
            padding-top: 0.5rem;
            border-top: 2px solid #dee2e6;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <h1 class="text-center mb-5">Customize Your Meal</h1>
        
        <div class="row">
            <div class="col-lg-8">
                <!-- Fruits Section -->
                <?php renderCategory('fruits', 'Fruits', $items['fruits']); ?>
                
                <!-- Vegetables Section -->
                <?php renderCategory('vegetables', 'Vegetables', $items['vegetables']); ?>
                
                <!-- Proteins Section -->
                <?php renderCategory('proteins', 'Proteins', $items['proteins']); ?>
                
                <!-- Grains Section -->
                <?php renderCategory('grains', 'Grains', $items['grains']); ?>
                
                <!-- Dairy Section -->
                <?php renderCategory('dairy', 'Dairy', $items['dairy']); ?>
                
                <!-- Extras Section -->
                <?php renderCategory('extras', 'Extras', $items['extras']); ?>
            </div>
            
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Your Custom Meal</h4>
                    </div>
                    <div class="card-body">
                        <div id="selected-items">
                            <!-- Selected items will be added here by JavaScript -->
                            <p class="text-muted text-center my-4">Select items to add to your meal</p>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-3">
                            <h5>Selected Items</h5>
                            <div id="item-details" class="mb-3">
                                <!-- Individual item details will be added here -->
                                <p class="text-muted small mb-0">No items selected</p>
                            </div>
                            
                            <div class="border-top pt-3">
                                <h5>Nutrition Summary</h5>
                                <div class="summary-item">
                                    <span>Calories:</span>
                                    <span id="total-calories">0</span>
                                </div>
                                <div class="summary-item">
                                    <span>Protein:</span>
                                    <span id="total-protein">0g</span>
                                </div>
                                <div class="summary-item">
                                    <span>Carbs:</span>
                                    <span id="total-carbs">0g</span>
                                </div>
                                <div class="summary-item">
                                    <span>Fat:</span>
                                    <span id="total-fat">0g</span>
                                </div>
                                <div class="summary-item">
                                    <span>Fiber:</span>
                                    <span id="total-fiber">0g</span>
                                </div>
                                <div class="summary-item">
                                    <span>Sugar:</span>
                                    <span id="total-sugar">0g</span>
                                </div>
                                <div class="summary-item summary-total">
                                    <span>Total Price:</span>
                                    <span id="total-price">₹0.00</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-4">
                            <button id="add-to-cart-btn" class="btn btn-primary btn-lg">
                                <i class="bi bi-cart-plus"></i> Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Helper function to escape HTML to prevent XSS
        function escapeHtml(unsafe) {
            if (!unsafe) return '';
            return unsafe
                .toString()
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }
        
        // Make selectedItems globally available
        window.selectedItems = [];
        
        // Debug function to log element states
        function debugElementStates() {
            console.group('=== Debugging Element States ===');
            const elements = [
                'total-calories', 'total-protein', 'total-carbs',
                'total-fat', 'total-fiber', 'total-sugar', 'total-price'
            ];
            
            elements.forEach(id => {
                const el = document.getElementById(id);
                console.log(`#${id}:`, {
                    exists: !!el,
                    textContent: el ? el.textContent : 'null',
                    parent: el ? el.parentElement : 'null'
                });
            });
            console.groupEnd();
        }
        
        // Expose debug function to global scope for manual calling
        window.debugMealCustomizer = debugElementStates;
        
        // Debug function to inspect an object's properties and types
        function debugObject(obj, name = 'Object') {
            const result = {};
            for (const [key, value] of Object.entries(obj)) {
                result[key] = {
                    value: value,
                    type: typeof value,
                    isNaN: typeof value === 'number' ? isNaN(value) : 'N/A',
                    stringValue: String(value)
                };
            }
            console.log(`${name} details:`, result);
            return result;
        }
        
        // Initialize the application when the DOM is fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM fully loaded, initializing...');
            
            // Initialize the UI
            if (typeof updateSummary === 'function') {
                console.log('Calling updateSummary');
                updateSummary();
            } else {
                console.error('updateSummary function not found!');
            }
            
            // Debug: Log initial state
            console.log('Initial selectedItems:', window.selectedItems);
            debugElementStates();
        });
    
        // Function to add item to meal
        function addToMeal(button, itemData) {
            console.group('=== addToMeal ===');
            console.log('Raw itemData received:', itemData);
            
            // Debug: Check if itemData is a string that needs parsing
            if (typeof itemData === 'string') {
                console.log('itemData is a string, attempting to parse as JSON');
                try {
                    itemData = JSON.parse(itemData);
                    console.log('Successfully parsed itemData:', itemData);
                } catch (e) {
                    console.error('Failed to parse itemData as JSON:', e);
                    console.error('String value was:', itemData);
                    console.groupEnd();
                    return;
                }
            }
            
            // Debug: Log the structure of itemData
            console.log('itemData structure:', Object.keys(itemData).map(key => ({
                key: key,
                type: typeof itemData[key],
                value: itemData[key]
            })));
                
            try {
                // Debug the incoming item data
                const itemDebug = debugObject(itemData, 'itemData');
                
                // Check if required fields exist
                const requiredFields = ['id', 'name', 'price', 'calories', 'protein', 'carbs', 'fat'];
                const missingFields = requiredFields.filter(field => !(field in itemData));
                
                if (missingFields.length > 0) {
                    console.error('Missing required fields:', missingFields);
                    console.error('Available fields:', Object.keys(itemData));
                }
                
                // Create a clean item object with proper types
                const newItem = {
                    id: Number(itemData.id) || Date.now(),
                    name: String(itemData.name || 'Unknown Item'),
                    price: parseFloat(itemData.price) || 0,
                    calories: parseFloat(itemData.calories) || 0,
                    protein: parseFloat(itemData.protein) || 0,
                    carbs: parseFloat(itemData.carbs) || 0,
                    fat: parseFloat(itemData.fat) || 0,
                    fiber: parseFloat(itemData.fiber || 0) || 0,
                    sugar: parseFloat(itemData.sugar || 0) || 0,
                    image_url: String(itemData.image_url || 'images/default-food.jpg'),
                    quantity: 1
                };
                
                console.log('Processed item data:', newItem);
                
                // Validate numeric values
                const numericFields = ['price', 'calories', 'protein', 'carbs', 'fat', 'fiber', 'sugar'];
                const invalidFields = numericFields.filter(field => isNaN(newItem[field]));
                
                if (invalidFields.length > 0) {
                    console.error('Invalid numeric values for fields:', invalidFields);
                    invalidFields.forEach(field => {
                        console.error(`- ${field}:`, itemData[field], '=>', newItem[field]);
                    });
                }
                
                // Check if item is already added
                const existingIndex = selectedItems.findIndex(item => Number(item.id) === Number(newItem.id));
                
                if (existingIndex >= 0) {
                    // Update quantity if item exists
                    selectedItems[existingIndex].quantity += 1;
                    console.log('Updated item quantity:', selectedItems[existingIndex]);
                    console.log('New quantity:', selectedItems[existingIndex].quantity);
                } else {
                    // Add new item
                    selectedItems.push(newItem);
                    console.log('Added new item to selectedItems:', newItem);
                    console.log('Total items in selectedItems:', selectedItems.length);
                }
                
                // Debug: Verify the item was added correctly
                console.log('Current selectedItems after update:', JSON.parse(JSON.stringify(selectedItems)));
                
                console.log('Current selectedItems:', JSON.parse(JSON.stringify(selectedItems)));
                
                // Update the summary with the latest data
                updateSummary();
                
                // Debug: Check if summary elements exist and were updated
                console.log('Checking summary elements after update:');
                const summaryIds = ['total-calories', 'total-protein', 'total-carbs', 'total-fat', 'total-fiber', 'total-sugar', 'total-price'];
                summaryIds.forEach(id => {
                    const el = document.getElementById(id);
                    console.log(`- #${id}:`, {
                        exists: !!el,
                        currentValue: el ? el.textContent : 'null',
                        parent: el ? el.parentElement : 'null'
                    });
                });
                
            } catch (error) {
                console.error('Error in addToMeal:', error);
                console.error('Item data that caused error:', itemData);
            } finally {
                console.groupEnd();
            }
        }
        
        // Function to remove item from meal
        function removeFromMeal(itemId) {
            console.group('=== removeFromMeal ===');
            console.log('Removing item with ID:', itemId);
            
            try {
                const index = selectedItems.findIndex(item => item.id == itemId);
                console.log('Found item at index:', index);
                
                if (index > -1) {
                    if (selectedItems[index].quantity > 1) {
                        // Decrease quantity if more than 1
                        selectedItems[index].quantity -= 1;
                        console.log('Decreased quantity to:', selectedItems[index].quantity);
                    } else {
                        // Remove item if quantity is 1
                        const removed = selectedItems.splice(index, 1);
                        console.log('Removed item:', removed);
                    }
                    
                    // Update the UI
                    updateSummary();
                    console.log('Updated UI after removal');
                } else {
                    console.warn('Item not found in selectedItems:', itemId);
                }
            } catch (error) {
                console.error('Error in removeFromMeal:', error);
            } finally {
                console.groupEnd();
            }
        }
        
        // Function to update the selected items list and summary
        // This function has been removed as its functionality is now handled by updateSummary()
        // which updates both the summary and the selected items list
        
        // Function to format nutritional value
        function formatNutrition(value, unit = '') {
            if (value === 0 || value === '0') return '0' + unit;
            if (!value) return '0' + unit;
            return value.toFixed(1) + unit;
        }

        // Function to directly update the summary values in the DOM
        function updateSummaryValues(totals) {
            console.group('=== Updating Summary Values ===');
            
            try {
                if (!totals || typeof totals !== 'object') {
                    console.error('Invalid totals object:', totals);
                    return;
                }
                
                console.log('Updating summary values with:', totals);
                
                // Helper function to safely update an element
                const updateElement = (id, value, isPrice = false) => {
                    console.group(`Updating element: #${id}`);
                    try {
                        const el = document.getElementById(id);
                        if (!el) {
                            console.error('Element not found:', id);
                            return false;
                        }
                        
                        // Ensure value is a valid number
                        const numValue = parseFloat(value) || 0;
                        
                        let displayValue;
                        if (isPrice) {
                            // Format as currency
                            displayValue = '₹' + numValue.toFixed(2);
                            console.log(`Formatted price: ${displayValue}`);
                        } else if (id === 'total-calories') {
                            // Round calories to nearest whole number
                            displayValue = Math.round(numValue).toString();
                            console.log(`Formatted calories: ${displayValue}`);
                        } else {
                            // Format other nutritional values with 1 decimal place
                            displayValue = numValue.toFixed(1) + 'g';
                            console.log(`Formatted ${id}: ${displayValue}`);
                        }
                        
                        // Update the element
                        el.textContent = displayValue;
                        console.log(`Successfully updated #${id} to:`, displayValue);
                        return true;
                        
                    } catch (e) {
                        console.error(`Error updating #${id}:`, e);
                        return false;
                    } finally {
                        console.groupEnd();
                    }
                };
                
                // Update all summary elements with error handling for each
                const updates = [
                    { id: 'total-calories', value: totals.calories },
                    { id: 'total-protein', value: totals.protein },
                    { id: 'total-carbs', value: totals.carbs },
                    { id: 'total-fat', value: totals.fat },
                    { id: 'total-fiber', value: totals.fiber },
                    { id: 'total-sugar', value: totals.sugar },
                    { id: 'total-price', value: totals.price, isPrice: true }
                ];
                
                updates.forEach(({ id, value, isPrice = false }) => {
                    try {
                        updateElement(id, value, isPrice);
                    } catch (e) {
                        console.error(`Failed to update ${id}:`, e);
                    }
                });
                
                console.log('All summary values updated successfully');
                
            } catch (error) {
                console.error('Error in updateSummaryValues:', error);
            } finally {
                console.groupEnd();
            }
        }
        
        // Function to update the nutrition summary
        function updateSummary() {
            console.group('=== Updating Nutrition Summary ===');
            
            try {
                console.log('updateSummary called with selectedItems:', window.selectedItems);
                
                // Initialize totals with explicit 0 values
                const totals = {
                    calories: 0,
                    protein: 0,
                    carbs: 0,
                    fat: 0,
                    fiber: 0,
                    sugar: 0,
                    price: 0
                };
                
                console.log('Selected items array (raw):', JSON.parse(JSON.stringify(selectedItems)));
                console.log('Number of selected items:', selectedItems.length);
                
                // Debug: Check if selectedItems is an array
                if (!Array.isArray(selectedItems)) {
                    console.error('selectedItems is not an array:', selectedItems);
                    console.groupEnd();
                    return;
                }
                
                const itemDetailsContainer = document.getElementById('item-details');
                
                if (!itemDetailsContainer) {
                    console.error('item-details container not found!');
                    debugElementStates();
                    console.groupEnd();
                    return;
                }
                
                // Clear previous items
                itemDetailsContainer.innerHTML = '';
                
                // If no items selected, reset and return early
                if (selectedItems.length === 0) {
                    console.log('No items selected, resetting summary to zero');
                    itemDetailsContainer.innerHTML = '<p class="text-muted small mb-0">No items selected</p>';
                    updateSummaryValues({
                        calories: 0,
                        protein: 0,
                        carbs: 0,
                        fat: 0,
                        fiber: 0,
                        sugar: 0,
                        price: 0
                    });
                    return;
                }
                
                console.log('Processing', selectedItems.length, 'items...');
                
                // Create a container for the items list
                const itemsList = document.createElement('div');
                itemsList.className = 'list-group list-group-flush mb-3';
                
                // Process each selected item
                selectedItems.forEach((item, index) => {
                    console.group(`Processing item ${index + 1}: ${item.name || 'Unnamed Item'}`);
                    
                    try {
                        // Debug log the raw item data
                        console.log('Raw item data:', JSON.parse(JSON.stringify(item)));
                        
                        // Parse and validate item values with detailed logging
                        const quantity = Math.max(1, parseFloat(item.quantity) || 1);
                        const calories = parseFloat(item.calories) || 0;
                        const protein = parseFloat(item.protein) || 0;
                        const carbs = parseFloat(item.carbs) || 0;
                        const fat = parseFloat(item.fat) || 0;
                        const fiber = parseFloat(item.fiber || 0) || 0;
                        const sugar = parseFloat(item.sugar || 0) || 0;
                        const price = parseFloat(item.price) || 0;
                        
                        console.log('Parsed values:', {
                            quantity, calories, protein, carbs, fat, fiber, sugar, price
                        });
                        
                        // Calculate item totals
                        const itemTotals = {
                            calories: calories * quantity,
                            protein: protein * quantity,
                            carbs: carbs * quantity,
                            fat: fat * quantity,
                            fiber: fiber * quantity,
                            sugar: sugar * quantity,
                            price: price * quantity
                        };
                        
                        // Debug log item details
                        console.log('Item values:', {
                            name: item.name,
                            quantity: quantity,
                            nutrition: {
                                calories: { perItem: calories, total: itemTotals.calories },
                                protein: { perItem: protein, total: itemTotals.protein },
                                carbs: { perItem: carbs, total: itemTotals.carbs },
                                fat: { perItem: fat, total: itemTotals.fat },
                                fiber: { perItem: fiber, total: itemTotals.fiber },
                                sugar: { perItem: sugar, total: itemTotals.sugar },
                                price: { perItem: price, total: itemTotals.price }
                            }
                        });
                        
                        // Update running totals with type safety
                        Object.keys(totals).forEach(key => {
                            const value = parseFloat(totals[key] || 0) + (isNaN(itemTotals[key]) ? 0 : itemTotals[key]);
                            totals[key] = Math.max(0, value); // Ensure non-negative values
                        });
                        
                        // Create and append item element
                        const itemElement = document.createElement('div');
                        itemElement.className = 'list-group-item border-0 px-0 py-2';
                        
                        // Generate item HTML safely
                        itemElement.innerHTML = `
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <div class="d-flex align-items-center">
                                    <button class="btn btn-sm btn-outline-danger me-2" 
                                            onclick="removeFromMeal('${item.id}'); return false;"
                                            title="Remove from meal">
                                        <i class="bi bi-dash"></i>
                                    </button>
                                    <div>
                                        <h6 class="mb-0">
                                            ${escapeHtml(item.name || 'Unknown Item')} 
                                            <span class="text-muted">×${quantity}</span>
                                        </h6>
                                        <div class="text-muted small">
                                            ${formatNutrition(calories, 'cal')} • 
                                            ${formatNutrition(protein, 'g protein')} • 
                                            ${formatNutrition(carbs, 'g carbs')}
                                        </div>
                                        <div class="small text-muted">
                                            ${formatNutrition(fat, 'g fat')} • 
                                            ${formatNutrition(fiber, 'g fiber')} • 
                                            ${formatNutrition(sugar, 'g sugar')}
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold">₹${(price * quantity).toFixed(2)}</div>
                                    <div class="small text-muted">₹${price.toFixed(2)} each</div>
                                </div>
                            </div>
                        `;
                        
                        itemsList.appendChild(itemElement);
                        console.log(`Added item ${index + 1} to the list`);
                        
                    } catch (error) {
                        console.error('Error processing item:', error);
                        console.error('Problematic item data:', item);
                    } finally {
                        console.groupEnd();
                    }
                });
                
                // Append all items to container
                itemDetailsContainer.appendChild(itemsList);
                
                // Debug log final totals
                console.log('Final calculated totals:', totals);
                
                // Debug: Log the final totals before updating UI
                console.log('Final calculated totals:', totals);
                
                // Update the UI with the calculated totals
                console.log('Calling updateSummaryValues with totals:', totals);
                updateSummaryValues(totals);
                
                // Debug: Verify the summary elements were updated
                console.log('Verifying summary elements after update:');
                const summaryIds = ['total-calories', 'total-protein', 'total-carbs', 'total-fat', 'total-fiber', 'total-sugar', 'total-price'];
                summaryIds.forEach(id => {
                    const el = document.getElementById(id);
                    console.log(`- #${id}:`, {
                        exists: !!el,
                        currentValue: el ? el.textContent : 'null'
                    });
                });
                
                // Update the add to cart button state
                const addToCartBtn = document.getElementById('add-to-cart-btn');
                if (addToCartBtn) {
                    addToCartBtn.disabled = selectedItems.length === 0;
                    console.log('Add to cart button state updated:', !addToCartBtn.disabled);
                }
                
            } catch (error) {
                console.error('Error in updateSummary:', error);
            } finally {
                console.groupEnd();
            }
        }
        
        // Handle add to cart
        const addToCartBtn = document.getElementById('add-to-cart-btn');
        if (addToCartBtn) {
            addToCartBtn.addEventListener('click', function() {
                if (selectedItems.length === 0) {
                    alert('Please add items to your meal first');
                    return;
                }
                
                const button = this;
                button.disabled = true;
                button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Adding...';
                
                // Prepare items for the cart
                const cartItems = selectedItems.map(item => {
                    // Calculate total price for the item (price * quantity)
                    const itemPrice = parseFloat(item.price) || 0;
                    const itemQuantity = parseInt(item.quantity) || 1;
                    const totalPrice = itemPrice * itemQuantity;
                    
                    return {
                        name: item.name,
                        quantity: itemQuantity,
                        price: itemPrice,  // Price per unit
                        total_price: totalPrice,  // Total price (price * quantity)
                        calories: parseFloat(item.calories) * itemQuantity || 0,
                        protein: parseFloat(item.protein) * itemQuantity || 0,
                        carbs: parseFloat(item.carbs) * itemQuantity || 0,
                        fat: parseFloat(item.fat) * itemQuantity || 0,
                        fiber: parseFloat(item.fiber) * itemQuantity || 0,
                        image_url: item.image_url || 'images/default-food.jpg'
                    };
                });
                
                // Send to server
                fetch('api/add-to-cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        type: 'custom-meal',
                        items: cartItems
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        alert('Meal added to cart successfully!');
                        
                        // Update cart count in header
                        const cartCount = document.querySelector('.cart-count');
                        if (cartCount) {
                            const currentCount = parseInt(cartCount.textContent) || 0;
                            cartCount.textContent = currentCount + 1;
                            cartCount.style.display = 'inline-block';
                        }
                        
                        // Optionally redirect to cart or reset the form
                        // window.location.href = 'cart.php';
                    } else {
                        throw new Error(data.message || 'Failed to add to cart');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error adding to cart: ' + error.message);
                })
                .finally(() => {
                    button.disabled = false;
                    button.innerHTML = '<i class="bi bi-cart-plus"></i> Add to Cart';
                });
            });
        }
    </script>
</body>
</html>

<?php
// Helper function to render a food category section
function renderCategory($id, $title, $items) {
    if (empty($items)) return;
    ?>
    <div class="food-category mb-4" id="<?php echo $id; ?>-section">
        <h4><?php echo htmlspecialchars($title); ?></h4>
        <div class="row">
            <?php foreach ($items as $item): ?>
                <div class="col-md-6 mb-4">
                    <div class="food-item">
                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                        <div class="food-details">
                            <h5><?php echo htmlspecialchars($item['name']); ?></h5>
                            <?php if (!empty($item['description'])): ?>
                                <p class="small text-muted mb-2"><?php echo htmlspecialchars($item['description']); ?></p>
                            <?php endif; ?>
                            <div class="food-meta">
                                <span><i class="bi bi-fire"></i> <?php echo $item['calories']; ?> cal</span>
                                <span><i class="bi bi-droplet"></i> <?php echo $item['protein']; ?>g protein</span>
                                <span><i class="bi bi-egg-fried"></i> <?php echo $item['carbs']; ?>g carbs</span>
                                <span><i class="bi bi-droplet-half"></i> <?php echo $item['fat']; ?>g fat</span>
                            </div>
                            <div class="food-dietary">
                                <?php if ($item['is_vegetarian']): ?>
                                    <span class="dietary-tag veg"><i class="bi bi-egg"></i> Veg</span>
                                <?php endif; ?>
                                <?php if ($item['is_vegan']): ?>
                                    <span class="dietary-tag vegan"><i class="bi bi-leaf"></i> Vegan</span>
                                <?php endif; ?>
                                <?php if ($item['is_gluten_free']): ?>
                                    <span class="dietary-tag gf"><i class="bi bi-check-circle"></i> GF</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php
                        // Prepare item data with all required fields
                        // Explicitly cast all numeric values to ensure they're treated as numbers in JavaScript
                        $itemData = [
                            'id' => (int)$item['id'],
                            'name' => $item['name'],
                            'price' => (float)$item['price'],
                            'calories' => (float)$item['calories'],
                            'protein' => (float)$item['protein'],
                            'carbs' => (float)$item['carbs'],
                            'fat' => (float)$item['fat'],
                            'fiber' => isset($item['fiber']) ? (float)$item['fiber'] : 0,
                            'sugar' => isset($item['sugar']) ? (float)$item['sugar'] : 0,
                            'image_url' => $item['image_url']
                        ];
                        
                        // Convert PHP array to JSON and escape for JavaScript
                        $jsonData = json_encode($itemData, JSON_NUMERIC_CHECK);
                        $escapedData = htmlspecialchars($jsonData, ENT_QUOTES, 'UTF-8');
                        ?>
                        <button class="add-to-meal" 
                                onclick="addToMeal(this, <?php echo $escapedData; ?>); return false;"
                                data-item='<?php echo $escapedData; ?>'>
                            + Add
                        </button>
                        <script>
                        // Debug: Verify the data is properly encoded
                        console.log('Item data for <?php echo $item['id']; ?>:', <?php echo $jsonData; ?>);
                        </script>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}
?>

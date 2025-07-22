document.addEventListener('DOMContentLoaded', function() {
    let selectedItems = [];
    
    // Function to get item details from select element
    function getItemDetails(selectElement) {
        if (!selectElement || !selectElement.selectedOptions[0]) return null;
        
        const option = selectElement.selectedOptions[0];
        if (!option.value) return null;
        
        return {
            id: option.dataset.id,
            name: option.value,
            calories: parseFloat(option.dataset.calories) || 0,
            protein: parseFloat(option.dataset.protein) || 0,
            carbs: parseFloat(option.dataset.carbs) || 0,
            fat: parseFloat(option.dataset.fat) || 0,
            fiber: parseFloat(option.dataset.fiber) || 0,
            sugar: parseFloat(option.dataset.sugar) || 0,
            price: parseFloat(option.dataset.price) || 0,
            is_vegetarian: option.dataset.vegetarian === '1',
            is_vegan: option.dataset.vegan === '1',
            is_gluten_free: option.dataset.glutenFree === '1',
            image_url: option.dataset.imageUrl || ''
        };
    }

    // Update summary and selected items list
    function updateSummary() {
        const selectedItemsList = document.getElementById('selected-items');
        if (!selectedItemsList) return; // Exit if element not found

        selectedItemsList.innerHTML = '';

        let totalCalories = 0;
        let totalProtein = 0;
        let totalCarbs = 0;
        let totalFat = 0;
        let totalFiber = 0;
        let totalSugar = 0;
        let totalPrice = 0;

        selectedItems.forEach((item, index) => {
            totalCalories += item.calories || 0;
            totalProtein += item.protein || 0;
            totalCarbs += item.carbs || 0;
            totalFat += item.fat || 0;
            totalFiber += item.fiber || 0;
            totalSugar += item.sugar || 0;
            totalPrice += item.price || 0;

            // Create list item for selected item
            const li = document.createElement('li');
            li.className = 'list-group-item d-flex justify-content-between align-items-center';
            li.dataset.index = index;

            // Item name and details
            const itemDetails = document.createElement('div');
            itemDetails.className = 'd-flex align-items-center';

            // Add item image if available
            if (item.image_url) {
                const img = document.createElement('img');
                img.src = item.image_url;
                img.alt = item.name;
                img.style.width = '40px';
                img.style.height = '40px';
                img.style.objectFit = 'cover';
                img.className = 'me-2';
                itemDetails.appendChild(img);
            }

            const textDiv = document.createElement('div');
            textDiv.innerHTML = `
                <strong>${item.name}</strong>
                <div class="text-muted small">
                    ${item.calories ? Math.round(item.calories) : 0} cal • 
                    ${item.protein ? item.protein.toFixed(1) : 0}g protein • 
                    ₹${item.price ? item.price.toFixed(2) : '0.00'}
                </div>
            `;
            itemDetails.appendChild(textDiv);

            // Remove button
            const removeBtn = document.createElement('button');
            removeBtn.className = 'btn btn-sm btn-outline-danger';
            removeBtn.innerHTML = '&times;';
            removeBtn.onclick = (e) => {
                e.preventDefault();
                selectedItems.splice(index, 1);
                updateSummary();
            };

            li.appendChild(itemDetails);
            li.appendChild(removeBtn);
            selectedItemsList.appendChild(li);
        });

        // Update summary cards
        const updateElement = (id, value) => {
            const el = document.getElementById(id);
            if (el) el.textContent = value;
        };

        updateElement('total-calories', Math.round(totalCalories));
        updateElement('total-protein', totalProtein.toFixed(1) + 'g');
        updateElement('total-carbs', totalCarbs.toFixed(1) + 'g');
        updateElement('total-fat', totalFat.toFixed(1) + 'g');
        updateElement('total-fiber', totalFiber.toFixed(1) + 'g');
        updateElement('total-sugar', totalSugar.toFixed(1) + 'g');
        updateElement('total-price', '₹' + totalPrice.toFixed(2));

        // Update checkout button state
        const checkoutBtn = document.getElementById('checkout-btn');
        if (checkoutBtn) {
            checkoutBtn.disabled = selectedItems.length === 0;
        }
    }

    // Handle select changes
    document.querySelectorAll('.item-select').forEach(select => {
        select.addEventListener('change', function() {
            const item = getItemDetails(this);
            if (!item) return;

            const category = this.dataset.category;
            const itemId = item.id;

            // Check if this item is already selected in another category
            const existingIndex = selectedItems.findIndex(i => i.id === itemId);
            if (existingIndex >= 0) {
                // Remove from existing category
                selectedItems.splice(existingIndex, 1);
            }

            // Add to selected items with category
            item.category = category;
            selectedItems.push(item);

            // Reset the select
            this.selectedIndex = 0;

            updateSummary();
        });
    });

    // Handle form submission
    const customizeForm = document.getElementById('customize-form');
    if (customizeForm) {
        customizeForm.addEventListener('submit', function(e) {
            e.preventDefault();

            if (selectedItems.length === 0) {
                alert('Please select at least one item');
                return;
            }

            // Prepare order data
            const orderData = {
                items: selectedItems.map(item => ({
                    id: item.id,
                    name: item.name,
                    price: item.price,
                    quantity: 1 // Default quantity
                })),
                total: selectedItems.reduce((sum, item) => sum + item.price, 0)
            };

            console.log('Selected items:', selectedItems);

            // For demo purposes, just show an alert
            alert('Order placed successfully!');

            // Reset the form
            selectedItems = [];
            updateSummary();
        });
    }

    // Initialize the summary
    updateSummary();
});

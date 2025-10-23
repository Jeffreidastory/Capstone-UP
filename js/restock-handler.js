document.addEventListener('DOMContentLoaded', function() {
    const restockForm = document.getElementById('restockForm');
    const productSelect = document.getElementById('product');
    const quantityInput = document.getElementById('quantity');
    const unitCostInput = document.getElementById('unitCost');
    const expirationDateInput = document.getElementById('expirationDate');
    const stockInfo = document.getElementById('stockInfo');
    const expirationStatus = document.getElementById('expirationStatus');
    const uomDisplay = document.getElementById('uomDisplay');

    // Function to format date as YYYY-MM-DD
    function formatDate(date) {
        return date.toISOString().split('T')[0];
    }

    // Set minimum date for expiration date input
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    expirationDateInput.min = formatDate(tomorrow);

    // Function to show notification
    function showNotification(message, type) {
        // Remove any existing notifications
        const existingNotifications = document.querySelectorAll('.notification');
        existingNotifications.forEach(notification => notification.remove());

        // Create new notification
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        
        const icon = type === 'success' ? '✓' : '⚠';
        
        notification.innerHTML = `
            <span class="notification-icon">${icon}</span>
            <span class="notification-message">${message}</span>
            <button class="notification-close" onclick="this.parentElement.remove()">×</button>
        `;

        document.body.appendChild(notification);

        // Auto dismiss after 5 seconds
        setTimeout(() => {
            notification.style.animation = 'slideIn 0.5s ease-out reverse';
            setTimeout(() => notification.remove(), 500);
        }, 5000);

        // Add click handler to close button
        const closeButton = notification.querySelector('.notification-close');
        closeButton.addEventListener('click', () => {
            notification.style.animation = 'slideIn 0.5s ease-out reverse';
            setTimeout(() => notification.remove(), 500);
        });
    }

    // Update stock info when product is selected
    productSelect.addEventListener('change', async function() {
        const productId = this.value;
        if (!productId) {
            stockInfo.textContent = '';
            uomDisplay.textContent = '';
            return;
        }

        try {
            const response = await fetch('../js/check_stock.php?product_id=' + productId);
            const data = await response.json();

            if (data.success) {
                stockInfo.textContent = `Current Stock: ${data.current_stock} ${data.uom}`;
                uomDisplay.textContent = data.uom;
            } else {
                showAlert('Error fetching stock information', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showAlert('Error fetching stock information', 'error');
        }
    });

    // Validate expiration date
    expirationDateInput.addEventListener('change', function() {
        const selectedDate = new Date(this.value);
        const today = new Date();
        const threeMonthsFromNow = new Date();
        threeMonthsFromNow.setMonth(threeMonthsFromNow.getMonth() + 3);

        if (selectedDate <= today) {
            expirationStatus.textContent = 'Expiration date must be in the future';
            expirationStatus.className = 'expiration-status warning';
            this.setCustomValidity('Expiration date must be in the future');
        } else if (selectedDate <= threeMonthsFromNow) {
            expirationStatus.textContent = 'Warning: Product will expire within 3 months';
            expirationStatus.className = 'expiration-status warning';
            this.setCustomValidity('');
        } else {
            expirationStatus.textContent = 'Expiration date is valid';
            expirationStatus.className = 'expiration-status good';
            this.setCustomValidity('');
        }
    });

    // Calculate total cost
    function updateTotalCost() {
        const quantity = parseFloat(quantityInput.value) || 0;
        const unitCost = parseFloat(unitCostInput.value) || 0;
        const totalCostElement = document.getElementById('totalCost');
        const total = quantity * unitCost;
        totalCostElement.textContent = total.toFixed(2);
    }

    quantityInput.addEventListener('input', updateTotalCost);
    unitCostInput.addEventListener('input', updateTotalCost);

    // Form submission handler
    restockForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        console.log('Form submitted');

        // Log form field values
        console.log('Form values:', {
            product: productSelect.value,
            quantity: quantityInput.value,
            unitCost: unitCostInput.value,
            expirationDate: expirationDateInput.value
        });

        // Validate inputs
        if (!productSelect.value) {
            showNotification('Please select a product', 'error');
            return;
        }

        if (!quantityInput.value || quantityInput.value <= 0) {
            showNotification('Quantity must be greater than 0', 'error');
            return;
        }

        if (!unitCostInput.value || unitCostInput.value <= 0) {
            showNotification('Unit cost must be greater than 0', 'error');
            return;
        }

        // Show loading state
        const submitButton = this.querySelector('button[type="submit"]');
        const originalText = submitButton.innerHTML;
        submitButton.disabled = true;
        submitButton.innerHTML = 'Recording...';

        // Prepare form data
        const formData = new FormData();
        formData.append('product_id', productSelect.value);
        formData.append('restock_quantity', quantityInput.value);
        formData.append('cost_per_unit', unitCostInput.value);
        formData.append('expiration_date', expirationDateInput.value);

        // Log the data being sent
        console.log('Sending data:', {
            product_id: productSelect.value,
            restock_quantity: quantityInput.value,
            cost_per_unit: unitCostInput.value,
            expiration_date: expirationDateInput.value
        });

        try {
            const response = await fetch('../kfood_admin/process_restock.php', {
                method: 'POST',
                body: formData
            });
            
            // Log raw response
            console.log('Raw response:', await response.clone().text());

            const result = await response.json();

            if (result.success) {
                showNotification('Stock recorded successfully! ✨', 'success');
                
                // Clear form
                this.reset();
                stockInfo.textContent = '';
                uomDisplay.textContent = '';
                
                // Refresh the records table without page reload
                refreshRecordsTable();
            } else {
                showNotification(result.message || 'Error recording stock', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('An error occurred while processing the request', 'error');
        } finally {
            // Restore button state
            submitButton.disabled = false;
            submitButton.innerHTML = originalText;
        }
    });

    // Function to refresh records table
    function refreshRecordsTable() {
        fetch('../kfood_admin/get_restock_records.php')
            .then(response => response.json())
            .then(data => {
                const tableBody = document.querySelector('.restock-table tbody');
                tableBody.innerHTML = '';

                data.forEach(record => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${record.product_name}</td>
                        <td>${record.current_stock} ${record.uom}</td>
                        <td>${record.quantity} ${record.uom}</td>
                        <td>₱${parseFloat(record.unit_cost).toFixed(2)}</td>
                        <td>₱${parseFloat(record.total_cost).toFixed(2)}</td>
                        <td>${record.expiration_date}</td>
                        <td>${record.timestamp}</td>
                        <td><span class="status-badge status-${record.status.toLowerCase()}">${record.status}</span></td>
                    `;
                    tableBody.appendChild(row);
                });
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Error fetching restock records', 'error');
            });
    }

    // Initial load of records
    refreshRecordsTable();
});
// Initialize form handling when document is ready
document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const restockForm = document.getElementById('restockForm');
    const productSelect = document.getElementById('product');
    const quantityInput = document.getElementById('restock_quantity');
    const costInput = document.getElementById('cost_per_unit');
    const expirationInput = document.getElementById('expiration_date');

    // Initialize min date for expiration
    const today = new Date();
    const minDate = new Date(today);
    minDate.setDate(today.getDate() + 90); // Set minimum date to 90 days from today
    expirationInput.min = minDate.toISOString().split('T')[0];

    // Form submission
    restockForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        // Basic validation
        if (!productSelect.value || !quantityInput.value || !costInput.value || !expirationInput.value) {
            showNotification('Error', 'All fields are required', 'error');
            return;
        }

        if (parseFloat(quantityInput.value) <= 0) {
            showNotification('Error', 'Quantity must be greater than 0', 'error');
            return;
        }

        if (parseFloat(costInput.value) <= 0) {
            showNotification('Error', 'Cost per unit must be greater than 0', 'error');
            return;
        }

        // Create form data
        const formData = new FormData();
        formData.append('product_id', productSelect.value);
        formData.append('restock_quantity', quantityInput.value);
        formData.append('cost_per_unit', costInput.value);
        formData.append('expiration_date', expirationInput.value);

        try {
            console.log('Submitting form data:', Object.fromEntries(formData));
            
            const response = await fetch('process_restock.php', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            console.log('Server response:', data);

            if (data.success) {
                showNotification(
                    'Success', 
                    `Successfully added ${data.restock_quantity} units of ${data.product_name}`,
                    'success'
                );

                // Reset form
                restockForm.reset();
                uomDisplay.textContent = '';
                document.getElementById('currentStock').textContent = '';
                expirationStatus.textContent = '';

                // Close modal if needed
                const modal = document.querySelector('.restock-modal-content');
                if (modal) {
                    modal.style.display = 'none';
                }

                // Refresh restocking records table
                loadRestockRecords();

                // Redirect to Inventory section after a short delay so user sees the success notification
                setTimeout(() => {
                    window.location.href = 'admin_pg.php?section=inventory';
                }, 1200);
            } else {
                showNotification('Error', data.message, 'error');
            }
        } catch (error) {
            showNotification('Error', 'Failed to process restocking', 'error');
        }
    });
});

// Function to show/hide restock form
function showRestockForm() {
    const formContainer = document.querySelector('.restock-modal-content');
    if (formContainer) {
        formContainer.style.display = 'block';
    }
}

function cancelRestock() {
    const formContainer = document.querySelector('.restock-modal-content');
    if (formContainer) {
        formContainer.style.display = 'none';
        document.getElementById('restockForm').reset();
        document.getElementById('uomDisplay').textContent = '';
        document.getElementById('currentStock').textContent = '';
        document.getElementById('expirationStatus').textContent = '';
    }
}

// Function to reload restocking records
function loadRestockRecords() {
    fetch('get_restock_records.php')
        .then(response => response.json())
        .then(data => {
            const records = Array.isArray(data) ? data : (data.records || []);
            const tbody = document.querySelector('.restock-table tbody');
            if (tbody) {
                tbody.innerHTML = records.map(record => `
                    <tr>
                        <td>${new Date(record.restock_date).toLocaleDateString()}</td>
                        <td>${record.product_name}</td>
                        <td>${record.restock_quantity} ${record.unit_measurement}</td>
                        <td>₱${parseFloat(record.cost_per_unit).toFixed(2)}</td>
                        <td>₱${parseFloat(record.final_price).toFixed(2)}</td>
                        <td>${new Date(record.expiration_date).toLocaleDateString()}</td>
                        <td><span class="status-badge status-${record.status.toLowerCase()}">${record.status}</span></td>
                    </tr>
                `).join('');
            }
        });
}
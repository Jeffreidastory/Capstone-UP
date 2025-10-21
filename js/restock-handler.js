document.addEventListener('DOMContentLoaded', function() {document.addEventListener('DOMContentLoaded', function() {document.addEventListener('DOMContentLoaded', function() {document.addEventListener('DOMContentLoaded', function() {

    const restockForm = document.getElementById('restockForm');

        const restockForm = document.getElementById('restockForm');

    if (restockForm) {

        restockForm.addEventListener('submit', function(e) {    console.log('Looking for restock form...');    // Get form element    const restockForm = document.getElementById('restockForm');

            e.preventDefault();

            

            const formData = new FormData(restockForm);

                if (restockForm) {    const restockForm = document.getElementById('restockForm');    const productSelect = document.getElementById('product');

            fetch('process_restock.php', {

                method: 'POST',        console.log('Restock form found');

                body: formData

            })        restockForm.addEventListener('submit', async function(e) {        const quantityInput = document.getElementById('quantity');  // Now matches the form field name

            .then(response => response.json())

            .then(data => {            e.preventDefault();

                if (data.success) {

                    alert('Stock recorded successfully!');            console.log('Form submitted');    if (restockForm) {    const unitCostInput = document.getElementById('unitCost');  // Now matches the form field name

                    restockForm.reset();

                    location.reload();

                } else {

                    alert(data.message || 'Error recording stock');            const formData = new FormData(this);        restockForm.addEventListener('submit', async function(e) {    const expirationDateInput = document.getElementById('expirationDate');  // Now matches the form field name

                }

            })            

            .catch(error => {

                console.error('Error:', error);            // Debug log form data            e.preventDefault();    const stockInfo = document.getElementById('stockInfo');

                alert('An error occurred while processing the request');

            });            for (let pair of formData.entries()) {

        });

    }                console.log(pair[0] + ': ' + pair[1]);                const expirationStatus = document.getElementById('expirationStatus');

});
            }

            // Get form data    const uomDisplay = document.getElementById('uomDisplay');

            try {

                console.log('Sending request...');            const formData = new FormData(this);

                const response = await fetch('process_restock.php', {

                    method: 'POST',                // Function to format date as YYYY-MM-DD

                    body: formData

                });            try {    function formatDate(date) {

                

                console.log('Got response');                const response = await fetch('process_restock.php', {        return date.toISOString().split('T')[0];

                const data = await response.json();

                console.log('Response data:', data);                    method: 'POST',    }



                if (data.success) {                    body: formData

                    alert('Stock recorded successfully!');

                    this.reset();                });    // Set minimum date for expiration date input

                    loadRestockRecords();

                } else {                    const tomorrow = new Date();

                    alert(data.message || 'Error recording stock');

                }                const data = await response.json();    tomorrow.setDate(tomorrow.getDate() + 1);

            } catch (error) {

                console.error('Error:', error);                    expirationDateInput.min = formatDate(tomorrow);

                alert('An error occurred while processing the request');

            }                if (data.success) {

        });

    } else {                    alert('Stock recorded successfully!');    // Function to show notification

        console.error('Restock form not found!');

    }                    // Clear form    function showNotification(message, type) {



    // Function to load restock records                    this.reset();        // Remove any existing notifications

    function loadRestockRecords() {

        console.log('Loading restock records...');                    // Refresh the table        const existingNotifications = document.querySelectorAll('.notification');

        fetch('get_restock_records.php')

            .then(response => {                    loadRestockRecords();        existingNotifications.forEach(notification => notification.remove());

                console.log('Got response from get_restock_records.php');

                return response.json();                } else {

            })

            .then(data => {                    alert(data.message || 'Error recording stock');        // Create new notification

                console.log('Records data:', data);

                const tableBody = document.querySelector('.restock-table tbody');                }        const notification = document.createElement('div');

                if (!tableBody) {

                    console.error('Table body element not found!');            } catch (error) {        notification.className = `notification ${type}`;

                    return;

                }                console.error('Error:', error);        



                tableBody.innerHTML = '';                alert('An error occurred while processing the request');        const icon = type === 'success' ? '✓' : '⚠';

                

                if (Array.isArray(data)) {            }        

                    data.forEach(record => {

                        const row = document.createElement('tr');        });        notification.innerHTML = `

                        const total_cost = record.restock_quantity * record.cost_per_unit;

                        row.innerHTML = `    }            <span class="notification-icon">${icon}</span>

                            <td>${record.product_name}</td>

                            <td>${record.current_stock}</td>            <span class="notification-message">${message}</span>

                            <td>${record.restock_quantity}</td>

                            <td>₱${parseFloat(record.cost_per_unit).toFixed(2)}</td>    // Function to load restock records            <button class="notification-close" onclick="this.parentElement.remove()">×</button>

                            <td>₱${total_cost.toFixed(2)}</td>

                            <td>${new Date(record.expiration_date).toLocaleDateString()}</td>    function loadRestockRecords() {        `;

                            <td>${new Date(record.restock_date).toLocaleDateString()}</td>

                            <td>${record.status}</td>        fetch('get_restock_records.php')

                        `;

                        tableBody.appendChild(row);            .then(response => response.json())        document.body.appendChild(notification);

                    });

                } else {            .then(data => {

                    console.error('Received data is not an array:', data);

                }                const tableBody = document.querySelector('.restock-table tbody');        // Auto dismiss after 5 seconds

            })

            .catch(error => {                if (!tableBody) return;        setTimeout(() => {

                console.error('Error loading records:', error);

            });            notification.style.animation = 'slideIn 0.5s ease-out reverse';

    }

                tableBody.innerHTML = '';            setTimeout(() => notification.remove(), 500);

    // Initial load of records

    loadRestockRecords();                        }, 5000);

});
                data.forEach(record => {

                    const row = document.createElement('tr');        // Add click handler to close button

                    row.innerHTML = `        const closeButton = notification.querySelector('.notification-close');

                        <td>${record.product_name}</td>        closeButton.addEventListener('click', () => {

                        <td>${record.current_stock}</td>            notification.style.animation = 'slideIn 0.5s ease-out reverse';

                        <td>${record.restock_quantity}</td>            setTimeout(() => notification.remove(), 500);

                        <td>₱${parseFloat(record.cost_per_unit).toFixed(2)}</td>        });

                        <td>₱${(record.restock_quantity * record.cost_per_unit).toFixed(2)}</td>    }

                        <td>${new Date(record.expiration_date).toLocaleDateString()}</td>

                        <td>${new Date(record.restock_date).toLocaleDateString()}</td>    // Update stock info when product is selected

                        <td>${record.status}</td>    productSelect.addEventListener('change', async function() {

                    `;        const productId = this.value;

                    tableBody.appendChild(row);        if (!productId) {

                });            stockInfo.textContent = '';

            })            uomDisplay.textContent = '';

            .catch(error => {            return;

                console.error('Error:', error);        }

            });

    }        try {

            const response = await fetch('../js/check_stock.php?product_id=' + productId);

    // Load records when page loads            const data = await response.json();

    loadRestockRecords();

});            if (data.success) {
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

        // Validate inputs
        if (!productSelect.value) {
            showNotification('Please select a product', 'error');
            return;
        }

        if (quantityInput.value <= 0) {
            showNotification('Quantity must be greater than 0', 'error');
            return;
        }

        if (unitCostInput.value <= 0) {
            showNotification('Unit cost must be greater than 0', 'error');
            return;
        }

        // Show loading state
        const submitButton = this.querySelector('button[type="submit"]');
        const originalText = submitButton.innerHTML;
        submitButton.disabled = true;
        submitButton.innerHTML = 'Recording...';

        // Prepare form data
        const formData = new FormData(this);

        try {
            const response = await fetch('../kfood_admin/process_restock.php', {
                method: 'POST',
                body: formData
            });

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
                    const total_cost = record.restock_quantity * record.cost_per_unit;
                    row.innerHTML = `
                        <td>${record.product_name}</td>
                        <td>${record.current_stock} ${record.unit_measurement}</td>
                        <td>${record.restock_quantity} ${record.unit_measurement}</td>
                        <td>₱${parseFloat(record.cost_per_unit).toFixed(2)}</td>
                        <td>₱${total_cost.toFixed(2)}</td>
                        <td>${new Date(record.expiration_date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })}</td>
                        <td>${new Date(record.restock_date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })}</td>
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
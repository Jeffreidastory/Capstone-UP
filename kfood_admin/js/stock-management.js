// Function to format date for display
function formatDate(dateString) {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    }).format(date);
}

// Function to update the stock history table
function updateStockHistory(productId) {
    fetch(`get_stock_history.php?product_id=${productId}`)
        .then(response => response.json())
        .then(data => {
            const historyTable = document.querySelector('#stockHistoryTable tbody');
            if (!historyTable) return;

            historyTable.innerHTML = data.map(record => {
                const expirationInfo = record.expiration_batch ? 
                    `<div>Exp: ${record.expiration_batch}</div>` : '';
                const costInfo = record.cost_per_unit ? 
                    `<div>Cost: ₱${parseFloat(record.cost_per_unit).toFixed(2)}</div>` : '';
                
                return `
                    <tr>
                        <td>${record.date}</td>
                        <td>
                            <div>${record.type === 'stock_in' ? 'Stock In' : 'Stock Out'}</div>
                            ${expirationInfo}
                            ${costInfo}
                        </td>
                        <td>${record.quantity}</td>
                        <td>${record.previous_stock}</td>
                        <td>${record.new_stock}</td>
                    </tr>
                `;
            }).join('');
        })
        .catch(error => console.error('Error:', error));
}

// Function to check for expiring stock
function checkExpiringStock() {
    fetch('../includes/check_expiring_stock.php')
        .then(response => response.json())
        .then(data => {
            if (data.length > 0) {
                showExpiringStockNotification(data);
            }
        })
        .catch(error => console.error('Error:', error));
}

// Function to display expiring stock notification
function showExpiringStockNotification(items) {
    const container = document.createElement('div');
    container.className = 'expiring-stock-alert';
    container.innerHTML = `
        <h3>Products Nearing Expiration</h3>
        <div class="expiring-items">
            ${items.map(item => `
                <div class="expiring-item">
                    <div class="item-name">${item.product_name}</div>
                    <div class="item-details">
                        <span>${item.batch_quantity} ${item.unit_measurement}</span>
                        <span class="expiry-date">Expires in ${item.days_until_expiry} days</span>
                    </div>
                </div>
            `).join('')}
        </div>
    `;

    // Add to notifications area or create modal
    const notificationsArea = document.getElementById('notificationList');
    if (notificationsArea) {
        notificationsArea.prepend(container);
    }
}

// Initialize checks when document is ready
document.addEventListener('DOMContentLoaded', function() {
    // Check for expiring stock periodically
    checkExpiringStock();
    setInterval(checkExpiringStock, 1800000); // Check every 30 minutes
});
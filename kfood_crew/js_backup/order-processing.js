// Function to render order cards
function renderOrderCard(order) {
    const statusClass = getStatusClass(order.status);
    const paymentMethodIcon = order.method === 'gcash' ? 'fa-credit-card' : 'fa-money-bill';
    
    const card = `
        <div class="order-card" data-order-id="${order.id}">
            <div class="order-header">
                <h3>#${order.id.toString().padStart(5, '0')}</h3>
                <span class="status-badge ${statusClass}">${order.status}</span>
            </div>
            
            <div class="customer-details">
                <h4><i class="fas fa-user"></i> Customer Details</h4>
                <p>${order.name}</p>
            </div>
            
            <div class="order-items">
                <h4><i class="fas fa-shopping-bag"></i> Order Items</h4>
                <p>${order.item_name}</p>
            </div>
            
            <div class="delivery-address">
                <h4><i class="fas fa-map-marker-alt"></i> Delivery Address</h4>
                <p>${order.address}</p>
            </div>
            
            <div class="payment-info">
                <i class="fas ${paymentMethodIcon}"></i>
                <span>${order.method.toUpperCase()}</span>
                <span class="total">₱${parseFloat(order.total_price).toFixed(2)}</span>
            </div>
            
            <div class="order-actions">
                ${getActionButtons(order)}
            </div>
        </div>
    `;
    
    return card;
}

function getActionButtons(order) {
    if (order.status === 'awaiting_payment_verification' && order.method === 'gcash') {
        return `
            <button class="btn-check-payment" onclick="showPaymentVerification(${order.id})">
                <i class="fas fa-receipt"></i> Check Payment
            </button>
        `;
    } else if (order.status === 'pending') {
        return `
            <button class="btn-start-preparing" onclick="startPreparing(${order.id})">
                <i class="fas fa-utensils"></i> Start Preparing
            </button>
        `;
    }
    // Add more button conditions based on status
    return '';
}

function getStatusClass(status) {
    const statusClasses = {
        'pending': 'status-pending',
        'preparing': 'status-preparing',
        'awaiting_payment_verification': 'status-awaiting',
        'payment_rejected': 'status-rejected',
        'ready_for_delivery': 'status-ready',
        'completed': 'status-completed'
    };
    return statusClasses[status] || 'status-default';
}

// Function to load orders
function loadOrders() {
    fetch('get_orders.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const ordersContainer = document.getElementById('ordersContainer');
                ordersContainer.innerHTML = data.orders.map(order => renderOrderCard(order)).join('');
            }
        })
        .catch(error => console.error('Error:', error));
}

// Function to start preparing an order
function startPreparing(orderId) {
    fetch('update_order_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            order_id: orderId,
            status: 'preparing'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadOrders(); // Reload orders to show updated status
        } else {
            alert('Error updating order status: ' + data.message);
        }
    })
    .catch(error => console.error('Error:', error));
}

// Load orders when page loads
document.addEventListener('DOMContentLoaded', loadOrders);

// Refresh orders periodically
setInterval(loadOrders, 30000); // Refresh every 30 seconds
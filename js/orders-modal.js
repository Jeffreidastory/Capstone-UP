document.addEventListener('DOMContentLoaded', function() {
    const ordersModal = document.getElementById('ordersModal');
    const myOrdersBtn = document.getElementById('myOrdersBtn');
    const closeOrdersBtn = document.getElementById('closeOrdersBtn');
    const ordersList = document.querySelector('.orders-list');

    // Error handling function
    function showError(message) {
        ordersList.innerHTML = `
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                ${message}
                <button onclick="loadRecentOrders()" class="retry-btn">
                    <i class="fas fa-redo"></i> Try Again
                </button>
            </div>
        `;
    }

    // No orders message
    function showNoOrders() {
        ordersList.innerHTML = `
            <div class="no-orders-message">
                <i class="fas fa-shopping-bag"></i>
                <h3>No Orders Found</h3>
                <p>You haven't placed any orders yet.</p>
            </div>
        `;
    }

    // Function to get status badge color
    function getStatusColor(status) {
        switch(status.toLowerCase()) {
            case 'pending':
                return 'status-pending';
            case 'preparing':
                return 'status-preparing';
            case 'delivered':
                return 'status-delivered';
            case 'out for delivery':
                return 'status-out-delivery';
            default:
                return 'status-default';
        }
    }

    // Function to format items list
    function formatItems(items) {
        try {
            return items.split(',').map(item => item.trim()).join(', ');
        } catch (e) {
            return items;
        }
    }

    // Open modal when clicking My Orders button
    myOrdersBtn.addEventListener('click', function() {
        loadRecentOrders();
        ordersModal.style.display = 'block';
    });

    // Close modal when clicking the close button
    closeOrdersBtn.addEventListener('click', function() {
        ordersModal.style.display = 'none';
    });

    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === ordersModal) {
            ordersModal.style.display = 'none';
        }
    });

    // Function to load recent orders
    function loadRecentOrders() {
        fetch('get_recent_orders.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log('Orders data:', data); // Debug log
                
                if (data.success && data.orders && data.orders.length > 0) {
                    ordersList.innerHTML = data.orders.map(order => `
                        <div class="order-item">
                            <div class="order-header">
                                <div class="order-info">
                                    <span class="order-number">#${order.id}</span>
                                    <span class="order-date">${order.order_time}</span>
                                </div>
                                <div class="order-amount">₱${order.total_price}</div>
                            </div>
                            <div class="order-details">
                                <div class="items-info">
                                    <div class="order-items">
                                        <span class="items-count">
                                            <i class="fas fa-shopping-bag"></i>
                                            ${order.total_products} item${order.total_products > 1 ? 's' : ''}
                                        </span>
                                        <span class="items-list">${formatItems(order.items)}</span>
                                    </div>
                                    <div class="payment-info">
                                        <i class="fas ${order.payment_method.toLowerCase() === 'gcash' ? 'fa-mobile-alt' : 'fa-money-bill-wave'}"></i>
                                        ${order.payment_method}
                                    </div>
                                </div>
                                <div class="status-container">
                                    <span class="status-badge ${getStatusColor(order.status)}">
                                        ${order.status}
                                    </span>
                                    <a href="order_details.php?id=${order.id}" class="see-details-btn">
                                        <i class="fas fa-external-link-alt"></i>
                                        See all details
                                    </a>
                                </div>
                            </div>
                        </div>
                    `).join('');
                } else {
                    // Display the "No orders found" message in a styled way
                    ordersList.innerHTML = `
                        <div class="no-orders-container">
                            <div class="no-orders-icon">
                                <i class="fas fa-clipboard-list"></i>
                            </div>
                            <h3 class="no-orders-title">No Orders Found</h3>
                            <p class="no-orders-text">You haven't placed any orders yet.</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error loading orders:', error);
                ordersList.innerHTML = '<p class="error-message">Failed to load orders. Please try again later.</p>';
            });
    }

    // Helper function to format date
    function formatDate(dateString) {
        const options = { 
            year: 'numeric', 
            month: 'short', 
            day: '2-digit', 
            hour: '2-digit', 
            minute: '2-digit'
        };
        return new Date(dateString).toLocaleString('en-US', options);
    }
});
document.addEventListener('DOMContentLoaded', function() {
    // Get modal elements
    const ordersModal = document.getElementById('ordersModal');
    const ordersList = document.querySelector('.orders-list');

    // Function to show the orders modal
    function showOrdersModal() {
        ordersModal.style.display = 'block';
    }

    // Function to add a new order to the list
    function addNewOrder(order) {
        const orderElement = document.createElement('div');
        orderElement.className = 'order-item new-order';
        orderElement.innerHTML = `
            <div class="order-header">
                <div class="order-info">
                    <span class="order-number">Order #${order.id}</span>
                    <span class="order-date">${order.order_time}</span>
                </div>
                <div class="order-amount">₱${order.total_price}</div>
            </div>
            <div class="order-content">
                <div class="items-info">
                    <div class="items-count">${order.total_products} item${order.total_products > 1 ? 's' : ''}</div>
                    <div class="items-list">${order.items}</div>
                </div>
                <div class="order-footer">
                    <div class="payment-method">
                        <i class="fas ${order.payment_method.toLowerCase() === 'gcash' ? 'fa-mobile-alt' : 'fa-money-bill-wave'}"></i>
                        ${order.payment_method}
                    </div>
                    <span class="status-badge status-${order.status.toLowerCase()}">${order.status}</span>
                </div>
            </div>
        `;

        // Insert the new order at the top of the list
        const firstOrder = ordersList.firstChild;
        if (firstOrder) {
            ordersList.insertBefore(orderElement, firstOrder);
        } else {
            ordersList.appendChild(orderElement);
        }

        // Add animation class
        setTimeout(() => {
            orderElement.classList.add('show');
        }, 100);
    }

    // Listen for successful order placement
    window.addEventListener('orderPlaced', function(e) {
        const order = e.detail;
        addNewOrder(order);
        showOrdersModal();
    });
});
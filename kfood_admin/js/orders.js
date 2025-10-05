document.addEventListener('DOMContentLoaded', function() {
    // Initialize orders when the section becomes visible
    const ordersSection = document.getElementById('orders-section');
    if (ordersSection) {
        loadOrders();
    }

    // Add event listeners for filters
    const statusFilter = document.getElementById('statusFilter');
    const orderSearch = document.getElementById('orderSearch');

    if (statusFilter) {
        statusFilter.addEventListener('change', loadOrders);
    }

    if (orderSearch) {
        orderSearch.addEventListener('input', debounce(loadOrders, 300));
    }
});

function loadOrders() {
    const statusFilter = document.getElementById('statusFilter').value;
    const searchQuery = document.getElementById('orderSearch').value;
    const tableBody = document.querySelector('.orders-table tbody');

    fetch(`get_orders.php?status=${statusFilter}&search=${encodeURIComponent(searchQuery)}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                showNotification('Error', data.error, 'error');
                return;
            }

            let html = '';
            data.forEach(order => {
                const orderDate = new Date(order.order_time).toLocaleString();
                html += `
                    <tr>
                        <td>#${order.id}</td>
                        <td>
                            <div class="customer-info">
                                <img src="${order.profile_picture || '../images/user.png'}" alt="Profile" class="profile-pic">
                                <span>${order.FirstName} ${order.LastName}</span>
                            </div>
                        </td>
                        <td>${order.delivery_address}</td>
                        <td>${order.payment_mode}</td>
                        <td>${order.item_name}</td>
                        <td>₱${parseFloat(order.total_price).toFixed(2)}</td>
                        <td>
                            <span class="status-badge ${order.order_status.toLowerCase()}">
                                ${order.order_status}
                            </span>
                        </td>
                        <td>${orderDate}</td>
                        <td>
                            <button onclick="viewOrderDetails(${order.id})" class="action-btn view-btn">
                                <i class="fas fa-eye"></i>
                            </button>
                            ${order.order_status === 'Pending' ? `
                                <button onclick="updateOrderStatus(${order.id}, 'Completed')" class="action-btn complete-btn">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button onclick="updateOrderStatus(${order.id}, 'Cancelled')" class="action-btn cancel-btn">
                                    <i class="fas fa-times"></i>
                                </button>
                            ` : ''}
                        </td>
                    </tr>
                `;
            });

            tableBody.innerHTML = html || '<tr><td colspan="9" class="no-orders">No orders found</td></tr>';
        })
        .catch(error => {
            console.error('Error loading orders:', error);
            showNotification('Error', 'Failed to load orders', 'error');
        });
}

function viewOrderDetails(orderId) {
    fetch(`get_order_details.php?id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                showNotification('Error', data.error, 'error');
                return;
            }

            const modal = document.getElementById('orderModal');
            const modalContent = modal.querySelector('.modal-body');
            
            let itemsHtml = '';
            data.items.forEach(item => {
                itemsHtml += `
                    <div class="order-item">
                        <span class="item-name">${item.name}</span>
                        <span class="item-quantity">x${item.quantity}</span>
                        <span class="item-price">₱${parseFloat(item.price).toFixed(2)}</span>
                    </div>
                `;
            });

            modalContent.innerHTML = `
                <div class="order-details">
                    <div class="order-header">
                        <h4>Order #${data.id}</h4>
                        <span class="order-date">${new Date(data.order_time).toLocaleString()}</span>
                    </div>
                    <div class="customer-details">
                        <h5>Customer Information</h5>
                        <p><strong>Name:</strong> ${data.customer_name}</p>
                        <p><strong>Address:</strong> ${data.delivery_address}</p>
                        <p><strong>Contact:</strong> ${data.phone}</p>
                    </div>
                    <div class="order-items">
                        <h5>Ordered Items</h5>
                        ${itemsHtml}
                    </div>
                    <div class="order-summary">
                        <div class="summary-item">
                            <span>Subtotal</span>
                            <span>₱${parseFloat(data.subtotal).toFixed(2)}</span>
                        </div>
                        <div class="summary-item">
                            <span>Delivery Fee</span>
                            <span>₱${parseFloat(data.delivery_fee).toFixed(2)}</span>
                        </div>
                        <div class="summary-item total">
                            <span>Total</span>
                            <span>₱${parseFloat(data.total_price).toFixed(2)}</span>
                        </div>
                    </div>
                </div>
            `;

            modal.style.display = 'block';
        })
        .catch(error => {
            console.error('Error loading order details:', error);
            showNotification('Error', 'Failed to load order details', 'error');
        });
}

function updateOrderStatus(orderId, newStatus) {
    if (!confirm(`Are you sure you want to mark this order as ${newStatus}?`)) {
        return;
    }

    fetch('update_order_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            order_id: orderId,
            status: newStatus
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Success', `Order marked as ${newStatus}`, 'success');
            loadOrders(); // Refresh the orders list
        } else {
            showNotification('Error', data.error || 'Failed to update order status', 'error');
        }
    })
    .catch(error => {
        console.error('Error updating order status:', error);
        showNotification('Error', 'Failed to update order status', 'error');
    });
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Close modal when clicking outside or on close button
window.addEventListener('click', function(event) {
    const modal = document.getElementById('orderModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
});

document.querySelector('.close-modal')?.addEventListener('click', function() {
    document.getElementById('orderModal').style.display = 'none';
});
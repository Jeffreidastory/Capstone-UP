// DOM Elements
const customerProfileForm = document.getElementById('customer-profile-form');
const customerOrdersContainer = document.getElementById('customer-orders');

// Event Listeners
if (customerProfileForm) {
    customerProfileForm.addEventListener('submit', updateProfile);
}

// Fetch customer orders on page load
document.addEventListener('DOMContentLoaded', () => {
    if (customerOrdersContainer) {
        loadCustomerOrders();
    }
});

// Update Profile Function
async function updateProfile(e) {
    e.preventDefault();

    const formData = new FormData(customerProfileForm);

    try {
        const response = await fetch('update_profile.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showNotification('Success', 'Profile updated successfully!', 'success');
        } else {
            showNotification('Error', data.message || 'Failed to update profile.', 'error');
        }
    } catch (error) {
        showNotification('Error', 'An error occurred while updating profile.', 'error');
        console.error('Error updating profile:', error);
    }
}

// Load Customer Orders
async function loadCustomerOrders() {
    try {
        const response = await fetch('get_customer_orders.php');
        const orders = await response.json();

        if (orders.length > 0) {
            displayOrders(orders);
        } else {
            customerOrdersContainer.innerHTML = '<p>No orders found.</p>';
        }
    } catch (error) {
        console.error('Error loading orders:', error);
        customerOrdersContainer.innerHTML = '<p>Error loading orders.</p>';
    }
}

// Display Orders
function displayOrders(orders) {
    const ordersHTML = orders.map(order => `
        <div class="order-card">
            <div class="order-header">
                <h3>Order #${order.order_id}</h3>
                <span class="order-date">${formatDate(order.order_date)}</span>
            </div>
            <div class="order-items">
                ${order.items.map(item => `
                    <div class="order-item">
                        <img src="${item.image}" alt="${item.name}" class="item-image">
                        <div class="item-details">
                            <h4>${item.name}</h4>
                            <p>Quantity: ${item.quantity}</p>
                            <p>Price: ₱${item.price.toFixed(2)}</p>
                        </div>
                    </div>
                `).join('')}
            </div>
            <div class="order-footer">
                <p class="order-total">Total: ₱${order.total.toFixed(2)}</p>
                <p class="order-status ${order.status.toLowerCase()}">${order.status}</p>
            </div>
        </div>
    `).join('');

    customerOrdersContainer.innerHTML = ordersHTML;
}

// Helper Functions
function formatDate(dateString) {
    const options = { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    return new Date(dateString).toLocaleDateString('en-US', options);
}

// Notification Function
function showNotification(title, message, type = 'info') {
    const event = new CustomEvent('show-notification', {
        detail: { title, message, type }
    });
    document.dispatchEvent(event);
}

// Export functionality
window.CustomerProfile = {
    updateProfile,
    loadCustomerOrders
};
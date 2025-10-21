// Global variable to store current order ID
let currentOrderId = null;

// Function to show notifications
function showNotification(title, message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
        <div>
            <strong>${title}</strong>
            <p>${message}</p>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Force reflow to enable transition
    notification.offsetHeight;
    notification.classList.add('show');
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Function to show payment verification modal
function showPaymentVerification(orderId) {
    currentOrderId = orderId;
    const modal = document.getElementById('paymentVerificationModal');
    
    // Show loading cursor
    document.body.style.cursor = 'wait';
    
    // Fetch payment details
    fetch(`get_payment_details.php?order_id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('orderId').textContent = `#${String(orderId).padStart(5, '0')}`;
                document.getElementById('refNumber').textContent = data.reference_number;
                document.getElementById('totalAmount').textContent = `₱${parseFloat(data.total_amount || 0).toFixed(2)}`;
                document.getElementById('paymentScreenshot').src = data.screenshot_url;
                
                // Show the modal
                modal.style.display = 'block';
            } else {
                throw new Error(data.message || 'Failed to load payment details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error', 'Failed to load payment details', 'error');
        })
        .finally(() => {
            document.body.style.cursor = 'default';
        });
}

// Function to update order card in real-time
function updateOrderCardStatus(orderId, newStatus) {
    const orderCard = document.querySelector(`.order-card[data-order-id="${orderId}"]`);
    if (!orderCard) return;

    // Update card status
    orderCard.dataset.status = newStatus.toLowerCase();
    
    // Update status badge
    const statusBadge = orderCard.querySelector('.status-badge');
    if (statusBadge) {
        statusBadge.className = `status-badge status-${newStatus.toLowerCase().replace(' ', '-')}`;
        statusBadge.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
    }

    // Update action buttons
    const actionButtons = orderCard.querySelector('.action-buttons');
    if (actionButtons) {
        if (newStatus.toLowerCase() === 'preparing') {
            actionButtons.innerHTML = `
                <button class="btn btn-success" onclick="updateStatus(${orderId}, 'out for delivery')">
                    <i class="fas fa-motorcycle"></i> Mark Out for Delivery
                </button>
            `;
        } else if (newStatus.toLowerCase() === 'out for delivery') {
            actionButtons.innerHTML = ''; // No buttons needed for out for delivery
        }
    }

    // Update card styling based on new status
    orderCard.className = 'order-card';
    orderCard.dataset.status = newStatus.toLowerCase();
}

// Function to handle payment verification
function handlePayment(action) {
    if (!currentOrderId) return;
    
    const modal = document.getElementById('paymentVerificationModal');
    const verifyBtn = document.getElementById('verifyPaymentBtn');
    const rejectBtn = document.getElementById('rejectPaymentBtn');
    
    // Disable buttons and show loading state
    verifyBtn.disabled = true;
    rejectBtn.disabled = true;
    document.body.style.cursor = 'wait';

    fetch('verify_and_prepare.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            order_id: currentOrderId,
            status: action
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Hide modal
            modal.style.display = 'none';
            
            // Show success message
            showNotification(
                'Success', 
                action === 'verified' ? 
                    'Payment verified and order is being prepared' : 
                    'Payment has been rejected',
                'success'
            );
            
            // Reload page after notification
            setTimeout(() => window.location.reload(), 1000);
        } else {
            throw new Error(data.message || `Failed to ${action} payment`);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error', error.message, 'error');
        
        // Re-enable buttons
        verifyBtn.disabled = false;
        rejectBtn.disabled = false;
    })
    .finally(() => {
        document.body.style.cursor = 'default';
    });
}

// Filter orders function
function filterOrders(status) {
    const orders = document.querySelectorAll('.order-card');
    
    orders.forEach(order => {
        const orderStatus = order.dataset.status;
        const paymentMethod = order.querySelector('.payment-method')?.textContent.toLowerCase() || '';
        const isGcash = paymentMethod.includes('gcash');
        const isCOD = !isGcash;

        if (status === 'all') {
            order.style.display = 'block';
        } else if (status === 'needs-verification') {
            // Show only pending GCash payments
            order.style.display = (orderStatus === 'pending' && isGcash) ? 'block' : 'none';
        } else if (status === 'pending') {
            // Show only pending COD payments
            order.style.display = (orderStatus === 'pending' && isCOD) ? 'block' : 'none';
        } else {
            order.style.display = orderStatus === status ? 'block' : 'none';
        }
    });

    // Update active filter button
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.status === status);
    });
}

// Filter orders function
function filterOrders(status) {
    const orders = document.querySelectorAll('.order-card');
    
    orders.forEach(order => {
        const orderStatus = order.dataset.status;
        const paymentMethod = order.querySelector('.payment-method')?.textContent.toLowerCase() || '';
        const isGcash = paymentMethod.includes('gcash');
        const isCOD = !isGcash;
        
        let shouldDisplay = false;
        
        switch(status) {
            case 'all':
                shouldDisplay = true;
                break;
            case 'needs-verification':
                // Show only pending GCash payments
                shouldDisplay = orderStatus === 'pending' && isGcash;
                break;
            case 'pending':
                // Show only pending COD payments
                shouldDisplay = orderStatus === 'pending' && isCOD;
                break;
            default:
                // For preparing and out for delivery
                shouldDisplay = orderStatus === status;
        }
        
        order.style.display = shouldDisplay ? 'block' : 'none';
    });

    // Update active filter button
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.status === status);
    });
}

// Set up event listeners when document is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Close modal when clicking the close button or outside
    document.querySelectorAll('.close, .close-modal').forEach(element => {
        element.addEventListener('click', () => {
            document.getElementById('paymentVerificationModal').style.display = 'none';
        });
    });

    // Add filter button event listeners
    document.querySelectorAll('.filter-btn').forEach(button => {
        button.addEventListener('click', () => {
            filterOrders(button.dataset.status);
        });
    });

    // Add click event listeners to verify and reject buttons
    const verifyBtn = document.getElementById('verifyPaymentBtn');
    const rejectBtn = document.getElementById('rejectPaymentBtn');

    if (verifyBtn) {
        verifyBtn.addEventListener('click', () => handlePayment('verified'));
    }

    if (rejectBtn) {
        rejectBtn.addEventListener('click', () => {
            if (confirm('Are you sure you want to reject this payment?')) {
                handlePayment('rejected');
            }
        });
    }
});

// Update order status function
function updateStatus(orderId, newStatus) {
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
            showNotification('Success', `Order status updated to ${newStatus}`, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            throw new Error(data.message || 'Failed to update order status');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error', error.message, 'error');
    });
}
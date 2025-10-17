let currentOrderId = null;

function updateStatus(orderId, newStatus) {
    // Show loading indicator
    const button = event.target.closest('button');
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Updating...`;

    fetch('../update_order_status.php', {
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
            // Show success message
            showNotification('Order status updated successfully', 'success');
            
            // Update the UI
            const orderCard = button.closest('.order-card');
            const statusBadge = orderCard.querySelector('.status-badge');
            
            // Update status badge
            statusBadge.className = `status-badge status-${newStatus.replace(' ', '-')}`;
            statusBadge.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
            
            // Update action buttons
            if (newStatus === 'preparing') {
                button.className = 'btn btn-success';
                button.innerHTML = '<i class="fas fa-motorcycle"></i> Mark Out for Delivery';
                button.onclick = () => updateStatus(orderId, 'out for delivery');
            } else if (newStatus === 'out for delivery') {
                button.remove(); // Remove the button as no more actions are needed
            }
            
            // Refresh the page after a short delay
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            showNotification(data.message || 'Error updating order status', 'error');
            button.disabled = false;
            button.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error updating order status', 'error');
        button.disabled = false;
        button.innerHTML = originalText;
    });
}

function showNotification(title, message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
        <div class="notification-content">
            <strong>${title}</strong>
            <p>${message}</p>
        </div>
    `;
    
    document.body.appendChild(notification);
    setTimeout(() => notification.classList.add('show'), 10);
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Function to show payment verification modal
function showPaymentVerification(orderId) {
    currentOrderId = orderId;
    const modal = document.getElementById('paymentVerificationModal');
    
    // Show loading state
    document.getElementById('orderId').textContent = 'Loading...';
    document.getElementById('refNumber').textContent = 'Loading...';
    document.getElementById('totalAmount').textContent = 'Loading...';
    document.getElementById('paymentScreenshot').src = '';
    
    // Show the modal
    modal.style.display = 'block';
    
    // Fetch payment details
    fetch(`../get_payment_details.php?order_id=${orderId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                document.getElementById('orderId').textContent = `#${String(orderId).padStart(5, '0')}`;
                document.getElementById('refNumber').textContent = data.reference_number;
                document.getElementById('totalAmount').textContent = `₱${parseFloat(data.total_amount).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
                
                const img = document.getElementById('paymentScreenshot');
                img.onerror = () => {
                    img.src = '../images/error-image.png';
                    showNotification('Warning', 'Failed to load payment screenshot', 'warning');
                };
                img.src = data.screenshot_url;
            } else {
                throw new Error(data.message || 'Failed to load payment details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error', 'Failed to load payment details: ' + error.message, 'error');
            modal.style.display = 'none';
        });
}

// Function to handle payment verification or rejection
function handlePayment(action) {
    event.preventDefault();
    
    if (!currentOrderId) {
        showNotification('Error', 'No order selected', 'error');
        return;
    }

    // Get buttons
    const verifyBtn = document.getElementById('verifyPaymentBtn');
    const rejectBtn = document.getElementById('rejectPaymentBtn');
    
    // Disable buttons during request
    verifyBtn.disabled = true;
    rejectBtn.disabled = true;
    
    // Make API call
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

    // First verify the payment
    fetch('../verify_and_prepare.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            order_id: currentOrderId,
            status: action
        })
    })
    .then(response => {
        if (!response.ok) throw new Error('Network response was not ok');
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // If payment verification was successful and action was 'verified',
            // update the order status to 'preparing'
            if (action === 'verified') {
                return fetch('../update_order_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        order_id: currentOrderId,
                        status: 'preparing'
                    })
                }).then(response => response.json());
            }
            return data;
        } else {
            throw new Error(data.message || 'Failed to process payment');
        }
    })
    
    // Disable both buttons during processing
    document.getElementById('verifyPaymentBtn').disabled = true;
    document.getElementById('rejectPaymentBtn').disabled = true;
    actionButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    
    fetch('../verify_and_prepare.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            order_id: currentOrderId,
            status: action
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Hide modal
            document.getElementById('paymentVerificationModal').style.display = 'none';
            
            // Show success message
            showNotification(
                'Success', 
                action === 'verified' ? 
                    'Payment verified and order is being prepared' : 
                    'Payment has been rejected',
                'success'
            );
            
            // Reload page after a short delay
            setTimeout(() => location.reload(), 1000);
        } else {
            throw new Error(data.message || `Failed to ${action} payment`);
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Hide modal immediately
            document.getElementById('paymentVerificationModal').style.display = 'none';
            // Immediately reload to show preparing status
            window.location.reload();
        } else {
            // Re-enable buttons on error
            verifyBtn.disabled = false;
            rejectBtn.disabled = false;
            throw new Error(data.message || 'Failed to process payment');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error', error.message, 'error');
        
        // Re-enable buttons
        document.getElementById('verifyPaymentBtn').disabled = false;
        document.getElementById('rejectPaymentBtn').disabled = false;
        actionButton.innerHTML = originalText;
    });
}

// Set up event listeners
document.addEventListener('DOMContentLoaded', () => {
    // Close modal when clicking the close button or outside
    document.querySelectorAll('.close, .close-modal').forEach(element => {
        element.addEventListener('click', () => {
            document.getElementById('paymentVerificationModal').style.display = 'none';
        });
    });

    // Handle verify button click
    document.getElementById('verifyPaymentBtn')?.addEventListener('click', () => {
        handlePayment('verified');
    });

    // Handle reject button click with confirmation
    document.getElementById('rejectPaymentBtn')?.addEventListener('click', () => {
        if (confirm('Are you sure you want to reject this payment?')) {
            handlePayment('rejected');
        }
    });
});
// Global variable to store current order ID
let currentOrderId = null;

// Function to show notifications
function showNotification(title, message, type = 'info') {
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

// Function to update order status
function updateStatus(orderId, newStatus) {
    // Show loading indicator
    const button = event.target.closest('button');
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Updating...`;

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
        button.disabled = false;
        button.innerHTML = originalText;
    });
}

// Function to handle payment verification
function handlePayment(action) {
    if (!currentOrderId) {
        showNotification('Error', 'No order selected', 'error');
        return;
    }

    // Get and disable buttons
    const verifyBtn = document.getElementById('verifyPaymentBtn');
    const rejectBtn = document.getElementById('rejectPaymentBtn');
    const progressIndicator = document.getElementById('verificationProgress');
    
    // Show loading state
    verifyBtn.disabled = true;
    rejectBtn.disabled = true;
    progressIndicator.style.display = 'flex';

    // First step: Verify payment
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
            // Payment verified successfully
            showNotification('Success', 
                action === 'verified' ? 
                    'Payment verified successfully! Order is now being prepared.' : 
                    'Payment rejected successfully.',
                'success'
            );
            
            // Reload the page after a short delay
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            throw new Error(data.message || 'Failed to process payment');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error', error.message, 'error');
        
        // Reset UI
        verifyBtn.disabled = false;
        rejectBtn.disabled = false;
        progressIndicator.style.display = 'none';
    });
}

// Function to show payment verification modal
function showPaymentVerification(orderId) {
    currentOrderId = orderId;
    const modal = document.getElementById('paymentVerificationModal');
    const progressIndicator = document.getElementById('verificationProgress');
    
    // Reset progress indicator
    progressIndicator.style.display = 'none';
    
    // Show the modal
    modal.style.display = 'block';
    
    // Fetch payment details
    fetch(`get_payment_details.php?order_id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('orderId').textContent = `#${String(orderId).padStart(5, '0')}`;
                document.getElementById('refNumber').textContent = data.reference_number;
                document.getElementById('totalAmount').textContent = 
                    `₱${parseFloat(data.total_amount).toLocaleString('en-PH', {minimumFractionDigits: 2})}`;
                
                // Handle image loading
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
            showNotification('Error', error.message, 'error');
            modal.style.display = 'none';
        });
}

// Set up event listeners when document is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Close modal when clicking the close button
    document.querySelectorAll('.close, .close-modal').forEach(element => {
        element.addEventListener('click', () => {
            document.getElementById('paymentVerificationModal').style.display = 'none';
        });
    });
});
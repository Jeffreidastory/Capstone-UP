// Payment status checker
class PaymentStatusChecker {
    constructor(orderId) {
        this.orderId = orderId;
        this.checkInterval = null;
        this.lastStatus = null;
    }

    startChecking() {
        // Check immediately
        this.checkStatus();
        
        // Then check every 5 seconds
        this.checkInterval = setInterval(() => {
            this.checkStatus();
        }, 5000);
    }

    stopChecking() {
        if (this.checkInterval) {
            clearInterval(this.checkInterval);
            this.checkInterval = null;
        }
    }

    async checkStatus() {
        try {
            const response = await fetch(`check_payment_status.php?order_id=${this.orderId}`);
            const data = await response.json();
            
            if (!data.success) {
                console.error('Error checking payment status:', data.message);
                return;
            }

            // If status has changed
            if (this.lastStatus !== data.status) {
                this.lastStatus = data.status;
                this.handleStatusChange(data);
            }
        } catch (error) {
            console.error('Error checking payment status:', error);
        }
    }

    handleStatusChange(data) {
        const placeOrderBtn = document.getElementById('placeOrderBtn');
        
        switch (data.status) {
            case 'verified':
                // Stop checking status
                this.stopChecking();
                
                // Show success notification
                showNotification('Success', 'Payment verified! Your order has been placed.', 'success');
                
                // Update button state
                if (placeOrderBtn) {
                    placeOrderBtn.className = 'place-order-btn success';
                    placeOrderBtn.innerHTML = '<i class="fas fa-check"></i> Order Placed Successfully';
                }
                
                // Redirect to order confirmation after 2 seconds
                setTimeout(() => {
                    window.location.href = `order_confirmation.php?order_id=${this.orderId}`;
                }, 2000);
                break;
                
            case 'invalid':
                // Stop checking status
                this.stopChecking();
                
                // Show error notification
                showNotification('Payment Failed', 'Your payment was rejected. Please try again.', 'error');
                
                // Update button state
                if (placeOrderBtn) {
                    placeOrderBtn.className = 'place-order-btn';
                    placeOrderBtn.disabled = false;
                    placeOrderBtn.innerHTML = 'Place Order';
                }
                
                // Show GCash modal again for re-upload
                const modal = document.getElementById('gcashModal');
                if (modal) {
                    modal.style.display = 'block';
                }
                break;
                
            case 'awaiting_verification':
                // Update button state
                if (placeOrderBtn) {
                    placeOrderBtn.className = 'place-order-btn awaiting-verification';
                    placeOrderBtn.innerHTML = '<i class="fas fa-clock"></i> Awaiting Payment Verification';
                }
                break;
        }
    }
}

// Export for use in other files
window.PaymentStatusChecker = PaymentStatusChecker;
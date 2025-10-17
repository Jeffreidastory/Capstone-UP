// Order Handler
class OrderHandler {
    constructor() {
        this.placeOrderBtn = document.getElementById('placeOrderBtn');
        this.init();
    }

    init() {
        if (this.placeOrderBtn) {
            this.placeOrderBtn.addEventListener('click', async () => this.handleOrderPlacement());
        }

        // Initialize payment method selection
        document.querySelectorAll('.payment-method').forEach(method => {
            method.addEventListener('click', () => this.handlePaymentMethodSelection(method));
        });
    }

    async handleOrderPlacement() {
        try {
            // Clear previous errors
            document.querySelectorAll('.error').forEach(el => el.classList.remove('error'));

            // Validate delivery address
            const deliveryAddress = document.getElementById('delivery_address');
            if (!deliveryAddress || !deliveryAddress.value) {
                this.showError('Please select a delivery address');
                deliveryAddress?.classList.add('error');
                return;
            }

            // Validate payment method
            const selectedPayment = document.querySelector('.payment-method.selected');
            if (!selectedPayment) {
                this.showError('Please select a payment method');
                return;
            }

            // Get cart items
            const selectedItems = JSON.parse(sessionStorage.getItem('selectedItems') || '[]');
            const cartItems = JSON.parse(sessionStorage.getItem('cart') || '[]');
            const selectedCartItems = cartItems.filter(item => selectedItems.includes(item.id));

            if (selectedCartItems.length === 0) {
                this.showError('No items selected for checkout');
                return;
            }

            // Show processing state
            this.placeOrderBtn.disabled = true;
            this.placeOrderBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing Order...';

            const paymentMethod = selectedPayment.dataset.method;
            const formData = new FormData();

            // Basic order data
            const orderData = {
                paymentMethod,
                deliveryAddressId: deliveryAddress.value,
                deliveryInstructions: document.querySelector('textarea[placeholder="Additional instructions for delivery..."]').value || '',
                items: selectedCartItems,
                orderTotal: selectedCartItems.reduce((sum, item) => sum + (item.price * item.quantity), 0),
                status: paymentMethod === 'gcash' ? 'pending_verification' : 'pending'
            };

            formData.append('orderData', JSON.stringify(orderData));

            // Handle GCash specific data
            if (paymentMethod === 'gcash') {
                const refNumber = document.getElementById('modal_reference_number')?.value.trim();
                const proofFile = document.getElementById('modal_payment_proof')?.files[0];

                if (!refNumber || !proofFile) {
                    this.showError('Please complete GCash payment details');
                    this.placeOrderBtn.disabled = false;
                    this.placeOrderBtn.innerHTML = 'Place Order';
                    return;
                }

                formData.append('reference_number', refNumber);
                formData.append('payment_proof', proofFile);
            }

            // Submit order
            const response = await fetch('save_order.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                // Update cart
                this.updateCart(selectedItems);

                if (paymentMethod === 'gcash') {
                    // Show verification UI
                    this.showVerificationUI(result.orderId);
                } else {
                    // Show success for COD
                    showNotification('Success', 'Your order has been placed successfully!', 'success');
                    this.placeOrderBtn.className = 'place-order-btn success';
                    this.placeOrderBtn.innerHTML = '<i class="fas fa-check"></i> Order Placed Successfully';

                    // Redirect after 2 seconds
                    setTimeout(() => {
                        window.location.href = `order_confirmation.php?order_id=${result.orderId}`;
                    }, 2000);
                }
            } else {
                throw new Error(result.message || 'Failed to place order');
            }
        } catch (error) {
            console.error('Order error:', error);
            this.showError(error.message || 'An error occurred while processing your order');
            this.placeOrderBtn.disabled = false;
            this.placeOrderBtn.innerHTML = 'Place Order';
        }
    }

    handlePaymentMethodSelection(method) {
        // Remove selection from all methods
        document.querySelectorAll('.payment-method').forEach(m => m.classList.remove('selected'));
        
        // Select the clicked method
        method.classList.add('selected');

        // Handle GCash modal
        if (method.dataset.method === 'gcash') {
            const modal = document.getElementById('gcashModal');
            if (modal) {
                modal.style.display = 'block';
                
                // Update amount in modal
                const total = this.calculateTotal();
                document.getElementById('gcashAmount').textContent = total.toFixed(2);
            }
        }
    }

    showVerificationUI(orderId) {
        // Remove any existing overlay
        document.querySelector('.payment-loading-overlay')?.remove();

        // Create and show loading overlay
        const overlay = document.createElement('div');
        overlay.className = 'payment-loading-overlay';
        overlay.innerHTML = `
            <div class="payment-loading-content">
                <div class="spinner"></div>
                <h2>Payment Verification in Progress</h2>
                <p>Please wait while we verify your payment...</p>
                <div class="status-steps">
                    <div class="step active">
                        <i class="fas fa-receipt"></i>
                        <span>Payment Submitted</span>
                    </div>
                    <div class="step">
                        <i class="fas fa-clock"></i>
                        <span>Verification in Progress</span>
                    </div>
                    <div class="step">
                        <i class="fas fa-check-circle"></i>
                        <span>Payment Verified</span>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(overlay);
        overlay.style.display = 'flex';

        // Update button state
        this.placeOrderBtn.className = 'place-order-btn awaiting-verification';
        this.placeOrderBtn.innerHTML = '<i class="fas fa-clock"></i> Awaiting Payment Verification';

        // Start checking payment status
        this.startPaymentVerification(orderId);
    }

    startPaymentVerification(orderId) {
        const checkStatus = async () => {
            try {
                const response = await fetch(`check_payment_status.php?order_id=${orderId}`);
                const result = await response.json();

                if (result.success) {
                    const statusSteps = document.querySelectorAll('.status-steps .step');
                    
                    switch (result.status) {
                        case 'verified':
                            statusSteps.forEach(step => step.classList.add('active'));
                            setTimeout(() => {
                                window.location.href = `order_confirmation.php?order_id=${orderId}`;
                            }, 2000);
                            return;
                        case 'invalid':
                            document.querySelector('.payment-loading-content').classList.add('error');
                            setTimeout(() => {
                                window.location.href = 'checkout.php';
                            }, 3000);
                            return;
                        default:
                            statusSteps[1].classList.add('active');
                            setTimeout(checkStatus, 5000); // Check again in 5 seconds
                    }
                }
            } catch (error) {
                console.error('Status check error:', error);
                setTimeout(checkStatus, 5000); // Retry on error
            }
        };

        checkStatus();
    }

    updateCart(selectedItems) {
        let currentCart = JSON.parse(sessionStorage.getItem('cart') || '[]');
        currentCart = currentCart.filter(item => !selectedItems.includes(item.id));
        sessionStorage.setItem('cart', JSON.stringify(currentCart));
        sessionStorage.removeItem('selectedItems');
    }

    calculateTotal() {
        const selectedItems = JSON.parse(sessionStorage.getItem('selectedItems') || '[]');
        const cartItems = JSON.parse(sessionStorage.getItem('cart') || '[]');
        return cartItems
            .filter(item => selectedItems.includes(item.id))
            .reduce((sum, item) => sum + (item.price * item.quantity), 0);
    }

    showError(message) {
        showNotification('Error', message, 'error');
    }
}

// Initialize order handler when document is ready
document.addEventListener('DOMContentLoaded', () => {
    window.orderHandler = new OrderHandler();
});
// Order Processor
class OrderProcessor {
    constructor() {
        this.placeOrderBtn = document.getElementById('placeOrderBtn');
        this.setupEventListeners();
    }

    setupEventListeners() {
        this.placeOrderBtn?.addEventListener('click', (e) => this.handlePlaceOrder(e));
    }

    async handlePlaceOrder(e) {
        e.preventDefault();

        try {
            // Validate cart items first
            const selectedItems = JSON.parse(sessionStorage.getItem('selectedItems') || '[]');
            const cartItems = JSON.parse(sessionStorage.getItem('cart') || '[]');
            
            if (!cartItems.length) {
                showNotification('Error', 'Your cart is empty', 'error');
                return;
            }
            
            if (!selectedItems.length) {
                showNotification('Error', 'No items selected for checkout', 'error');
                return;
            }

            // Get selected payment method
            const selectedPayment = document.querySelector('.payment-method.selected');
            if (!selectedPayment) {
                showNotification('Error', 'Please select a payment method', 'error');
                return;
            }

            const paymentMethod = selectedPayment.dataset.method;

            // Validate delivery address
            const deliveryAddress = document.getElementById('delivery_address');
            if (!deliveryAddress?.value) {
                showNotification('Error', 'Please select a delivery address', 'error');
                deliveryAddress?.classList.add('error');
                return;
            }

            // For GCash payments, verify payment details
            if (paymentMethod === 'gcash') {
                if (!window.isGcashPaymentConfirmed) {
                    showNotification('Error', 'Please confirm your GCash payment details first', 'error');
                    document.getElementById('gcashModal').style.display = 'block';
                    return;
                }

                const modalRefNumber = document.getElementById('modal_reference_number');
                const modalPaymentProof = document.getElementById('modal_payment_proof');

                if (!modalRefNumber || !modalPaymentProof || !modalPaymentProof.files.length) {
                    showNotification('Error', 'Please complete the GCash payment details', 'error');
                    document.getElementById('gcashModal').style.display = 'block';
                    return;
                }
            }

            // Show processing state
            this.placeOrderBtn.disabled = true;
            this.placeOrderBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing Order...';

            // Prepare order data
            const formData = this.prepareOrderData(paymentMethod);
            
            // Show loading UI for GCash orders
            if (paymentMethod === 'gcash') {
                this.showLoadingOverlay();
            }

            // Get selected cart items for logging
            const selectedCartItems = cartItems.filter(item => selectedItems.includes(item.id));
            
            // Submit order
            console.log('Submitting order with data:', {
                method: paymentMethod,
                items: selectedCartItems,
                hasPaymentProof: paymentMethod === 'gcash' ? formData.has('payment_proof') : 'N/A',
                hasRefNumber: paymentMethod === 'gcash' ? formData.has('reference_number') : 'N/A'
            });

            const response = await fetch('save_order.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            console.log('Server response:', result);

            if (result.success) {
                // Clear cart
                this.clearCart();

                if (paymentMethod === 'gcash') {
                    // Update loading UI and start verification
                    this.updateLoadingStatus('pending_verification');
                    this.startPaymentVerification(result.orderId);
                } else {
                    // Show success for COD
                    showNotification('Success', 'Your order has been placed successfully!', 'success');
                    this.placeOrderBtn.className = 'place-order-btn success';
                    this.placeOrderBtn.innerHTML = '<i class="fas fa-check"></i> Order Placed Successfully';
                    
                    // Redirect to confirmation
                    setTimeout(() => {
                        window.location.href = `order_confirmation.php?order_id=${result.orderId}`;
                    }, 2000);
                }
            } else {
                throw new Error(result.message || 'Failed to place order');
            }
        } catch (error) {
            console.error('Order error:', error);
            if (error instanceof Error) {
                console.error('Error details:', {
                    message: error.message,
                    stack: error.stack
                });
            }
            showNotification('Error', error.message || 'An error occurred while processing your order', 'error');
            this.placeOrderBtn.disabled = false;
            this.placeOrderBtn.innerHTML = 'Place Order';
        }
    }

    prepareOrderData(paymentMethod) {
        const formData = new FormData();
        
        // Get delivery information
        const deliveryAddressId = document.getElementById('delivery_address')?.value;
        if (!deliveryAddressId) {
            throw new Error('Please select a delivery address');
        }
        
        const deliveryInstructions = document.querySelector('textarea[placeholder="Additional instructions for delivery..."]')?.value || '';
        
        // Get cart items
        const selectedItems = JSON.parse(sessionStorage.getItem('selectedItems') || '[]');
        const cartItems = JSON.parse(sessionStorage.getItem('cart') || '[]');
        const selectedCartItems = cartItems.filter(item => selectedItems.includes(item.id));
        
        if (!selectedCartItems.length) {
            throw new Error('No items selected for checkout');
        }
        
        // Calculate total
        const total = selectedCartItems.reduce((sum, item) => sum + (parseFloat(item.price) * parseInt(item.quantity)), 0);
        
        const orderData = {
            paymentMethod,
            deliveryAddressId,
            deliveryInstructions,
            items: selectedCartItems,
            total: total.toFixed(2),
            status: paymentMethod === 'gcash' ? 'pending_verification' : 'pending'
        };

        formData.append('orderData', JSON.stringify(orderData));

        // Add GCash specific data if applicable
        if (paymentMethod === 'gcash') {
            const modalRefNumber = document.getElementById('modal_reference_number');
            const modalPaymentProof = document.getElementById('modal_payment_proof');
            
            console.log('GCash payment elements:', {
                refNumberElement: modalRefNumber,
                proofElement: modalPaymentProof,
                hasFiles: modalPaymentProof?.files?.length > 0
            });
            
            if (!modalRefNumber || !modalPaymentProof || !modalPaymentProof.files.length) {
                throw new Error('GCash payment details are incomplete. Please confirm payment details first.');
            }
            
            const refNumber = modalRefNumber.value.trim();
            const paymentProof = modalPaymentProof.files[0];
            
            if (!refNumber) {
                throw new Error('Please enter the GCash reference number.');
            }
            
            // Validate file
            if (!paymentProof.type.match(/^image\/(jpeg|png)$/)) {
                throw new Error('Invalid file type. Please upload a JPG or PNG image.');
            }
            
            if (paymentProof.size > 2 * 1024 * 1024) {
                throw new Error('File size too large. Maximum size is 2MB.');
            }
            
            console.log('Adding GCash data to form:', {
                refNumber,
                fileName: paymentProof.name,
                fileSize: paymentProof.size,
                fileType: paymentProof.type
            });
            
            formData.append('reference_number', refNumber);
            formData.append('payment_proof', paymentProof);
        }

        return formData;
    }

    showLoadingOverlay() {
        // Remove any existing overlay
        document.querySelector('.payment-loading-overlay')?.remove();

        const overlay = document.createElement('div');
        overlay.className = 'payment-loading-overlay';
        overlay.innerHTML = `
            <div class="payment-loading-content">
                <div class="spinner"></div>
                <h2>Processing Your Order</h2>
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
                        <span>Order Confirmed</span>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(overlay);
        overlay.style.display = 'flex';
    }

    updateLoadingStatus(status) {
        const steps = document.querySelectorAll('.status-steps .step');
        
        switch (status) {
            case 'pending_verification':
                steps[1].classList.add('active');
                break;
            case 'verified':
                steps.forEach(step => step.classList.add('active'));
                document.querySelector('.payment-loading-content').classList.add('success');
                break;
            case 'invalid':
                document.querySelector('.payment-loading-content').classList.add('error');
                break;
        }
    }

    startPaymentVerification(orderId) {
        const checkStatus = async () => {
            try {
                const response = await fetch(`check_payment_status.php?order_id=${orderId}`);
                const result = await response.json();

                if (result.success) {
                    switch (result.status) {
                        case 'verified':
                            this.updateLoadingStatus('verified');
                            setTimeout(() => {
                                window.location.href = `order_confirmation.php?order_id=${orderId}`;
                            }, 2000);
                            return;
                        case 'invalid':
                            this.updateLoadingStatus('invalid');
                            setTimeout(() => {
                                window.location.href = 'checkout.php';
                            }, 3000);
                            return;
                        default:
                            // Continue checking
                            setTimeout(checkStatus, 5000);
                    }
                } else {
                    setTimeout(checkStatus, 5000);
                }
            } catch (error) {
                console.error('Status check error:', error);
                setTimeout(checkStatus, 5000);
            }
        };

        checkStatus();
    }

    clearCart() {
        const selectedItems = JSON.parse(sessionStorage.getItem('selectedItems') || '[]');
        let currentCart = JSON.parse(sessionStorage.getItem('cart') || '[]');
        currentCart = currentCart.filter(item => !selectedItems.includes(item.id));
        sessionStorage.setItem('cart', JSON.stringify(currentCart));
        sessionStorage.removeItem('selectedItems');
    }
}

// Initialize when document is ready
document.addEventListener('DOMContentLoaded', () => {
    window.orderProcessor = new OrderProcessor();
});
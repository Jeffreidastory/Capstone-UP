// GCash Payment Handling
let gcashPaymentData = null;
let isGcashPaymentConfirmed = false;

document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('gcashModal');
    const confirmButton = document.getElementById('confirmGcashPayment');
    const closeButtons = document.querySelectorAll('.close, .close-modal');
    const paymentMethods = document.querySelectorAll('.payment-method');
    const modalForm = document.getElementById('gcashPaymentForm');
    
    // Payment method selection
    paymentMethods.forEach(method => {
        method.addEventListener('click', () => {
            // If payment is already confirmed, prevent changing
            if (isGcashPaymentConfirmed) {
                showNotification('Info', 'Payment already confirmed. Please proceed with order or refresh page to change payment.', 'info');
                return;
            }

            // Remove selected class from all methods
            paymentMethods.forEach(m => {
                if (m.classList.contains('confirmed')) {
                    // Don't remove selection from confirmed payment
                    return;
                }
                m.classList.remove('selected');
            });
            
            // Only add selected class if not already confirmed
            if (!method.classList.contains('confirmed')) {
                method.classList.add('selected');
            }

            // Handle GCash selection
            if (method.getAttribute('data-method') === 'gcash' && !method.classList.contains('confirmed')) {
                const cartItems = JSON.parse(sessionStorage.getItem('cart') || '[]');
                const selectedItems = JSON.parse(sessionStorage.getItem('selectedItems') || '[]');
                const total = cartItems
                    .filter(item => selectedItems.includes(item.id))
                    .reduce((sum, item) => sum + (item.price * item.quantity), 0);
                
                document.getElementById('gcashAmount').textContent = total.toFixed(2);
                modal.style.display = 'block';

                // Reset form if payment is not confirmed
                if (!isGcashPaymentConfirmed) {
                    modalForm.reset();
                    const previewContainer = document.getElementById('previewContainer');
                    if (previewContainer) {
                        previewContainer.classList.add('hidden');
                    }
                }
            }
        });
    });

    // Close modal handlers
    closeButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            modal.style.display = 'none';
            if (!isGcashPaymentConfirmed) {
                // Deselect GCash if payment wasn't confirmed
                paymentMethods.forEach(m => {
                    if (m.getAttribute('data-method') === 'gcash') {
                        m.classList.remove('selected');
                    }
                });
            }
        });
    });

    // Handle modal payment confirmation
    if (confirmButton) {
        confirmButton.addEventListener('click', () => {
            const referenceNumber = document.getElementById('modal_reference_number')?.value.trim();
            const paymentProof = document.getElementById('modal_payment_proof')?.files[0];
            const refInput = document.getElementById('modal_reference_number');
            const proofInput = document.getElementById('modal_payment_proof');

            // Reset error states
            refInput?.classList.remove('error');
            proofInput?.classList.remove('error');

            let isValid = true;
            let errorMessage = [];

            // Validate reference number
            if (!referenceNumber) {
                refInput?.classList.add('error');
                errorMessage.push('Please enter the GCash reference number');
                isValid = false;
            }

            // Validate payment proof
            if (!paymentProof) {
                proofInput?.classList.add('error');
                errorMessage.push('Please upload the payment screenshot');
                isValid = false;
            } else {
                // Validate file type
                if (!paymentProof.type.match(/^image\/(jpeg|png)$/)) {
                    proofInput?.classList.add('error');
                    errorMessage.push('Please select a valid image file (JPG or PNG)');
                    isValid = false;
                }
                // Validate file size (2MB limit)
                if (paymentProof.size > 2 * 1024 * 1024) {
                    proofInput?.classList.add('error');
                    errorMessage.push('Image size must be less than 2MB');
                    isValid = false;
                }
            }

            if (!isValid) {
                showNotification('Error', errorMessage.join('. '), 'error');
                return;
            }

            // Store payment data in hidden inputs that will be used during order placement
            const hiddenRefNumber = document.getElementById('referenceNumber');
            const hiddenPaymentProofInput = document.getElementById('paymentProof');
            
            if (hiddenRefNumber && hiddenPaymentProofInput) {
                // Copy data to hidden fields that will be used during order placement
                hiddenRefNumber.value = referenceNumber;
                
                // Create a new DataTransfer object to copy the file
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(paymentProof);
                hiddenPaymentProofInput.files = dataTransfer.files;
            }

            // Set confirmed state
            isGcashPaymentConfirmed = true;
            window.isGcashPaymentConfirmed = true; // Make it globally available

            // Update UI
            modal.style.display = 'none';
            showNotification('Success', 'Payment details confirmed. You can now place your order.', 'success');

            // Update payment method display
            const gcashMethod = document.querySelector('.payment-method[data-method="gcash"]');
            if (gcashMethod) {
                gcashMethod.classList.add('confirmed');
                gcashMethod.style.pointerEvents = 'none'; // Disable further clicks

                // Add or update confirmation message
                let confirmationText = gcashMethod.querySelector('.payment-confirmed');
                if (!confirmationText) {
                    confirmationText = document.createElement('div');
                    confirmationText.className = 'payment-confirmed';
                    gcashMethod.appendChild(confirmationText);
                }
                confirmationText.innerHTML = '<i class="fas fa-check-circle"></i> Payment details confirmed';

                // Disable COD option
                const codMethod = document.querySelector('.payment-method[data-method="cod"]');
                if (codMethod) {
                    codMethod.style.opacity = '0.5';
                    codMethod.style.pointerEvents = 'none';
                }
            }
        });
    }

    // Handle payment proof preview
    const modalPaymentProof = document.getElementById('modal_payment_proof');
    const previewContainer = document.getElementById('previewContainer');
    const imagePreview = document.getElementById('imagePreview');

    if (modalPaymentProof && previewContainer && imagePreview) {
        modalPaymentProof.addEventListener('change', function(e) {
            const file = this.files[0];
            if (!file) {
                previewContainer.classList.remove('show');
                return;
            }

            // Validate file
            if (!file.type.match(/^image\/(jpeg|png|gif)$/)) {
                showNotification('Error', 'Please select a valid image file (JPG, PNG, or GIF)', 'error');
                this.value = '';
                previewContainer.classList.remove('show');
                return;
            }

            if (file.size > 2 * 1024 * 1024) {
                showNotification('Error', 'Image size must be less than 2MB', 'error');
                this.value = '';
                previewContainer.classList.remove('show');
                return;
            }

            // Show preview
            const reader = new FileReader();
            reader.onload = function(e) {
                imagePreview.src = e.target.result;
                previewContainer.classList.add('show');
            };
            reader.readAsDataURL(file);
        });
        
        // Reset preview when modal is closed
        closeButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                if (!isGcashPaymentConfirmed) {
                    modalPaymentProof.value = '';
                    previewContainer.classList.remove('show');
                }
            });
        });
    }
});
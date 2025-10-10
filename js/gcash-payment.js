// GCash Payment Handling
document.addEventListener('DOMContentLoaded', function() {
    // Initialize payment method selection
    document.querySelectorAll('.payment-method').forEach(method => {
        method.addEventListener('click', function() {
            // Remove selected class from all methods
            document.querySelectorAll('.payment-method').forEach(m => {
                m.classList.remove('selected');
            });
            
            // Add selected class to clicked method
            this.classList.add('selected');

            // Toggle GCash payment form
            const gcashForm = document.getElementById('gcashPaymentForm');
            if (this.getAttribute('data-method') === 'gcash') {
                gcashForm.style.display = 'block';
                // Update amount in instructions
                document.getElementById('gcashAmount').textContent = 
                    document.getElementById('totalAmount').textContent;
            } else {
                gcashForm.style.display = 'none';
            }
        });
    });

    // Handle file preview
    document.getElementById('paymentProof').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;

        // Validate file type
        if (!['image/jpeg', 'image/png'].includes(file.type)) {
            showNotification('Error', 'Please upload only JPG or PNG files', 'error');
            this.value = '';
            return;
        }

        // Validate file size (2MB)
        if (file.size > 2 * 1024 * 1024) {
            showNotification('Error', 'File size must be less than 2MB', 'error');
            this.value = '';
            return;
        }

        // Show preview
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('imagePreview');
            preview.src = e.target.result;
            document.getElementById('previewContainer').classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    });
});

// Process GCash Payment
async function processGCashPayment(orderId) {
    const form = document.getElementById('gcashPaymentForm');
    const formData = new FormData(form);
    formData.append('order_id', orderId);

    try {
        const response = await fetch('process_gcash_payment.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        
        if (result.status === 'success') {
            showNotification('Success', 'Payment proof uploaded successfully. Please wait for verification.', 'success');
            // Redirect to order confirmation page
            setTimeout(() => {
                window.location.href = 'order_confirmation.php?order_id=' + orderId;
            }, 2000);
        } else {
            showNotification('Error', result.message, 'error');
        }
    } catch (error) {
        showNotification('Error', 'An error occurred while processing your payment', 'error');
        console.error('Error:', error);
    }
}
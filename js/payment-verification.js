// Payment status constants
const PAYMENT_STATUS = {
    PENDING: 'pending_verification',
    VERIFIED: 'verified',
    INVALID: 'invalid'
};

// Create loading overlay
function createLoadingOverlay() {
    const overlay = document.createElement('div');
    overlay.className = 'payment-loading-overlay';
    overlay.innerHTML = `
        <div class="payment-loading-content">
            <div class="spinner"></div>
            <h2>Payment Verification in Progress</h2>
            <p>Please wait while our crew verifies your payment...</p>
            <div class="status-steps">
                <div class="step active">
                    <i class="fas fa-receipt"></i>
                    <span>Payment Submitted</span>
                </div>
                <div class="step">
                    <i class="fas fa-clock"></i>
                    <span>Awaiting Verification</span>
                </div>
                <div class="step">
                    <i class="fas fa-check-circle"></i>
                    <span>Payment Verified</span>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(overlay);
    return overlay;
}

// Show loading overlay
function showLoadingOverlay() {
    const overlay = createLoadingOverlay();
    overlay.style.display = 'flex';
}

// Update loading status
function updateLoadingStatus(status, message) {
    const overlay = document.querySelector('.payment-loading-overlay');
    if (!overlay) return;

    const steps = overlay.querySelectorAll('.step');
    const statusMessage = overlay.querySelector('p');

    switch (status) {
        case PAYMENT_STATUS.PENDING:
            steps[1].classList.add('active');
            statusMessage.textContent = message || 'Our crew is verifying your payment...';
            break;
        case PAYMENT_STATUS.VERIFIED:
            steps.forEach(step => step.classList.add('active'));
            statusMessage.textContent = message || 'Payment verified! Redirecting to order confirmation...';
            overlay.querySelector('.payment-loading-content').classList.add('success');
            break;
        case PAYMENT_STATUS.INVALID:
            overlay.querySelector('.payment-loading-content').classList.add('error');
            statusMessage.textContent = message || 'Payment verification failed. Please try again.';
            break;
    }
}

// Check payment status
async function checkPaymentStatus(orderId) {
    try {
        const response = await fetch(`check_payment_status.php?order_id=${orderId}`);
        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Error checking payment status:', error);
        return { status: 'error', message: 'Failed to check payment status' };
    }
}

// Start payment verification process
async function startPaymentVerification(orderId) {
    showLoadingOverlay();
    let attempts = 0;
    const maxAttempts = 60; // 5 minutes (checking every 5 seconds)

    const checkStatus = async () => {
        attempts++;
        const result = await checkPaymentStatus(orderId);

        if (result.status === 'success') {
            switch (result.payment_status) {
                case PAYMENT_STATUS.VERIFIED:
                    updateLoadingStatus(PAYMENT_STATUS.VERIFIED);
                    setTimeout(() => {
                        window.location.href = `order_confirmation.php?order_id=${orderId}`;
                    }, 2000);
                    return;
                case PAYMENT_STATUS.INVALID:
                    updateLoadingStatus(PAYMENT_STATUS.INVALID);
                    setTimeout(() => {
                        window.location.href = 'checkout.php';
                    }, 3000);
                    return;
            }
        }

        // Continue checking if still pending and not exceeded max attempts
        if (attempts < maxAttempts) {
            updateLoadingStatus(PAYMENT_STATUS.PENDING, 
                `Verification in progress (${Math.floor((maxAttempts - attempts) / 12)} minutes remaining)...`);
            setTimeout(checkStatus, 5000); // Check every 5 seconds
        } else {
            // Timeout after 5 minutes
            updateLoadingStatus(PAYMENT_STATUS.PENDING, 
                'Verification is taking longer than usual. Please check your order status page.');
            setTimeout(() => {
                window.location.href = 'order_history.php';
            }, 3000);
        }
    };

    checkStatus();
}
<?php
session_start();
require_once "connect.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: loginpage.php");
    exit();
}

// Get user information
$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT FirstName, LastName, Email, phone, address, verification_status FROM users WHERE Id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Check if user is verified
$isVerified = $user['verification_status'] === 'approved';

// Check if required information is complete
$isProfileComplete = !empty($user['phone']) && !empty($user['address']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - K-Food Delight</title>
    <link rel="stylesheet" href="css/modern-style.css">
    <link rel="stylesheet" href="css/navbar-modern.css">
    <link rel="stylesheet" href="css/cart.css">
    <link rel="stylesheet" href="css/cart-breakdown.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script>
        function showNotification(title, message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                <div class="notification-content">
                    <p><strong>${title}</strong></p>
                    <p>${message}</p>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Slide in animation
            setTimeout(() => notification.classList.add('show'), 10);
            
            // Remove after 3 seconds
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
    </script>
    <style>
        .verification-required {
            background: #fff3cd;
            color: #856404;
            padding: 15px 20px;
            border-radius: 8px;
            margin: 20px auto;
            max-width: 600px;
            text-align: center;
            border: 1px solid #ffeeba;
        }

        .verification-required a {
            color: #ff6b6b;
            text-decoration: none;
            font-weight: bold;
        }

        .verification-required a:hover {
            text-decoration: underline;
        }

        .notifications-container {
            position: relative;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto 20px;
            padding: 0 20px;
        }

        .alert-box {
            background: white;
            border-radius: 8px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .alert-content {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            gap: 15px;
        }

        .alert-text {
            flex: 1;
        }

        .alert-text strong {
            display: block;
            margin-bottom: 5px;
            font-size: 1rem;
        }

        .alert-text p {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
        }

        .alert-box.warning {
            border-left: 4px solid #ff9800;
            background-color: #fff8e1;
        }

        .alert-box.info {
            border-left: 4px solid #2196F3;
            background-color: #e3f2fd;
        }

        .alert-box i {
            font-size: 1.5rem;
        }

        .alert-box.warning i {
            color: #ff9800;
        }

        .alert-box.info i {
            color: #2196F3;
        }

        .alert-btn {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .alert-btn {
            background-color: #ff9800;
            color: white;
        }

        .alert-btn.info-btn {
            background-color: #2196F3;
            color: white;
        }

        .alert-btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        /* Original notification style kept for other notifications */
        .notification {
            background: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 1000;
            transform: translateX(120%);
            transition: transform 0.3s ease-out;
            border-left: 4px solid #4CAF50;
        }

        .notification.show {
            transform: translateX(0);
        }

        .notification.success {
            border-left-color: #4CAF50;
            background-color: #f8fdf8;
        }

        .notification.error {
            border-left-color: #f44336;
            background-color: #fef8f8;
        }

        .notification i {
            font-size: 20px;
            color: #4CAF50;
        }

        .notification.error i {
            color: #f44336;
        }

        .notification.warning {
            border-left-color: #ff9800;
            background-color: #fff8e1;
        }

        .notification.warning i {
            color: #ff9800;
        }

        .verify-link {
            display: inline-block;
            margin-top: 10px;
            padding: 8px 16px;
            background-color: #ff9800;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s ease;
        }

        .verify-link:hover {
            background-color: #f57c00;
        }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        .checkout-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            font-family: 'Poppins', sans-serif;
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }

        .checkout-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #eee;
            transition: all 0.3s ease;
        }

        .checkout-section:hover {
            border-color: #ff6b6b;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255,107,107,0.1);
        }

        .section-title {
            font-size: 1.2rem;
            color: #333;
            margin-bottom: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-title i {
            color: #ff6b6b;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #666;
            font-size: 0.9rem;
        }

        .form-group input, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            font-size: 0.95rem;
            background: #f8f9fa;
            transition: all 0.3s ease;
        }

        .form-group input:hover, .form-group textarea:hover {
            border-color: #ff6b6b;
        }

        .form-group input:focus, .form-group textarea:focus {
            border-color: #ff6b6b;
            outline: none;
            box-shadow: 0 0 0 3px rgba(255,107,107,0.1);
        }

        /* Payment Methods */
        .payment-methods {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 15px;
            max-width: 600px;
        }

        .payment-method {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            background: #ffffff;
            position: relative;
        }

        .payment-method:hover {
            border-color: #ff6b6b;
            background: #fff9f9;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255,107,107,0.1);
        }

        .payment-method.selected {
            border-color: #ff6b6b;
            background: #fff9f9;
        }

        .payment-method.selected::after {
            content: '✓';
            position: absolute;
            right: 12px;
            color: #ff6b6b;
            font-size: 14px;
            font-weight: bold;
        }

        .payment-icon {
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            border-radius: 6px;
            padding: 6px;
        }

        .payment-icon img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .payment-info {
            flex: 1;
        }

        .payment-info strong {
            display: block;
            font-size: 0.95rem;
            color: #333;
            margin-bottom: 2px;
        }

        .payment-info p {
            font-size: 0.8rem;
            color: #666;
            margin: 0;
        }

        /* Cart Summary */
        .cart-summary {
            margin-bottom: 20px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            color: #666;
            font-size: 0.95rem;
        }

        .summary-total {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        /* Place Order Button */
        .place-order-btn {
            width: 100%;
            padding: 15px;
            background: #ff6b6b;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 20px;
        }

        .place-order-btn:hover {
            background: #ff5252;
            transform: translateY(-1px);
        }

        .place-order-btn:disabled {
            background: #ffb3b3;
            cursor: not-allowed;
            transform: none;
            opacity: 0.7;
        }
        
        .place-order-btn.success {
            background: #4CAF50;
            cursor: default;
            pointer-events: none;
        }
        
        .place-order-btn i {
            margin-right: 8px;
        }

        @media (max-width: 968px) {
            .checkout-container {
                grid-template-columns: 1fr;
                padding: 15px;
            }
        }

        .page-header {
            background: white;
            padding: 20px;
            position: relative;
            margin-bottom: 30px;
            border-bottom: 1px solid #eee;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: white;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 20px;
            background-color: #ff6b6b;
            transition: all 0.3s ease;
            border: none;
        }

        .back-btn:hover {
            background-color: #ff5252;
            transform: translateX(-2px);
        }

        .back-btn i {
            font-size: 0.9em;
            transition: transform 0.3s ease;
        }

        .back-btn:hover i {
            transform: translateX(-2px);
        }

        .page-title {
            color: #ff5252;
            font-size: 2.2rem;
            font-weight: 700;
            text-align: center;
            margin: 15px 0;
            font-family: 'Poppins', sans-serif;
            letter-spacing: -0.5px;
        }

        @media (max-width: 768px) {
            .form-row, .payment-methods {
                grid-template-columns: 1fr;
            }

            .page-title {
                font-size: 2rem;
            }

            .back-btn {
                font-size: 0.9rem;
                padding: 6px 12px;
            }
        }
    </style>
</head>
<body>
    <div class="page-header">
        <a href="index.php" class="back-btn">
            <i class="fas fa-arrow-left"></i>
            <span>Back to Menu</span>
        </a>
        <h1 class="page-title">Checkout</h1>
    </div>

    <div class="notifications-container">
        <?php if (!$isVerified): ?>
        <div class="alert-box warning">
            <div class="alert-content">
                <i class="fas fa-exclamation-triangle"></i>
                <div class="alert-text">
                    <strong>Account Verification Required</strong>
                    <p>Please verify your account to place orders. This helps us ensure a secure ordering process.</p>
                </div>
                <a href="profile.php#verification" class="alert-btn">Go to Verification</a>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!$isProfileComplete): ?>
        <div class="alert-box info">
            <div class="alert-content">
                <i class="fas fa-exclamation-circle"></i>
                <div class="alert-text">
                    <strong>Profile Incomplete</strong>
                    <p>Please update your phone number and delivery address to proceed with checkout.</p>
                </div>
                <a href="profile.php" class="alert-btn info-btn">Complete Profile</a>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="checkout-container">
        <!-- Billing Information -->
        <div class="checkout-section">
            <h2 class="section-title">
                <i class="fas fa-user"></i>
                Billing Information
            </h2>
            <div class="form-row">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" value="<?php echo htmlspecialchars($user['FirstName']); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" value="<?php echo htmlspecialchars($user['LastName']); ?>" readonly>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" value="<?php echo htmlspecialchars($user['Email']); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" value="<?php echo htmlspecialchars($user['phone']); ?>" readonly>
                </div>
            </div>
        </div>

        <!-- Delivery Information -->
        <div class="checkout-section">
            <h2 class="section-title">
                <i class="fas fa-shipping-fast"></i>
                Delivery Information
            </h2>
            <div class="form-group">
                <label>Delivery Address</label>
                <input type="text" value="<?php echo htmlspecialchars($user['address']); ?>" readonly>
            </div>
            <div class="form-group">
                <label>Delivery Instructions (Optional)</label>
                <textarea placeholder="Additional instructions for delivery..." rows="3"></textarea>
            </div>
        </div>

        <!-- Order Summary -->
        <div class="checkout-section">
            <h2 class="section-title">
                <i class="fas fa-shopping-cart"></i>
                Order Summary
            </h2>
            <div id="orderItems" class="cart-summary">
                <!-- Will be populated by JavaScript -->
            </div>
        </div>

        <!-- Payment Method -->
        <div class="checkout-section">
            <h2 class="section-title">
                <i class="fas fa-credit-card"></i>
                Payment Method
            </h2>
            <div class="payment-methods">
                <div class="payment-method" data-method="cod">
                    <div class="payment-icon">
                        <img src="images/cash-icon.png" alt="Cash on Delivery">
                    </div>
                    <div class="payment-info">
                        <strong>Cash on Delivery</strong>
                        <p>Pay with cash</p>
                    </div>
                </div>
                <div class="payment-method" data-method="gcash">
                    <div class="payment-icon">
                        <img src="images/gcash-icon.png" alt="GCash">
                    </div>
                    <div class="payment-info">
                        <strong>GCash</strong>
                        <p>Pay via GCash</p>
                    </div>
                </div>
            </div>
        </div>

        <button id="placeOrderBtn" class="place-order-btn" <?php echo (!$isProfileComplete || !$isVerified) ? 'disabled' : ''; ?> 
                data-verified="<?php echo $isVerified ? 'true' : 'false'; ?>"
                data-profile-complete="<?php echo $isProfileComplete ? 'true' : 'false'; ?>">
            Place Order
        </button>
    </div>

    <script>
        // Payment method selection
        document.querySelectorAll('.payment-method').forEach(method => {
            method.addEventListener('click', () => {
                document.querySelectorAll('.payment-method').forEach(m => m.classList.remove('selected'));
                method.classList.add('selected');
            });
        });

        // Load selected cart items from sessionStorage
        function loadCartData() {
            const checkoutItems = JSON.parse(sessionStorage.getItem('checkoutItems') || '[]');
            const orderItems = document.getElementById('orderItems');
            
            if (!orderItems || checkoutItems.length === 0) {
                window.location.href = 'index.php';
                return;
            }
            
            if (!orderItems) return;

            let html = '<div class="cart-breakdown">';
            let total = 0;

            checkoutItems.forEach(item => {
                const itemTotal = item.price * item.quantity;
                total += itemTotal;
                html += `
                    <div class="breakdown-item">
                        <div class="item-detail">
                            <span class="item-name">${item.name} (${item.quantity})</span>
                            <span class="item-dots"></span>
                            <span class="item-price">₱${itemTotal.toFixed(2)}</span>
                        </div>
                    </div>
                `;
            });

            html += `
                <div class="breakdown-divider"></div>
                <div class="breakdown-total">
                    <span class="total-label">Total:</span>
                    <span class="total-amount">₱${total.toFixed(2)}</span>
                </div>
            </div>`;

            orderItems.innerHTML = html;
        }

        // Handle place order
        const placeOrderBtn = document.getElementById('placeOrderBtn');
        if (placeOrderBtn) {
            placeOrderBtn.addEventListener('click', async () => {
                // Check verification status
                if (placeOrderBtn.dataset.verified !== 'true') {
                    showNotification('Verification Required', 'Please verify your account before placing an order.', 'warning');
                    return;
                }
                
                // Check profile completion
                if (placeOrderBtn.dataset.profileComplete !== 'true') {
                    showNotification('Profile Incomplete', 'Please complete your profile before placing an order.', 'warning');
                    return;
                }
                const selectedPayment = document.querySelector('.payment-method.selected');
                if (!selectedPayment) {
                    showNotification('Warning', 'Please select a payment method', 'error');
                    return;
                }

                const paymentMethod = selectedPayment.dataset.method;
                const deliveryInstructions = document.querySelector('textarea').value || '';
                const cartData = JSON.parse(sessionStorage.getItem('cart') || '[]');

                if (cartData.length === 0) {
                    showNotification('Warning', 'Your cart is empty', 'error');
                    return;
                }

                // Disable button and show loading state
                placeOrderBtn.disabled = true;
                placeOrderBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

                // Set a timeout to prevent infinite loading
                const timeoutId = setTimeout(() => {
                    placeOrderBtn.disabled = false;
                    placeOrderBtn.innerHTML = 'Place Order';
                    showNotification('Error', 'Request timed out. Please try again.', 'error');
                }, 30000); // 30 second timeout

                try {
                    const response = await fetch('save_order.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            paymentMethod,
                            deliveryInstructions,
                            items: cartData,
                            orderTotal: cartData.reduce((sum, item) => sum + (item.price * item.quantity), 0)
                        })
                    });

                    // Clear timeout since we got a response
                    clearTimeout(timeoutId);

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const result = await response.json();

                    if (result.success) {
                        // Show success notification
                        showNotification('Success', 'Your order has been placed successfully!', 'success');
                        
                        // Clear cart but keep the structure
                        sessionStorage.removeItem('cart');
                        loadCartData();
                        
                        // Update button to show success
                        placeOrderBtn.className = 'place-order-btn success';
                        placeOrderBtn.innerHTML = '<i class="fas fa-check"></i> Order Placed Successfully';
                        
                        // Optionally redirect after a delay
                        setTimeout(() => {
                            window.location.href = 'index.php';
                        }, 3000);
                    } else {
                        throw new Error(result.message || 'Failed to place order');
                    }
                } catch (error) {
                    console.error('Order error:', error);
                    showNotification('Error', error.message || 'An error occurred while processing your order', 'error');
                    
                    // Reset button state
                    placeOrderBtn.disabled = false;
                    placeOrderBtn.innerHTML = 'Place Order';
                }
            });
        }

        // Load cart data when page loads
        window.addEventListener('load', loadCartData);
    </script>
</body>
</html>
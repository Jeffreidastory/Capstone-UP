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
                <div class="notification-content" style="color: #333;">
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
        /* Delivery Address Styles */
        select.form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            font-size: 0.95rem;
            background: #f8f9fa;
            transition: all 0.3s ease;
            margin-bottom: 10px;
        }

        select.form-control:hover {
            border-color: #ff6b6b;
        }

        select.form-control:focus {
            border-color: #ff6b6b;
            outline: none;
            box-shadow: 0 0 0 3px rgba(255,107,107,0.1);
        }

        select.form-control.error {
            border-color: #ff4444;
            background-color: #fff8f8;
        }

        select.form-control:invalid {
            border-color: #ff4444;
        }

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
            color: #333;
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

        .payment-confirmed {
            position: absolute;
            bottom: -20px;
            left: 0;
            right: 0;
            text-align: center;
            color: #4CAF50;
            font-size: 0.85rem;
            font-weight: 500;
            padding: 4px;
            background: #f0fff0;
            border-radius: 0 0 8px 8px;
        }

        .payment-confirmed i {
            margin-right: 4px;
        }

        .payment-method.confirmed {
            border-color: #4CAF50;
            background: #f0fff0;
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
            transition: all 0.3s ease;
        }

        .place-order-btn.awaiting-verification {
            background: #ffa726;
            cursor: default;
            pointer-events: none;
        }
        
        .place-order-btn i {
            margin-right: 8px;
            animation: spinAround 1s linear infinite;
        }

        .place-order-btn.success i,
        .place-order-btn.awaiting-verification i {
            animation: none;
        }

        @keyframes spinAround {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Loading state for place order button */
        .place-order-btn:disabled {
            background: #ffb3b3;
            cursor: not-allowed;
            opacity: 0.7;
        }

        .place-order-btn:disabled i {
            animation: spinAround 1s linear infinite;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            position: relative;
            background-color: #fff;
            margin: 5% auto;
            padding: 0;
            width: 90%;
            max-width: 600px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            animation: slideIn 0.3s ease;
        }

        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h4 {
            margin: 0;
            color: #333;
            font-size: 1.5rem;
        }

        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .close:hover {
            color: #ff6b6b;
        }

        .modal-body {
            padding: 20px;
            max-height: 70vh;
            overflow-y: auto;
        }

        .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        /* Error states for GCash form inputs */
        .form-control.error {
            border-color: #ff4444;
            background-color: #fff8f8;
        }

        .form-control.error:focus {
            box-shadow: 0 0 0 2px rgba(255, 68, 68, 0.2);
        }

        .file-upload-wrapper .error {
            border-color: #ff4444;
            background-color: #fff8f8;
        }

        /* Add a red asterisk for required fields */
        .required-field::after {
            content: '*';
            color: #ff4444;
            margin-left: 4px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-secondary {
            background-color: #e0e0e0;
            color: #333;
            border: none;
        }

        .btn-primary {
            background-color: #ff6b6b;
            color: white;
            border: none;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .btn-secondary:hover {
            background-color: #d0d0d0;
        }

        .btn-primary:hover {
            background-color: #ff5252;
        }

        .payment-options {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin: 15px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .qr-option, .number-option {
            padding: 15px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .qr-option p, .number-option p {
            margin-bottom: 10px;
            color: #333;
        }

        .gcash-number {
            font-size: 1.2rem;
            font-weight: 600;
            color: #ff6b6b;
            padding: 10px;
            background: #fff0f0;
            border-radius: 6px;
            text-align: center;
            margin: 10px 0;
            letter-spacing: 1px;
        }

        .qr-image {
            max-width: 200px;
            margin: 20px auto;
            display: block;
        }

        .preview-image {
            max-width: 100%;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .numbered-list {
            padding-left: 20px;
            list-style-type: decimal;
        }

        .numbered-list li {
            margin-bottom: 10px;
            color: #555;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from { transform: translateY(-10%); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* Delivery Address Styles */
        select.form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            font-size: 0.95rem;
            background: #f8f9fa;
            transition: all 0.3s ease;
            margin-bottom: 10px;
        }

        select.form-control:hover {
            border-color: #ff6b6b;
        }

        select.form-control:focus {
            border-color: #ff6b6b;
            outline: none;
            box-shadow: 0 0 0 3px rgba(255,107,107,0.1);
        }

        select.form-control.error {
            border-color: #ff4444;
            background-color: #fff8f8;
        }

        select.form-control:invalid {
            border-color: #ff4444;
        }

        .delivery-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .btn-add-address {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #ff6b6b;
            text-decoration: none;
            font-size: 0.9rem;
            padding: 8px 12px;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .btn-add-address:hover {
            background: #fff0f0;
            color: #ff5252;
        }

        .btn-add-address i {
            font-size: 0.9rem;
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
                <div class="delivery-header">
                    <label>Delivery Address</label>
                    <a href="profile.php" class="btn-add-address">
                        <i class="fas fa-plus"></i> Add New Address
                    </a>
                </div>
                <?php
                // Fetch user's delivery addresses
                $stmt = $conn->prepare("SELECT id, label, street_address, barangay, city, province, zip_code FROM delivery_addresses WHERE user_id = ? ORDER BY label ASC");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                $addresses = $result->fetch_all(MYSQLI_ASSOC);
                ?>
                <select id="delivery_address" name="delivery_address" class="form-control" required>
                    <option value="">Select a delivery address</option>
                    <?php foreach ($addresses as $address): ?>
                        <option value="<?php echo $address['id']; ?>">
                            <?php echo htmlspecialchars($address['label'] . ' - ' . 
                                     $address['street_address'] . ', ' . 
                                     $address['barangay'] . ', ' . 
                                     $address['city'] . ', ' . 
                                     $address['province'] . ' ' . 
                                     $address['zip_code']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

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

            <!-- GCash Payment Form -->
            <div id="gcashForm" style="display: none;" class="mt-4">
                <div class="gcash-instructions bg-white p-4 rounded shadow-sm mb-4">
                    <h4 class="mb-3">GCash Payment Instructions</h4>
                    <ol class="list-decimal pl-4">
                        <li>Open your GCash app and scan this QR code:</li>
                        <div class="qr-container text-center my-3">
                            <img src="images/gcash-qr.png" alt="GCash QR Code" class="max-w-xs">
                        </div>
                        <li>Enter the exact amount: </span></li>
                        <li>Complete the payment in your GCash app</li>
                        <li>Take a screenshot of your payment receipt</li>
                        <li>Upload the screenshot and enter the reference number below</li>
                    </ol>
                </div>

                <div class="form-group">
                    <label for="referenceNumber" class="block text-sm font-medium mb-2">Reference Number</label>
                    <input type="text" id="referenceNumber" name="reference_number" 
                           class="form-control" required 
                           placeholder="Enter GCash Reference Number">
                </div>

                <div class="form-group">
                    <label for="paymentProof" class="block text-sm font-medium mb-2">Payment Screenshot</label>
                    <div class="file-upload-wrapper">
                        <input type="file" id="paymentProof" name="payment_proof" 
                               accept="image/jpeg,image/png" required 
                               class="form-control">
                        <small class="text-muted">Accepted formats: JPG, PNG (Max 2MB)</small>
                    </div>
                    <div id="previewContainer" class="mt-3 hidden">
                        <img id="imagePreview" class="max-w-xs rounded shadow-sm">
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

    <!-- GCash Payment Modal -->
    <div id="gcashModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h4>GCash Payment Instructions</h4>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <div class="gcash-instructions mb-4">
                    <ol class="numbered-list">
                        <li>Choose one of these payment methods:
                            <div class="payment-options">
                                <div class="qr-option">
                                    <p><strong>Option 1:</strong> Scan QR Code</p>
                                    <div class="qr-container text-center my-3">
                                        <img src="images/gcash-qr.png" alt="GCash QR Code" class="qr-image">
                                    </div>
                                </div>
                                <div class="number-option">
                                    <p><strong>Option 2:</strong> Send to GCash Number</p>
                                    <p class="gcash-number">09944767382</p>
                                </div>
                            </div>
                        </li>
                        <li>Enter the exact amount: ₱<span id="gcashAmount">0.00</span></li>
                        <li>Complete the payment in your GCash app</li>
                        <li>Take a screenshot of your payment receipt</li>
                        <li>Upload the screenshot and enter the reference number below</li>
                    </ol>
                </div>

                <div class="form-group">
                    <label for="referenceNumber">Reference Number</label>
                    <input type="text" id="referenceNumber" name="reference_number" 
                           class="form-control" required 
                           placeholder="Enter GCash Reference Number">
                </div>

                <div class="form-group">
                    <label for="paymentProof">Payment Screenshot</label>
                    <div class="file-upload-wrapper">
                        <input type="file" id="paymentProof" name="payment_proof" 
                               accept="image/jpeg,image/png" required 
                               class="form-control">
                        <small class="text-muted">Accepted formats: JPG, PNG (Max 2MB)</small>
                    </div>
                    <div id="previewContainer" class="mt-3 hidden">
                        <img id="imagePreview" class="preview-image">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary close-modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmGcashPayment">Confirm Payment</button>
            </div>
        </div>
    </div>

    <script>
        // Wait for DOM to be ready
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize modal elements
            const modal = document.getElementById('gcashModal');
            const confirmButton = document.getElementById('confirmGcashPayment');
            const closeButtons = document.querySelectorAll('.close, .close-modal');
            const previewContainer = document.getElementById('previewContainer');
            const imagePreview = document.getElementById('imagePreview');
            let isGcashPaymentConfirmed = false;

            if (!modal || !confirmButton) {
                console.error('Required modal elements not found');
                return;
            }
            const gcashForm = document.getElementById('gcashPaymentForm');
            if (gcashForm) {
                gcashForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    return false;
                });

                // Prevent enter key from submitting
                gcashForm.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        return false;
                    }
                });
            }
        });

        // Form validation function
        function validateCheckoutForm() {
            const deliveryAddress = document.getElementById('delivery_address');
            let isValid = true;

            // Check delivery address
            if (!deliveryAddress.value) {
                deliveryAddress.classList.add('error');
                showNotification('Error', 'Please select a delivery address', 'error');
                deliveryAddress.focus();
                isValid = false;
            } else {
                deliveryAddress.classList.remove('error');
            }

            return isValid;
        }

        // Payment method selection
        // Modal functionality
        const modal = document.getElementById('gcashModal');
        const closeButtons = document.querySelectorAll('.close, .close-modal');
        const confirmButton = document.getElementById('confirmGcashPayment');
        let isGcashPaymentConfirmed = false;

        // Handle payment method selection
        document.querySelectorAll('.payment-method').forEach(method => {
            method.addEventListener('click', () => {
                document.querySelectorAll('.payment-method').forEach(m => m.classList.remove('selected'));
                method.classList.add('selected');
                
                if (method.dataset.method === 'gcash') {
                    // Show modal and update amount
                    const cartItems = JSON.parse(sessionStorage.getItem('cart') || '[]');
                    const selectedItems = JSON.parse(sessionStorage.getItem('selectedItems') || '[]');
                    const total = cartItems
                        .filter(item => selectedItems.includes(item.id))
                        .reduce((sum, item) => sum + (item.price * item.quantity), 0);
                    document.getElementById('gcashAmount').textContent = total.toFixed(2);
                    
                    // Reset form only if not confirmed
                    if (!isGcashPaymentConfirmed) {
                        document.getElementById('gcashPaymentForm').reset();
                        document.getElementById('previewContainer').classList.add('hidden');
                    }
                    
                    modal.style.display = 'block';
                }
            });
        });

        // Close modal when clicking close button or outside
        closeButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                modal.style.display = 'none';
                if (!isGcashPaymentConfirmed) {
                    // Deselect GCash if payment wasn't confirmed
                    document.querySelectorAll('.payment-method').forEach(m => m.classList.remove('selected'));
                }
            });
        });

        // Removed window click event to prevent accidental modal closing

        // Handle payment confirmation
            confirmButton?.addEventListener('click', () => {
            const refNumberInput = document.getElementById('referenceNumber');
            const paymentProofInput = document.getElementById('paymentProof');            if (!refNumberInput || !paymentProofInput) {
                showNotification('Error', 'Payment form not properly initialized', 'error');
                return;
            }

            const refNumber = refNumberInput.value.trim();
            const paymentProof = paymentProofInput.files[0];

            // Reset previous error states
            refNumberInput.classList.remove('error');
            paymentProofInput.classList.remove('error');

            let isValid = true;
            let errorMessage = [];

            // Validate reference number
            if (!refNumber || refNumber.length === 0) {
                refNumberInput.classList.add('error');
                errorMessage.push('Please enter the GCash reference number');
                isValid = false;
            }

            // Validate payment proof
            if (!paymentProof) {
                paymentProofInput.classList.add('error');
                errorMessage.push('Please upload the payment screenshot');
                isValid = false;
            } else {
                // Validate file type
                if (!paymentProof.type.match(/^image\/(jpeg|png)$/)) {
                    paymentProofInput.classList.add('error');
                    errorMessage.push('Please select a valid image file (JPG or PNG)');
                    isValid = false;
                }
                // Validate file size (2MB limit)
                if (paymentProof.size > 2 * 1024 * 1024) {
                    paymentProofInput.classList.add('error');
                    errorMessage.push('Image size must be less than 2MB');
                    isValid = false;
                }
            }

            if (!isValid) {
                showNotification('Error', errorMessage.join('\n'), 'error');
                return;
            }

            // All validations passed
            isGcashPaymentConfirmed = true;
            modal.style.display = 'none';
            showNotification('Success', 'GCash payment details confirmed', 'success');
            
            // Update the payment method section to show confirmation
            const gcashMethod = document.querySelector('.payment-method[data-method="gcash"]');
            if (gcashMethod) {
                gcashMethod.classList.add('confirmed');
                const confirmationText = document.createElement('div');
                confirmationText.className = 'payment-confirmed';
                confirmationText.innerHTML = '<i class="fas fa-check-circle"></i> Payment details confirmed';
                gcashMethod.appendChild(confirmationText);
            }
        });

        // Handle payment proof image preview
        const paymentProof = document.getElementById('paymentProof');
        const imagePreview = document.getElementById('imagePreview');
        const previewContainer = document.getElementById('previewContainer');

        if (paymentProof && imagePreview && previewContainer) {
            paymentProof.addEventListener('change', function(e) {
                const file = this.files[0];
                if (file) {
                    if (!file.type.match(/^image\/(jpeg|png)$/)) {
                        showNotification('Error', 'Please select a valid image file (JPG or PNG)', 'error');
                        this.value = '';
                        previewContainer.classList.add('hidden');
                        return;
                    }

                    if (file.size > 2 * 1024 * 1024) {
                        showNotification('Error', 'Image size must be less than 2MB', 'error');
                        this.value = '';
                        previewContainer.classList.add('hidden');
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = function(e) {
                        imagePreview.src = e.target.result;
                        previewContainer.classList.remove('hidden');
                    }
                    reader.readAsDataURL(file);
                } else {
                    previewContainer.classList.add('hidden');
                }
            });
        }

        // Load selected cart items from sessionStorage
        function loadCartData() {
            const selectedItems = JSON.parse(sessionStorage.getItem('selectedItems') || '[]');
            const cartItems = JSON.parse(sessionStorage.getItem('cart') || '[]');
            const orderItems = document.getElementById('orderItems');
            
            if (!orderItems) return;
            
            // Filter cart items to only include selected ones
            const selectedCartItems = cartItems.filter(item => selectedItems.includes(item.id));
            
            if (selectedCartItems.length === 0) {
                orderItems.innerHTML = '<div class="empty-cart">No items selected for checkout</div>';
                return;
            }

            let html = '<div class="cart-breakdown">';
            let total = 0;

            selectedCartItems.forEach(item => {
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
        function validateCheckoutForm() {
            const deliveryAddress = document.getElementById('delivery_address');
            if (!deliveryAddress.value) {
                deliveryAddress.classList.add('error');
                showNotification('Error', 'Please select a delivery address', 'error');
                deliveryAddress.focus();
                return false;
            }
            deliveryAddress.classList.remove('error');
            return true;
        }

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

                // Validate delivery address
                if (!validateCheckoutForm()) {
                    return;
                }

                // Check if delivery address is selected
                const deliveryAddress = document.getElementById('delivery_address');
                if (!deliveryAddress.value) {
                    showNotification('Delivery Address Required', 'Please select a delivery address', 'error');
                    deliveryAddress.focus();
                    return;
                }
                const selectedPayment = document.querySelector('.payment-method.selected');
                if (!selectedPayment) {
                    showNotification('Warning', 'Please select a payment method', 'error');
                    return;
                }

                const paymentMethod = selectedPayment.dataset.method;
                const deliveryInstructions = document.querySelector('textarea').value || '';
                const selectedItems = JSON.parse(sessionStorage.getItem('selectedItems') || '[]');
                const cartItems = JSON.parse(sessionStorage.getItem('cart') || '[]');
                const selectedCartItems = cartItems.filter(item => selectedItems.includes(item.id));

                if (selectedCartItems.length === 0) {
                    showNotification('Warning', 'No items selected for checkout', 'error');
                    return;
                }

                // GCash payment validation
                // For GCash payments, ensure payment was confirmed
                if (paymentMethod === 'gcash') {
                    if (!isGcashPaymentConfirmed) {
                        modal.style.display = 'block';
                        showNotification('Error', 'Please confirm your GCash payment details first', 'error');
                        placeOrderBtn.disabled = false;
                        placeOrderBtn.innerHTML = 'Place Order';
                        return;
                    }
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
                    // Show processing state
                    placeOrderBtn.disabled = true;
                    placeOrderBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing Order...';

                    let formData = new FormData();
                    
                    // Basic order data
                    const orderData = {
                        paymentMethod,
                        deliveryAddressId: document.getElementById('delivery_address').value,
                        deliveryInstructions: document.querySelector('textarea[placeholder="Additional instructions for delivery..."]').value,
                        items: selectedCartItems,
                        orderTotal: selectedCartItems.reduce((sum, item) => sum + (item.price * item.quantity), 0),
                        status: paymentMethod === 'gcash' ? 'awaiting_payment_verification' : 'pending'
                    };

                    formData.append('orderData', JSON.stringify(orderData));

                    // For GCash payments, include payment details
                    if (paymentMethod === 'gcash') {
                        if (!isGcashPaymentConfirmed) {
                            showNotification('Error', 'Please confirm your GCash payment details first', 'error');
                            modal.style.display = 'block';
                            placeOrderBtn.disabled = false;
                            placeOrderBtn.innerHTML = 'Place Order';
                            return;
                        }
                        formData.append('reference_number', document.getElementById('referenceNumber').value.trim());
                        formData.append('payment_proof', document.getElementById('paymentProof').files[0]);
                    }

                    const response = await fetch('save_order.php', {
                        method: 'POST',
                        body: formData
                    });

                    // Clear timeout since we got a response
                    clearTimeout(timeoutId);

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const result = await response.json();

                    if (result.success) {
                        // Show appropriate success message based on payment method
                        const successMessage = paymentMethod === 'gcash' 
                            ? 'Order placed successfully! Please wait for payment verification.'
                            : 'Your order has been placed successfully!';
                        
                        showNotification('Success', successMessage, 'success');
                        
                        // Get ordered items and current cart
                        const orderedItems = result.orderedItems;
                        let currentCart = JSON.parse(sessionStorage.getItem('cart') || '[]');
                        
                        // Remove the ordered items from cart
                        const orderedIds = orderedItems.map(item => item.id);
                        currentCart = currentCart.filter(item => !orderedIds.includes(item.id));
                        
                        // Update cart storage
                        sessionStorage.setItem('cart', JSON.stringify(currentCart));
                        sessionStorage.removeItem('checkoutItems');
                        sessionStorage.removeItem('selectedItems');
                        
                        // Update button based on payment method
                        placeOrderBtn.className = 'place-order-btn success';
                        if (paymentMethod === 'gcash') {
                            placeOrderBtn.innerHTML = '<i class="fas fa-clock"></i> Awaiting Payment Verification';
                            placeOrderBtn.style.backgroundColor = '#ffa726'; // Orange color for pending verification
                        } else {
                            placeOrderBtn.innerHTML = '<i class="fas fa-check"></i> Order Placed Successfully';
                        }
                        
                        // Redirect after 3 seconds
                        setTimeout(() => {
                            window.location.href = 'order_confirmation.php?order_id=' + result.orderId;
                        }, 3000);
                        
                        loadCartData();
                        
                        // Stay on the success page
                        // No redirect needed - user can use the back button to return to menu
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
        // Add event listener for address selection changes
        document.getElementById('delivery_address')?.addEventListener('change', function() {
            this.classList.remove('error');
        });

        window.addEventListener('load', loadCartData);
    </script>
</body>
</html>
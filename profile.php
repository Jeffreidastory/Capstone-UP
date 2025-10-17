<?php
require_once "connect.php";
require_once "Session.php";

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id'])) {
    header("Location: loginpage.php");
    exit();
} elseif ($_SESSION['role_id'] != 4) {
    // If not a customer, redirect to appropriate dashboard
    switch($_SESSION['role_id']) {
        case 3: // Crew
            header("Location: kfood_crew/dashboard.php");
            exit();
        case 2: // Admin
            header("Location: kfood_admin/admin_pg.php");
            exit();
        case 1: // Super Admin
            header("Location: kfood_admin/SA_login.php");
            exit();
    }
}

$userId = $_SESSION['user_id'];
$message = '';
$messageType = '';
if (!isset($_SESSION['user_id'])) {
    header("Location: loginpage.php");
    exit();
}

$userId = $_SESSION['user_id'];
$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $firstName = filter_input(INPUT_POST, 'firstName', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $lastName = filter_input(INPUT_POST, 'lastName', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $street_address = filter_input(INPUT_POST, 'street_address', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $barangay = filter_input(INPUT_POST, 'barangay', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $city = filter_input(INPUT_POST, 'city', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $province = filter_input(INPUT_POST, 'province', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $zip_code = filter_input(INPUT_POST, 'zip_code', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    
    // Combine address components
    $address = implode(', ', [
        $street_address,
        $barangay,
        $city,
        $province,
        $zip_code,
        'Philippines'
    ]);

    // Handle profile picture upload
    $profilePicture = null;
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        // Create upload directory if it doesn't exist
        $uploadDir = 'uploaded_img';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        if (in_array($_FILES['profile_picture']['type'], $allowedTypes) && $_FILES['profile_picture']['size'] <= $maxSize) {
            $fileName = uniqid() . '_' . basename($_FILES['profile_picture']['name']);
            $uploadPath = 'uploaded_img/' . $fileName;

            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadPath)) {
                $profilePicture = $fileName;
            }
        }
    }

    // Update database
    try {
        $sql = "UPDATE users SET 
                firstName = ?, 
                lastName = ?, 
                email = ?, 
                phone = ?, 
                address = ?";
        $params = [$firstName, $lastName, $email, $phone, $address];
        
        if ($profilePicture) {
            $sql .= ", profile_picture = ?";
            $params[] = $profilePicture;
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $userId;

        $stmt = $conn->prepare($sql);
        if ($stmt->execute($params)) {
            $message = "Profile updated successfully!";
            $messageType = 'success';
            $_SESSION['email'] = $email; // Update session email
        } else {
            $message = "Error updating profile.";
            $messageType = 'error';
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = 'error';
    }
}

// Fetch current user data
$stmt = $conn->prepare("SELECT id, firstName, lastName, email, username, phone, address, profile_picture, role_id, 
    COALESCE(verification_status, 'none') as verification_status,
    id_document,
    verification_date
FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Initialize default values if not set
if (!$user) {
    $user = [
        'firstName' => '',
        'lastName' => '',
        'email' => '',
        'phone' => '',
        'address' => '',
        'profile_picture' => ''
    ];
} else {
    // Ensure all fields exist
    $defaultFields = [
        'firstName' => '',
        'lastName' => '',
        'email' => '',
        'phone' => '',
        'address' => '',
        'profile_picture' => '',
        'verification_status' => 'none',
        'id_document' => '',
        'verification_date' => null
    ];
    $user = array_merge($defaultFields, $user);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - K-Food Delight</title>
    <link rel="stylesheet" href="css/modern-style.css">
    <link rel="stylesheet" href="css/profile-style.css">
    <link rel="stylesheet" href="css/navbar-modern.css">
    <link rel="stylesheet" href="css/verification.css">
    
    <!-- Modal for Adding/Editing Delivery Address -->
    <div class="modal" id="addressModal" style="display: none;">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2><i class="fas fa-truck"></i> Add Delivery Address</h2>
            <form id="deliveryAddressForm" class="address-form">
                <div class="form-group">
                    <label for="delivery_label">Address Label</label>
                    <input type="text" id="delivery_label" name="label" placeholder="e.g., Home, Office, Parent's House" required>
                </div>
                <div class="form-group">
                    <label for="delivery_street">House/Unit/Room No., Building, Street Name</label>
                    <input type="text" id="delivery_street" name="street_address" required>
                </div>
                <div class="form-group">
                    <label for="delivery_barangay">Subdivision/Barangay</label>
                    <input type="text" id="delivery_barangay" name="barangay" required>
                </div>
                <div class="form-group">
                    <label for="delivery_city">City/Municipality</label>
                    <input type="text" id="delivery_city" name="city" required>
                </div>
                <div class="form-group">
                    <label for="delivery_province">Province</label>
                    <input type="text" id="delivery_province" name="province" required>
                </div>
                <div class="form-group">
                    <label for="delivery_zip">ZIP Code</label>
                    <input type="text" id="delivery_zip" name="zip_code" pattern="[0-9]{4}" required>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Add</button>
                    <button type="button" class="btn btn-secondary" onclick="closeAddressModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    <style>
        /* Styling for the address input */
        .address-group {
            margin-bottom: 20px;
        }
        
        .address-fields {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .address-field input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .address-field input:focus {
            border-color: #4CAF50;
            box-shadow: 0 0 5px rgba(76, 175, 80, 0.2);
            outline: none;
        }
        
        .address-field input:invalid {
            border-color: #ff4444;
        }
        
        .address-field input::placeholder {
            color: #999;
            font-size: 13px;
        }

        /* Delivery Addresses Styles */
        .delivery-addresses-list {
            margin: 15px 0;
        }

        .delivery-address-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            position: relative;
            background: #fff;
        }

        .address-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .address-content {
            color: #666;
            line-height: 1.5;
        }

        .address-actions {
            position: absolute;
            right: 15px;
            top: 15px;
        }

        .btn-action {
            background: none;
            border: none;
            padding: 5px;
            cursor: pointer;
            color: #999;
            font-size: 14px;
        }

        .btn-action:hover {
            color: #ff4444;
        }

        .btn-add-address {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            float: right;
        }

        .btn-add-address:hover {
            background: #45a049;
        }

        /* Permanent Address Styles */
            <style>
        /* Notification Styles */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 16px 20px;
            border-radius: 12px;
            background: #ffffff;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            transform: translateX(120%);
            transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            z-index: 9999;
            min-width: 320px;
            max-width: 450px;
            border: 1px solid #eee;
            opacity: 0;
        }

        .notification.show {
            transform: translateX(0);
            opacity: 1;
        }

        .notification.success {
            background: linear-gradient(145deg, #4CAF50, #45a049);
            color: white;
            border: none;
        }

        .notification.error {
            background: linear-gradient(145deg, #f44336, #e53935);
            color: white;
            border: none;
        }

        .notification i {
            margin-right: 12px;
            font-size: 1.4em;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .notification.success i {
            color: white;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        }

        .notification.error i {
            color: white;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        }

        .notification-content {
            flex-grow: 1;
            margin-right: 10px;
        }

        .notification-title {
            font-weight: 600;
            margin-bottom: 4px;
            font-size: 1.1em;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        .notification-message {
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.95em;
            line-height: 1.4;
        }

        .notification::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            border-radius: 12px;
            background: linear-gradient(rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0));
            pointer-events: none;
        }

        .notification::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: rgba(255, 255, 255, 0.3);
            animation: progress 3s linear forwards;
        }

        @keyframes progress {
            from { width: 100%; }
            to { width: 0%; }
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-2px); }
            20%, 40%, 60%, 80% { transform: translateX(2px); }
        }

        .notification.error.show {
            animation: shake 0.8s ease-in-out;
        }

        .permanent-address-display .address-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            background: #fff;
            position: relative;
        }

        .permanent-address-display .address-content {
            margin-bottom: 15px;
        }

        .permanent-address-display .address-content p {
            margin: 5px 0;
            color: #333;
        }

        .btn-edit {
            background: #f0f0f0;
            border: 1px solid #ddd;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: #666;
        }

        .btn-edit:hover {
            background: #e4e4e4;
            color: #333;
        }

        .form-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .btn-secondary {
            background: #f0f0f0;
            border: 1px solid #ddd;
            color: #666;
        }

        .btn-primary {
            background: #4CAF50;
            border: 1px solid #45a049;
            color: white;
        }

        .btn-secondary, .btn-primary {
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-secondary:hover {
            background: #e4e4e4;
        }

        .btn-primary:hover {
            background: #45a049;
        }
        
        .form-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
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
        }

        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            position: relative;
        }

        .close {
            position: absolute;
            right: 20px;
            top: 15px;
            font-size: 24px;
            cursor: pointer;
        }

        /* Delivery Address Card Styles */
        .delivery-address-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            background: white;
            position: relative;
        }

        .delivery-address-card.default {
            border-color: #4CAF50;
            background: #f8fff8;
        }

        .address-label {
            font-weight: 600;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .address-actions {
            position: absolute;
            right: 15px;
            top: 15px;
            display: flex;
            gap: 10px;
        }

        .btn-action {
            background: none;
            border: none;
            padding: 5px;
            cursor: pointer;
            color: #666;
        }

        .btn-action:hover {
            color: #333;
        }

        .default-badge {
            background: #4CAF50;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
        }
        .profile-section {
            display: none;
        }
        
        .profile-section.active {
            display: block;
        }
        
        .nav-item.active {
            background-color: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }
        
        .profile-navigation .nav-item {
            padding: 12px 20px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .profile-navigation .nav-item:hover {
            background-color: rgba(59, 130, 246, 0.05);
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="app-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <a href="index.php" class="logo">
                    <img src="images/logo.png" alt="K-Food Logo" class="logo-img">
                    <span class="logo-text">K-Food Delight</span>
                </a>
            </div>
            
            <nav class="sidebar-nav">
                <a href="#profile" class="nav-item active" data-section="profileSection">
                    <i class="fas fa-user"></i>
                    <span>Profile</span>
                </a>
                <a href="#security" class="nav-item" data-section="securitySection">
                    <i class="fas fa-shield-alt"></i>
                    <span>Security</span>
                </a>
                <a href="#verification" class="nav-item" data-section="verificationSection">
                    <i class="fas fa-id-card"></i>
                    <span>Verification</span>
                </a>
                <a href="#preferences" class="nav-item" data-section="preferencesSection">
                    <i class="fas fa-cog"></i>
                    <span>Preferences</span>
                </a>
                <a href="#support" class="nav-item" data-section="supportSection">
                    <i class="fas fa-headset"></i>
                    <span>Support</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <a href="index.php" class="back-to-home">
                    <i class="fas fa-home"></i>
                    <span>Back to Home</span>
                </a>
            </div>
        </aside>

        <main class="main-content">
            <header class="content-header">
                <div class="user-welcome">
                    <h1>My Profile</h1>
                    <p class="user-email"><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
                <div class="header-actions">
                    <a href="index.php" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i>
                        Return to Store
                    </a>
                </div>
            </header>

    <div class="profile-container">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="profile-navigation">
            <a href="#profileSection" class="nav-item active" data-section="profileSection">
                <i class="fas fa-user"></i>
                Profile
            </a>
            <a href="#securitySection" class="nav-item" data-section="securitySection">
                <i class="fas fa-shield-alt"></i>
                Security
            </a>
            <a href="#verificationSection" class="nav-item" data-section="verificationSection">
                <i class="fas fa-id-card"></i>
                Verification
            </a>
            <a href="#preferencesSection" class="nav-item" data-section="preferencesSection">
                <i class="fas fa-cog"></i>
                Preferences
            </a>
            <a href="#supportSection" class="nav-item" data-section="supportSection">
                <i class="fas fa-headset"></i>
                Support
            </a>
        </div>

        <div class="profile-stats">
            <div class="stat-item">
                <span class="stat-number">0</span>
                <span class="stat-label">Orders</span>
            </div>
            <div class="stat-item">
                <span class="stat-number">0</span>
                <span class="stat-label">Favorites</span>
            </div>
            <div class="stat-item">
                <span class="stat-number">0</span>
                <span class="stat-label">Reviews</span>
            </div>
        </div>

        <div class="profile-sections">
            <div class="profile-section active" id="profileSection">
                <h2 class="section-title">
                    <i class="fas fa-user-circle"></i>
                    Profile Information
                </h2>
                
                <form class="profile-form" method="POST" action="process_profile_update.php" enctype="multipart/form-data">
                    <div class="profile-picture-section">
                        <div class="profile-picture-container">
                            <?php if (!empty($user['profile_picture']) && file_exists('uploaded_img/' . $user['profile_picture'])): ?>
                                <img src="uploaded_img/<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile Picture" class="profile-picture">
                            <?php else: ?>
                                <i class="fas fa-user-circle default-profile"></i>
                            <?php endif; ?>
                        </div>
                        <div class="profile-picture-upload">
                            <label for="profile_picture" class="upload-button">
                                <i class="fas fa-camera"></i>
                                Change Picture
                            </label>
                            <input type="file" id="profile_picture" name="profile_picture" accept="image/jpeg,image/png,image/gif" hidden>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="firstName">
                                <i class="fas fa-user"></i>
                                First Name
                            </label>
                            <input type="text" id="firstName" name="firstName" value="<?php echo htmlspecialchars($user['firstName']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="lastName">
                                <i class="fas fa-user"></i>
                                Last Name
                            </label>
                            <input type="text" id="lastName" name="lastName" value="<?php echo htmlspecialchars($user['lastName']); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">
                            <i class="fas fa-envelope"></i>
                            Email
                        </label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">
                            <i class="fas fa-phone"></i>
                            Phone Number
                        </label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" 
                               pattern="[0-9]{11}" maxlength="11" 
                               placeholder="09XXXXXXXXX" 
                               title="Please enter a valid 11-digit phone number starting with 09">
                        <small class="form-text text-muted">Format: 09XXXXXXXXX (11 digits)</small>
                    </div>

                    <!-- Permanent Address Section -->
                    <div class="form-group address-group">
                        <h3 class="section-subtitle">
                            <i class="fas fa-home"></i>
                            Permanent Address
                        </h3>
                        
                        <!-- Address Display View -->
                        <div id="permanent-address-display" class="permanent-address-display">
                            <?php if (!empty($user['address'])): ?>
                                <div class="address-card">
                                    <div class="address-content">
                                        <?php 
                                            $addressParts = explode(', ', $user['address']);
                                            if (count($addressParts) >= 5) {
                                                echo "<p>" . htmlspecialchars($addressParts[0]) . "</p>";
                                                echo "<p>" . htmlspecialchars($addressParts[1]) . "</p>";
                                                echo "<p>" . htmlspecialchars($addressParts[2]) . ", " . 
                                                     htmlspecialchars($addressParts[3]) . " " . 
                                                     htmlspecialchars($addressParts[4]) . "</p>";
                                            } else {
                                                echo "<p>" . htmlspecialchars($user['address']) . "</p>";
                                            }
                                        ?>
                                    </div>
                                    <button type="button" class="btn btn-edit" onclick="togglePermanentAddressEdit(true)">
                                        <i class="fas fa-edit"></i> Change Address
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Address Edit Form -->
                        <div id="permanent-address-form" class="address-fields" style="display: <?php echo empty($user['address']) ? 'block' : 'none'; ?>">
                            <div class="address-field">
                                <input type="text" 
                                       id="street_address" 
                                       name="street_address" 
                                       class="form-control" 
                                       placeholder="House/Unit/Room No., Building, Street Name"
                                       required>
                            </div>
                            <div class="address-field">
                                <input type="text" 
                                       id="barangay" 
                                       name="barangay" 
                                       class="form-control" 
                                       placeholder="Subdivision/Barangay"
                                       required>
                            </div>
                            <div class="address-field">
                                <input type="text" 
                                       id="city" 
                                       name="city" 
                                       class="form-control" 
                                       placeholder="City/Municipality"
                                       required>
                            </div>
                            <div class="address-field">
                                <input type="text" 
                                       id="province" 
                                       name="province" 
                                       class="form-control" 
                                       placeholder="Province"
                                       required>
                            </div>
                            <div class="address-field">
                                <input type="text" 
                                       id="zip_code" 
                                       name="zip_code" 
                                       class="form-control" 
                                       placeholder="ZIP Code"
                                       pattern="[0-9]{4}"
                                       title="Please enter a valid 4-digit ZIP code"
                                       required>
                            </div>
                            <div class="form-actions">
                                <?php if (!empty($user['address'])): ?>
                                    <button type="button" class="btn btn-secondary" onclick="togglePermanentAddressEdit(false)">
                                        Cancel
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <small class="form-text text-muted">
                            This is your permanent address for official records.
                        </small>
                    </div>

                    <!-- Delivery Addresses Section -->
                    <div class="form-group delivery-addresses-section">
                        <h3 class="section-subtitle">
                            <i class="fas fa-truck"></i>
                            Delivery Addresses
                            <button type="button" class="btn btn-add-address" onclick="showAddressModal()">
                                <i class="fas fa-plus"></i> Add New Address
                            </button>
                        </h3>
                        
                        <div id="delivery-addresses-list" class="delivery-addresses-list">
                            <!-- Addresses will be loaded here dynamically -->
                        </div>
                    </div>

                    <div class="form-actions">
                        <input type="hidden" name="form_submitted" value="1">
                        <button type="submit" name="update_profile" class="save-button btn btn-primary" onclick="submitProfileForm(event)">
                            <i class="fas fa-check"></i>
                            UPDATE PROFILE
                        </button>
                    </div>
                </form>
            </div>

            <div class="profile-section" id="verificationSection">
                <h2 class="section-title">
                    <i class="fas fa-id-card"></i>
                    Account Verification
                </h2>

                <div class="verification-status">
                    <?php
                    $statusClass = '';
                    $statusText = '';
                    
                    switch($user['verification_status']) {
                        case 'approved':
                            $statusClass = 'status-approved';
                            $statusText = 'Verified';
                            break;
                        case 'rejected':
                            $statusClass = 'status-rejected';
                            $statusText = 'Verification Rejected';
                            break;
                        case 'pending':
                            $statusClass = 'status-pending';
                            $statusText = 'Pending Approval';
                            break;
                        default:
                            $statusClass = 'status-none';
                            $statusText = 'Not Verified';
                    }
                    ?>
                    <div class="status-badge <?php echo $statusClass; ?>">
                        <i class="fas <?php echo $statusClass === 'status-approved' ? 'fa-check-circle' : 
                                          ($statusClass === 'status-rejected' ? 'fa-times-circle' : 
                                          'fa-clock'); ?>"></i>
                        <?php echo $statusText; ?>
                    </div>
                </div>

                <?php if ($user['verification_status'] === 'rejected'): ?>
                <div class="verification-message error">
                    <p>Your verification was rejected. Please submit a new ID document.</p>
                </div>
                <?php endif; ?>

                <div class="verification-instructions">
                    <h3>Verification Requirements:</h3>
                    <ul>
                        <li>Upload a valid government-issued ID</li>
                        <li>Supported formats: JPG, PNG, PDF</li>
                        <li>Maximum file size: 5MB</li>
                        <li>ID must be clear and readable</li>
                    </ul>
                    <p class="note">Note: Orders will be restricted until your account is verified.</p>
                </div>

                <?php if ($user['verification_status'] === 'approved'): ?>
                    <div class="verification-success-message">
                        <i class="fas fa-check-circle"></i>
                        <p>Your account is verified! You have full access to all features.</p>
                        <div class="id-preview-container verified-id-container">
                            <?php if (!empty($user['id_document'])): ?>
                                <img src="uploaded_img/<?php echo htmlspecialchars($user['id_document']); ?>" alt="ID Document" class="id-preview">
                            <?php endif; ?>
                        </div>
                    </div>
                <?php elseif ($user['verification_status'] === 'pending'): ?>
                    <div class="verification-pending-message">
                        <i class="fas fa-clock"></i>
                        <p>Your verification is currently under review. Please wait for the admin's approval.</p>
                        <?php if (!empty($user['id_document'])): ?>
                            <div class="id-preview-container">
                                <img src="uploaded_img/<?php echo htmlspecialchars($user['id_document']); ?>" alt="ID Document" class="id-preview">
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <form class="verification-form" method="POST" enctype="multipart/form-data" action="process_verification.php">
                        <div class="id-upload-section">
                            <div class="id-preview-container" id="idPreviewContainer">
                                <?php if (!empty($user['id_document'])): ?>
                                    <img src="uploaded_img/<?php echo htmlspecialchars($user['id_document']); ?>" alt="ID Document" class="id-preview">
                                <?php else: ?>
                                    <div class="upload-placeholder">
                                        <i class="fas fa-id-card"></i>
                                        <p>No ID document uploaded</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="upload-controls">
                                <label for="id_document" class="upload-button">
                                    <i class="fas fa-upload"></i>
                                    Upload ID Document
                                </label>
                                <input type="file" id="id_document" name="id_document" accept=".jpg,.jpeg,.png,.pdf" hidden>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="submit-button" name="submit_verification">
                                <i class="fas fa-paper-plane"></i>
                                Submit for Verification
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>

            <div class="profile-section" id="securitySection">
                <h2 class="section-title">
                    <i class="fas fa-lock"></i>
                    Security Settings
                </h2>
                
                <form class="security-form" method="POST" action="update_password.php">
                    <div class="form-group">
                        <label for="currentPassword">
                            <i class="fas fa-key"></i>
                            Current Password
                        </label>
                        <div class="password-input">
                            <input type="password" id="currentPassword" name="currentPassword" required>
                            <button type="button" class="toggle-password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="newPassword">
                            <i class="fas fa-lock"></i>
                            New Password
                        </label>
                        <div class="password-input">
                            <input type="password" id="newPassword" name="newPassword" required>
                            <button type="button" class="toggle-password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirmPassword">
                            <i class="fas fa-lock"></i>
                            Confirm New Password
                        </label>
                        <div class="password-input">
                            <input type="password" id="confirmPassword" name="confirmPassword" required>
                            <button type="button" class="toggle-password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="save-button">
                            <i class="fas fa-key"></i>
                            UPDATE PASSWORD
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Delivery Address Management
        let deliveryAddresses = [];

        function loadDeliveryAddresses() {
            fetch('manage_delivery_address.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        deliveryAddresses = data.addresses;
                        renderDeliveryAddresses();
                    }
                })
                .catch(error => console.error('Error loading delivery addresses:', error));
        }

        function renderDeliveryAddresses() {
            const container = document.getElementById('delivery-addresses-list');
            container.innerHTML = '';

            deliveryAddresses.forEach(address => {
                const card = document.createElement('div');
                card.className = `delivery-address-card ${address.is_default ? 'default' : ''}`;
                card.innerHTML = `
                    <div class="address-label">
                        <span>${address.label}</span>
                        ${address.is_default ? '<span class="default-badge">Default</span>' : ''}
                    </div>
                    <div class="address-content">
                        ${address.street_address}<br>
                        ${address.barangay}<br>
                        ${address.city}, ${address.province} ${address.zip_code}
                    </div>
                    <div class="address-actions">
                        ${!address.is_default ? `
                            <button type="button" class="btn-action" onclick="setDefaultAddress(${address.id})">
                                <i class="fas fa-check-circle"></i>
                            </button>` : ''}
                        <button type="button" class="btn-action" onclick="deleteAddress(${address.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                `;
                container.appendChild(card);
            });
        }

        function showAddressModal() {
            document.getElementById('addressModal').style.display = 'block';
        }

        function closeAddressModal() {
            document.getElementById('addressModal').style.display = 'none';
            document.getElementById('deliveryAddressForm').reset();
        }

        function deleteAddress(addressId) {
            if (!confirm('Are you sure you want to delete this delivery address?')) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('address_id', addressId);

            fetch('manage_delivery_address.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    loadDeliveryAddresses();
                }
            })
            .catch(error => console.error('Error deleting address:', error));
        }

        document.getElementById('deliveryAddressForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'add');

            fetch('manage_delivery_address.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    closeAddressModal();
                    loadDeliveryAddresses();
                    showMessage('Address added successfully', 'success');
                } else {
                    showMessage('Failed to add address', 'error');
                }
            })
            .catch(error => {
                console.error('Error adding address:', error);
                showMessage('Failed to add address', 'error');
            });
        });

        // Toggle permanent address edit form
        function togglePermanentAddressEdit(show) {
            const displayDiv = document.getElementById('permanent-address-display');
            const formDiv = document.getElementById('permanent-address-form');
            
            displayDiv.style.display = show ? 'none' : 'block';
            formDiv.style.display = show ? 'block' : 'none';

            if (!show) {
                // Reset form when canceling
                const form = document.querySelector('.profile-form');
                const addressFields = form.querySelectorAll('.address-field input');
                addressFields.forEach(input => input.value = '');
            } else {
                // Pre-fill the form with existing address if available
                const currentAddress = document.querySelector('.address-content');
                if (currentAddress) {
                    const addressParagraphs = currentAddress.querySelectorAll('p');
                    if (addressParagraphs.length >= 3) {
                        document.getElementById('street_address').value = addressParagraphs[0].textContent;
                        document.getElementById('barangay').value = addressParagraphs[1].textContent;
                        
                        // Split the last paragraph which contains city, province, and zip
                        const lastPart = addressParagraphs[2].textContent.split(',');
                        if (lastPart.length >= 2) {
                            document.getElementById('city').value = lastPart[0].trim();
                            const provZip = lastPart[1].trim().split(' ');
                            document.getElementById('province').value = provZip[0];
                            if (provZip.length > 1) {
                                document.getElementById('zip_code').value = provZip[1];
                            }
                        }
                    }
                }
            }
        }

        // Load delivery addresses when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadDeliveryAddresses();
        });

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('addressModal');
            if (event.target === modal) {
                closeAddressModal();
            }
        };

        // Navigation functionality for both sidebar and profile navigation
        function initializeNavigation() {
            const navItems = document.querySelectorAll('.nav-item');
            navItems.forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    const sectionId = this.getAttribute('data-section');
                    
                    // Remove active class from all nav items
                    navItems.forEach(nav => nav.classList.remove('active'));
                    
                    // Add active class to clicked item and its corresponding items
                    document.querySelectorAll(`[data-section="${sectionId}"]`).forEach(nav => {
                        nav.classList.add('active');
                    });
                    
                    // Hide all sections
                    document.querySelectorAll('.profile-section').forEach(section => {
                        section.classList.remove('active');
                    });
                    
                    // Show selected section
                    const targetSection = document.getElementById(sectionId);
                    if (targetSection) {
                        targetSection.classList.add('active');
                    }
                });
            });
        }
        
        // Initialize navigation on page load
        document.addEventListener('DOMContentLoaded', initializeNavigation);

        // Handle profile form submission
        function submitProfileForm(event) {
            event.preventDefault();
            const form = document.querySelector('.profile-form');
            const formData = new FormData(form);
            
            // Add the form_submitted field
            formData.append('form_submitted', '1');

            // Validate address fields if they are visible
            const addressForm = document.getElementById('permanent-address-form');
            if (addressForm && addressForm.style.display !== 'none') {
                const addressFields = [
                    { id: 'street_address', label: 'Street Address' },
                    { id: 'barangay', label: 'Barangay' },
                    { id: 'city', label: 'City' },
                    { id: 'province', label: 'Province' },
                    { id: 'zip_code', label: 'ZIP Code' }
                ];

                for (const field of addressFields) {
                    const value = document.getElementById(field.id).value.trim();
                    if (!value) {
                        showError(`${field.label} is required`);
                        return;
                    }
                }

                // Validate ZIP code
                const zipCode = document.getElementById('zip_code').value;
                if (!/^\d{4}$/.test(zipCode)) {
                    showError('Please enter a valid 4-digit ZIP code');
                    return;
                }
            }
            
            fetch('process_profile_update.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                // Show success message
                showSuccess('Profile Updated', 'Your profile has been successfully updated!');
                
                // Reload the page after a short delay to show the message
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Update Failed', 'An error occurred while updating your profile');
            });
        }

        // Enhanced Notification display function
        function showNotification(title, message, type = 'info') {
            // Container for stacking notifications
            let container = document.querySelector('.notifications-container');
            if (!container) {
                container = document.createElement('div');
                container.className = 'notifications-container';
                document.body.appendChild(container);
            }

            // Remove existing notifications of the same type
            const existingNotifications = document.querySelectorAll(`.notification.${type}`);
            existingNotifications.forEach(notif => {
                notif.classList.remove('show');
                setTimeout(() => notif.remove(), 300);
            });

            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.innerHTML = `
                <i class="fas ${type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'} fa-lg"></i>
                <div class="notification-content">
                    <div class="notification-title">${title}</div>
                    <div class="notification-message">${message}</div>
                </div>
            `;
            
            container.appendChild(notification);
            
            // Add sound effect for notifications
            const audio = new Audio();
            if (type === 'success') {
                audio.src = 'data:audio/mp3;base64,SUQzBAAAAAAAI1RTU0UAAAAPAAADTGF2ZjU4Ljc2LjEwMAAAAAAAAAAAAAAA//tQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAASW5mbwAAAA8AAAASAAAgmgAGBg0NDRQUFBsbIiIiKSkpMDA3NzdERERLS0tSUllZWWBgYGdnbm5udXV1fHyDg4OKioqRkZiYmJ+fn6amra2ttLS0u7vCwsLJyc/Pz9bW3Nzc4+Pp6env7/b29v39AAAAAAAAAAAAAAAAAAAA//sQZAACwAAAf4AAAAgAAA/wAAABAAAB/gAAACAAAD/AAAAETEFNRTMuMTAwVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVX/+xBkaA7wAAB/gAAACAAAD/AAAAEAAAGqAAAAIAAANUAAAARVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVf/7EGR4DvAAAH+AAAAIAAAP8AAABAAABqgAAACAAADVAAAAEVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVU=';
            } else {
                audio.src = 'data:audio/mp3;base64,SUQzBAAAAAAAI1RTU0UAAAAPAAADTGF2ZjU4Ljc2LjEwMAAAAAAAAAAAAAAA//tQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAASW5mbwAAAA8AAAASAAAUqAAGBg0NDRQUFBsbIiIiKSkpMDA3NzdERERLS0tSUllZWWBgYGdnbm5udXV1fHyDg4OKioqRkZiYmJ+fn6amra2ttLS0u7vCwsLJyc/Pz9bW3Nzc4+Pp6env7/b29v39AAAAAAAAAAAAAAAAAAAA//sQZAAOwAAAfQAAAAgAAA/wAAABAAAB/AAAACAAADVAAAAETEFNRTMuMTAwVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVX/+xBkaAPAAAGqAAAAIAAANUAAAAQAAAaoAAAAgAAA1QAAAARVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVf/7EGR4A8AAAaoAAAAgAAA1QAAAAQAABqgAAACAAADVAAAAEVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVU=';
            }
            audio.volume = 0.2;
            audio.play().catch(() => {}); // Ignore autoplay restrictions

            // Show notification with slight delay for better animation
            requestAnimationFrame(() => {
                notification.classList.add('show');
            });
            
            // Remove notification after 3 seconds with progress bar animation
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.remove();
                        // Remove container if empty
                        if (!container.hasChildNodes()) {
                            container.remove();
                        }
                    }
                }, 400);
            }, 3000);

            // Make notification dismissible on click
            notification.addEventListener('click', () => {
                notification.classList.remove('show');
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.remove();
                        if (!container.hasChildNodes()) {
                            container.remove();
                        }
                    }
                }, 400);
            });
        }

        // Shorthand functions for success and error notifications
        function showSuccess(title, message) {
            showNotification(title, message, 'success');
        }

        function showError(title, message) {
            showNotification(title, message, 'error');
        }

        // Preview profile picture before upload
        document.getElementById('profile_picture').addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const profilePic = document.querySelector('.profile-picture') || document.createElement('img');
                    profilePic.src = e.target.result;
                    profilePic.classList.add('profile-picture');
                    
                    const defaultIcon = document.querySelector('.default-profile');
                    if (defaultIcon) {
                        defaultIcon.remove();
                    }
                    
                    const container = document.querySelector('.profile-picture-container');
                    if (!document.querySelector('.profile-picture')) {
                        container.appendChild(profilePic);
                    }
                };
                reader.readAsDataURL(e.target.files[0]);
            }
        });

        // Toggle password visibility
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.previousElementSibling;
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                this.querySelector('i').classList.toggle('fa-eye');
                this.querySelector('i').classList.toggle('fa-eye-slash');
            });
        });

        // Enhanced form validation
        document.querySelector('.profile-form').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            const phone = document.getElementById('phone').value;
            
            // Enhanced email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                showError('Validation Error', 'Please enter a valid email address.');
                return;
            }
            
            // Enhanced phone number validation
            const phoneRegex = /^[0-9\+\-\s]+$/;
            if (phone && !phoneRegex.test(phone)) {
                e.preventDefault();
                showError('Please enter a valid phone number.');
                return;
            }

            // Address validation
            const addressFields = [
                { id: 'street_address', label: 'Street Address' },
                { id: 'barangay', label: 'Barangay' },
                { id: 'city', label: 'City' },
                { id: 'province', label: 'Province' },
                { id: 'zip_code', label: 'ZIP Code' }
            ];

            for (const field of addressFields) {
                const value = document.getElementById(field.id).value.trim();
                if (!value) {
                    e.preventDefault();
                    showError(`${field.label} is required`);
                    return;
                }
            }

            // ZIP code validation
            const zipCode = document.getElementById('zip_code').value;
            if (!/^\d{4}$/.test(zipCode)) {
                e.preventDefault();
                showError('Please enter a valid 4-digit ZIP code');
                return;
            }
        });

        // Password form validation
        document.querySelector('.security-form')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const newPass = document.getElementById('newPassword').value;
            const confirmPass = document.getElementById('confirmPassword').value;

            if (newPass !== confirmPass) {
                showError('New passwords do not match.');
                return;
            }

            if (newPass.length < 8) {
                showError('Password must be at least 8 characters long.');
                return;
            }

            this.submit();
        });

        // Error message display
        function showError(message) {
            showMessage(message, 'error');
        }

        // ID Document preview
        document.getElementById('id_document')?.addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const file = e.target.files[0];
                
                // Validate file size
                if (file.size > 5 * 1024 * 1024) { // 5MB
                    showError('File is too large. Maximum size is 5MB.');
                    e.target.value = '';
                    return;
                }

                // Validate file type
                const validTypes = ['image/jpeg', 'image/png', 'application/pdf'];
                if (!validTypes.includes(file.type)) {
                    showError('Invalid file type. Please upload JPG, PNG, or PDF.');
                    e.target.value = '';
                    return;
                }

                // If it's an image, show preview
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const container = document.getElementById('idPreviewContainer');
                        container.innerHTML = `<img src="${e.target.result}" alt="ID Document" class="id-preview">`;
                    };
                    reader.readAsDataURL(file);
                } else {
                    // For PDF, show an icon
                    const container = document.getElementById('idPreviewContainer');
                    container.innerHTML = `
                        <div class="upload-placeholder">
                            <i class="fas fa-file-pdf"></i>
                            <p>${file.name}</p>
                        </div>
                    `;
                }

                // Enable submit button
                const submitBtn = document.querySelector('button[name="submit_verification"]');
                if (submitBtn) {
                    submitBtn.disabled = false;
                }
            }
        });
    </script>
</body>
</html>
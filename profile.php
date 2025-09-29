<?php
require_once "connect.php";
require_once "Session.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: loginpage.php");
    exit();
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
    $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

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
$stmt = $conn->prepare("SELECT id, firstName, lastName, email, username, phone, address, profile_picture, role_id FROM users WHERE id = ?");
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
        'profile_picture' => ''
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
            <a href="#" class="nav-item active">
                <i class="fas fa-user"></i>
                Profile
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-shield-alt"></i>
                Security
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-cog"></i>
                Preferences
            </a>
            <a href="#" class="nav-item">
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
                
                <form class="profile-form" method="POST" enctype="multipart/form-data">
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
                        <label for="contactNumber">
                            <i class="fas fa-phone"></i>
                            Phone Number
                        </label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" pattern="[0-9]{11}" title="Please enter a valid phone number">
                    </div>

                    <div class="form-group">
                        <label for="deliveryAddress">
                            <i class="fas fa-map-marker-alt"></i>
                            Delivery Address
                        </label>
                        <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($user['address']); ?></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="save-button">
                            <i class="fas fa-check"></i>
                            UPDATE PROFILE
                        </button>
                    </div>
                </form>
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
        // Navigation functionality
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
                this.classList.add('active');
                
                const sectionId = this.getAttribute('href').substring(1);
                document.querySelectorAll('.profile-section').forEach(section => {
                    section.classList.remove('active');
                });
                document.getElementById(sectionId)?.classList.add('active');
            });
        });

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
                showError('Please enter a valid email address.');
                return;
            }
            
            // Enhanced phone number validation
            const phoneRegex = /^[0-9\+\-\s]+$/;
            if (phone && !phoneRegex.test(phone)) {
                e.preventDefault();
                showError('Please enter a valid phone number.');
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
            const alert = document.createElement('div');
            alert.className = 'alert alert-error';
            alert.textContent = message;
            
            const container = document.querySelector('.profile-container');
            container.insertBefore(alert, container.firstChild);
            
            setTimeout(() => alert.remove(), 5000);
        }
    </script>
</body>
</html>
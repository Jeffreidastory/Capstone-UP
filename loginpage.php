


<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering to prevent headers already sent issues
ob_start();

// Set secure headers
header("Content-Security-Policy: default-src 'self' https: 'unsafe-inline'; script-src 'self' https: 'unsafe-inline'; style-src 'self' https: 'unsafe-inline' fonts.googleapis.com; font-src 'self' fonts.gstatic.com;");

include "connect.php";
session_start();

if(isset($_POST["submit"])){
    $username = mysqli_real_escape_string($conn, $_POST["username"]);
    $password = $_POST["password"];

    // Debug information
    error_log("Login attempt - Username: " . $username);
    
    // Check in users table for all account types
    $sql = "SELECT Id as user_id, username, Email, FirstName, LastName, Password, role_id, profile_picture 
            FROM users 
            WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Log attempt
    error_log("Checking for customer account");
    
    // Debug database query
    error_log("Query executed - Found rows: " . $result->num_rows);

    if($result->num_rows > 0){
        $row = $result->fetch_assoc();
        // Debug password verification
        error_log("Stored hash: " . $row["Password"]);
        error_log("Password verification result: " . (password_verify($password, $row["Password"]) ? "true" : "false"));
        
        if(password_verify($password, $row["Password"])){
            // Clear any existing session data
            session_unset();
            
            // Debug the row data
            error_log("Database row data: " . print_r($row, true));
            
            // Set session variables based on the account type
            $_SESSION['user_id'] = (int)$row['user_id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role_id'] = (int)$row['role_id'];

            if($row['role_id'] == 1) {
                // Super admin specific data
                $_SESSION['full_name'] = $row['full_name'];
                $_SESSION['admin_id'] = $row['user_id'];
                $_SESSION['admin_role'] = 1;
                $_SESSION['just_logged_in'] = true;
            } else {
                // Regular user data
                $_SESSION['email'] = $row['Email'];
                $_SESSION['firstName'] = $row['FirstName'];
                $_SESSION['lastName'] = $row['LastName'];
                $_SESSION['profile_picture'] = !empty($row['profile_picture']) ? $row['profile_picture'] : null;
                $_SESSION['login_timestamp'] = time();
                $_SESSION['show_dashboard'] = true;
                error_log('Profile picture set in session: ' . print_r($_SESSION['profile_picture'], true));
            }
            
            // Verify session data was set correctly
            error_log("Session data after setting: " . print_r($_SESSION, true));

            // Debug role information
            error_log("Login successful - Username: " . $username);
            error_log("User ID: " . $_SESSION['user_id']);
            error_log("Role ID: " . $_SESSION['role_id']);
            error_log("Session data: " . print_r($_SESSION, true));

            // Clear any output
            ob_clean();

            // Make sure all required session data is set and valid
            if ($_SESSION['user_id'] > 0 && $_SESSION['role_id'] > 0) {  // Check for valid IDs
                // Clear output buffer
                ob_clean();
                
                error_log("Redirecting user with role: " . $_SESSION['role_id']);
                
                // Admin users (role_id 2) don't need additional permission checks for now
                if ($_SESSION['role_id'] == 2) {
                    $_SESSION['admin_permissions'] = array('basic_access' => true);
                }
                
                // Redirect based on role
                switch ($_SESSION['role_id']) {
                    case 4: // Customer
                        header("Location: index.php");
                        break;
                    case 3: // Crew
                        header("Location: kfood_crew/dashboard.php");
                        break;
                    case 2: // Admin
                        $_SESSION['admin_permissions'] = array('basic_access' => true);
                        header("Location: kfood_admin/admin_pg.php");
                        break;
                    case 1: // Super Admin
                        header("Location: kfood_admin/SA_login.php");
                        break;
                    default:
                        error_log("Invalid role_id: " . $_SESSION['role_id']);
                        $error = "Invalid user role";
                        return;
                }
                exit();
            } else {
                error_log("Missing required session data");
                $error = "Login failed - missing data";
                return;
            }
        } else {
            $error = "Invalid password";
        }
    } else {
        $error = "Username not found";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self' https: 'unsafe-inline'; script-src 'self' https: 'unsafe-inline'; style-src 'self' https: 'unsafe-inline' fonts.googleapis.com; font-src 'self' fonts.gstatic.com;">
    <title>Welcome Back - K-Food Delight</title>
    <link rel="stylesheet" href="new-login-style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .error-message, .info-message {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: fadeIn 0.3s ease;
        }

        .error-message {
            background-color: #fee2e2;
            border: 1px solid #fecaca;
            color: #dc2626;
        }

        .info-message {
            background-color: #e0f2fe;
            border: 1px solid #bae6fd;
            color: #0284c7;
        }

        .error-message i, .info-message i {
            font-size: 18px;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Admin specific styles */
        .admin-login-hint {
            font-size: 0.9rem;
            color: #6b7280;
            text-align: center;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <img src="images/logo.png" alt="K-Food Delight">
            <h1>Welcome Back</h1>
            <p>Sign in to your account</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['login_message'])): ?>
            <div class="info-message">
                <i class="fas fa-info-circle"></i>
                <?php 
                    echo htmlspecialchars($_SESSION['login_message']); 
                    unset($_SESSION['login_message']);
                ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="loginForm" novalidate>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autocomplete="username"
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-input">
                    <input type="password" id="password" name="password" required autocomplete="current-password">
                    <button type="button" class="toggle-password">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="auth-options">
                <label class="remember-me">
                    <input type="checkbox" name="remember" id="remember">
                    <span>Remember me</span>
                </label>
                <a href="forgot-password.php" class="forgot-password">Forgot Password?</a>
            </div>

            <button type="submit" name="submit" class="auth-button">Sign In</button>

            <div class="auth-footer">
                <p>Don't have an account? <a href="register.php">Sign Up</a></p>
            </div>
        </form>
    </div>

    <script>
        // Password visibility toggle
        document.querySelector('.toggle-password').addEventListener('click', function() {
            const password = document.querySelector('#password');
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });
        // Form submission debugging
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            console.log('Form submitted');
        });
    </script>
</body>
</html>

<?php
include 'connect.php';
session_start();

$error = '';
$success = '';

if(isset($_POST['submit'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $newPassword = $_POST['new_password'];
    
    // Hash the new password properly
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Update the password
    $stmt = $conn->prepare("UPDATE users SET Password = ? WHERE username = ?");
    $stmt->bind_param("ss", $hashedPassword, $username);
    
    if($stmt->execute()) {
        $success = "Password successfully reset. You can now login with your new password.";
    } else {
        $error = "Error resetting password. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - K-Food Delight</title>
    <link rel="stylesheet" href="new-login-style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <h1>Reset Password</h1>
            <p>Enter your username and new password</p>
        </div>

        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>

            <div class="form-group">
                <label for="new_password">New Password</label>
                <div class="password-input">
                    <input type="password" id="new_password" name="new_password" required>
                    <button type="button" class="toggle-password">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" name="submit" class="auth-button">Reset Password</button>

            <div class="auth-footer">
                <p><a href="loginpage.php">Back to Login</a></p>
            </div>
        </form>
    </div>

    <script>
        document.querySelector('.toggle-password').addEventListener('click', function() {
            const password = document.querySelector('#new_password');
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });
    </script>
</body>
</html>
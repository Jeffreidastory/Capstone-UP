<?php
session_start();
include "../connect.php";

if(isset($_POST["submit"])) {
    $username = mysqli_real_escape_string($conn, $_POST["username"]);
    $password = $_POST["password"];
    
    // Query the super_admin_users table with role check
    $sql = "SELECT super_admin_id, username, password, role_id, is_active FROM super_admin_users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if(password_verify($password, $row["password"])) {
            // Check if account is active and has role_id = 1
            if($row["is_active"] == 1 && $row["role_id"] == 1) {
                // Set admin session variables
                $_SESSION['admin_id'] = $row['super_admin_id'];
                $_SESSION['admin_username'] = $row['username'];
                $_SESSION['role_id'] = $row['role_id'];
                
                // Redirect to admin panel
                header("Location: admin_pg.php");
                exit();
            } else if($row["is_active"] != 1) {
                $error = "Account is not active. Please contact system administrator.";
            } else {
                $error = "You don't have permission to access this area.";
            }
        } else {
            $error = "Invalid password";
        }
    } else {
        $error = "Super Admin account not found";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Login - K-Food Delight</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/sa-login-style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Super Admin Login</h1>
            <p>Access the administrative dashboard</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" id="username" name="username" required
                           placeholder="Enter your username"
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" required
                           placeholder="Enter your password">
                </div>
            </div>

            <button type="submit" name="submit" class="submit-btn">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>

        <div class="back-to-home">
            <a href="../index.php">
                <i class="fas fa-arrow-left"></i> Back to Homepage
            </a>
        </div>
    </div>

    <script>
        // Add subtle hover effect to input fields
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'translateY(-2px)';
            });
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html>
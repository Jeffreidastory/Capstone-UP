<?php
session_start();
include "../connect.php";

if(isset($_POST["submit"])) {
    $username = mysqli_real_escape_string($conn, $_POST["username"]);
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);
    $email = mysqli_real_escape_string($conn, $_POST["email"]);
    $full_name = mysqli_real_escape_string($conn, $_POST["full_name"]);
    $created_at = date('Y-m-d H:i:s');
    $is_active = 1;
    $role_id = 1; // Setting as super admin

    // Check if username already exists
    $check_sql = "SELECT username FROM super_admin_users WHERE username = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $username);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if($result->num_rows > 0) {
        $error = "Username already exists!";
    } else {
        // Insert new super admin
        $sql = "INSERT INTO super_admin_users (username, password, email, full_name, created_at, last_login, is_active, role_id) 
                VALUES (?, ?, ?, ?, ?, NULL, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssii", $username, $password, $email, $full_name, $created_at, $is_active, $role_id);
        
        if($stmt->execute()) {
            $success = "Super Admin account created successfully! Please delete this file after use.";
        } else {
            $error = "Error creating account: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Temporary Super Admin Registration</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #f0f2f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .warning-banner {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: #ff4444;
            color: white;
            text-align: center;
            padding: 10px;
            font-weight: bold;
            z-index: 1000;
        }

        .register-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
        }

        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .register-header h1 {
            color: #333;
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #4CAF50;
        }

        .submit-btn {
            width: 100%;
            padding: 0.75rem;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .submit-btn:hover {
            background: #45a049;
        }

        .message {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .success {
            background: #dff0d8;
            color: #3c763d;
            border: 1px solid #d6e9c6;
        }

        .error {
            background: #f2dede;
            color: #a94442;
            border: 1px solid #ebccd1;
        }

        .password-requirements {
            font-size: 0.8rem;
            color: #666;
            margin-top: 0.5rem;
        }

        .delete-warning {
            background: #fff3cd;
            color: #856404;
            padding: 1rem;
            border-radius: 5px;
            margin-top: 1rem;
            text-align: center;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="warning-banner">
        ⚠️ TEMPORARY FILE - DELETE AFTER CREATING SUPER ADMIN ACCOUNT ⚠️
    </div>

    <div class="register-container">
        <div class="register-header">
            <h1>Create Super Admin Account</h1>
            <p>Temporary registration form for initial setup</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="message error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required 
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
                <div class="password-requirements">
                    Password should be at least 8 characters long and include numbers and special characters
                </div>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" required
                       value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
            </div>

            <button type="submit" name="submit" class="submit-btn">
                <i class="fas fa-user-plus"></i> Create Super Admin Account
            </button>
        </form>

        <div class="delete-warning">
            <i class="fas fa-exclamation-triangle"></i>
            IMPORTANT: Delete this file immediately after creating the super admin account!
        </div>
    </div>

    <script>
        // Simple password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const requirements = document.querySelector('.password-requirements');
            
            if(password.length >= 8 && /[0-9]/.test(password) && /[!@#$%^&*]/.test(password)) {
                requirements.style.color = '#4CAF50';
            } else {
                requirements.style.color = '#666';
            }
        });
    </script>
</body>
</html>
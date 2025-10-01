<?php
session_start();
include "../connect.php";

if (!isset($_SESSION['role_id'])) {
    header("Location: ../loginpage.php");
    exit();
}

// Get user ID based on role
if ($_SESSION['role_id'] == 1) {
    $userId = $_SESSION['admin_id'];
} else {
    $userId = $_SESSION['user_id']; // Make sure this matches your session variable name
}

if (!$userId) {
    header("Location: ../loginpage.php");
    exit();
}

// Handle profile update
if(isset($_POST['update_profile'])) {
    $firstName = mysqli_real_escape_string($conn, $_POST['firstName']);
    $lastName = mysqli_real_escape_string($conn, $_POST['lastName']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    
    // Handle profile picture upload
    if(isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_picture']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        if(in_array(strtolower($filetype), $allowed)) {
            $newname = uniqid() . '_profile.' . $filetype;
            $upload = "../uploaded_img/" . $newname;
            
            if(move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload)) {
                $profile_pic_sql = ", profile_picture = '$newname'";
            } else {
                $error = "Failed to upload image.";
            }
        } else {
            $error = "Invalid file type. Only JPG, JPEG, PNG & GIF files are allowed.";
        }
    } else {
        $profile_pic_sql = "";
    }

    // Update user data
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);

    $update_query = "UPDATE users SET 
                    FirstName = '$firstName',
                    LastName = '$lastName',
                    username = '$username',
                    Email = '$email',
                    address = '$address',
                    phone = '$phone'
                    $profile_pic_sql
                    WHERE Id = $userId";

    try {
        $stmt = mysqli_prepare($conn, $update_query);
        if ($stmt === false) {
            throw new Exception('Failed to prepare update statement: ' . mysqli_error($conn));
        }
        
        if (mysqli_stmt_execute($stmt)) {
            // Show immediate feedback using JavaScript
            echo "<script>alert('Profile updated successfully!');</script>";
            
            // Update session data
            $_SESSION['firstName'] = $firstName;
            $_SESSION['lastName'] = $lastName;
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;
            
            // Update profile picture in session if it was changed
            if (isset($newname)) {
                $_SESSION['profile_picture'] = $newname;
            }
        } else {
            $_SESSION['message'] = "Error updating profile.";
            $_SESSION['message_type'] = "error";
        }
        
        header("Location: profile.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['message'] = "Error: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        header("Location: profile.php");
        exit();
    }
}

// Get user data
try {
    $query = "SELECT * FROM users WHERE Id = ?";
    $stmt = mysqli_prepare($conn, $query);
    
    if ($stmt === false) {
        throw new Exception('Failed to prepare statement: ' . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "i", $userId);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to execute query: ' . mysqli_stmt_error($stmt));
    }
    
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    
    if (!$user) {
        throw new Exception('User not found');
    }
    
    mysqli_stmt_close($stmt);
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background: #f8f9fa;
            min-height: 100vh;
        }

        .container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .profile-header {
            background: linear-gradient(45deg, #FFB75E, #FF7F50);
            padding: 40px 0;
            border-radius: 15px;
            margin-bottom: 30px;
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .profile-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, rgba(255,255,255,0.1), transparent);
            z-index: 1;
        }

        .profile-picture-container {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto 20px;
            z-index: 2;
        }

        .profile-picture {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .edit-picture {
            position: absolute;
            bottom: 5px;
            right: 5px;
            background: white;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }

        .edit-picture:hover {
            transform: scale(1.1);
        }

        .profile-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #666;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #FFB75E;
            box-shadow: 0 0 0 3px rgba(255,183,94,0.1);
        }

        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
            resize: vertical;
            min-height: 80px;
        }

        .save-button {
            background: linear-gradient(45deg, #FFB75E, #FF7F50);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            width: 100%;
        }

        .save-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255,127,80,0.2);
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #666;
            text-decoration: none;
            margin-bottom: 20px;
            transition: color 0.2s;
        }

        .back-button:hover {
            color: #FF7F50;
        }

        .notification {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            animation: slideIn 0.3s ease-out;
        }

        .notification.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .notification.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="admin_pg.php" class="back-button">
            <i class="fas fa-arrow-left"></i>
            Back to Dashboard
        </a>

        <?php if(isset($_SESSION['message'])): ?>
            <div class="notification <?php echo $_SESSION['message_type']; ?>">
                <?php 
                    echo $_SESSION['message'];
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                ?>
            </div>
        <?php endif; ?>

        <div class="profile-header">
            <div class="profile-picture-container">
                <img src="<?php echo isset($user['profile_picture']) ? '../uploaded_img/' . $user['profile_picture'] : '../images/user.png'; ?>" 
                     alt="Profile Picture" 
                     class="profile-picture" 
                     id="preview-image">
                <label for="profile_picture" class="edit-picture">
                    <i class="fas fa-camera" style="color: #FF7F50;"></i>
                </label>
            </div>
            <h1><?php echo htmlspecialchars($user['FirstName'] . ' ' . $user['LastName']); ?></h1>
            <p><?php echo htmlspecialchars($user['username']); ?></p>
        </div>

        <div class="profile-card">
            <form action="" method="POST" enctype="multipart/form-data">
                <input type="file" 
                       id="profile_picture" 
                       name="profile_picture" 
                       accept="image/*" 
                       style="display: none;"
                       onchange="previewImage(this)">
                
                <div class="form-group">
                    <label for="firstName">First Name</label>
                    <input type="text" 
                           id="firstName" 
                           name="firstName" 
                           value="<?php echo htmlspecialchars($user['FirstName']); ?>" 
                           required>
                </div>

                <div class="form-group">
                    <label for="lastName">Last Name</label>
                    <input type="text" 
                           id="lastName" 
                           name="lastName" 
                           value="<?php echo htmlspecialchars($user['LastName']); ?>" 
                           required>
                </div>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           value="<?php echo htmlspecialchars($user['username']); ?>" 
                           required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           value="<?php echo htmlspecialchars($user['Email']); ?>" 
                           required>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" 
                           id="phone" 
                           name="phone" 
                           value="<?php echo htmlspecialchars($user['phone']); ?>" 
                           placeholder="Enter your phone number">
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" 
                              name="address" 
                              placeholder="Enter your address"
                              rows="3"><?php echo htmlspecialchars($user['address']); ?></textarea>
                </div>

                <button type="submit" name="update_profile" class="save-button">
                    Save Changes
                </button>
            </form>
        </div>
    </div>

    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                
                reader.onload = function(e) {
                    document.getElementById('preview-image').setAttribute('src', e.target.result);
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>
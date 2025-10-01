<?php
session_start();
include "../connect.php";

// Test database connection
if ($conn->connect_error) {
    error_log('Database connection failed: ' . $conn->connect_error);
    die('Database connection failed');
} else {
    error_log('Database connection successful');
}

// Function to check if admin has permission for a section
function hasPermission($conn, $userId, $section) {
    $query = "SELECT {$section}_access FROM user_permissions WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    return $row ? $row["{$section}_access"] == 1 : false;
}

// Check if user is logged in and has appropriate permissions
if (!isset($_SESSION['role_id'])) {
    // Clear any lingering session data
    session_unset();
    session_destroy();
    header("Location: ../loginpage.php");
    exit();
}

// Clear any old notification flags if this isn't a fresh login
if (!isset($_SESSION['just_logged_in']) || $_SESSION['just_logged_in'] !== true) {
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
    echo "<script>sessionStorage.removeItem('alertShown');</script>";
}

// Allow both super admin (role_id = 1) and admin (role_id = 2)
if ($_SESSION['role_id'] != 1 && $_SESSION['role_id'] != 2) {
    header("Location: ../unauthorized.php");
    exit();
}

// Set admin flag for UI customization
$is_super_admin = ($_SESSION['role_id'] == 1);

// Debug POST data
if(!empty($_POST)) {
    error_log('POST data received: ' . print_r($_POST, true));
}

// User Creation Functionality
if(isset($_POST['createUser'])) {
    // Debug information
    $debug_file = __DIR__ . '/form_submissions.log';
    $debug_data = [
        'timestamp' => date('Y-m-d H:i:s'),
        'POST' => $_POST,
        'GET' => $_GET,
        'FILES' => $_FILES,
        'SESSION' => $_SESSION ?? 'No session'
    ];
    
    // Log to debug file
    file_put_contents($debug_file, date('Y-m-d H:i:s') . " - Form submission:\n" . 
                     print_r($debug_data, true) . "\n\n", FILE_APPEND);
    
    error_log('Create user form submitted');
    error_log('POST data: ' . print_r($_POST, true));

    // Verify database connection first
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        die("Connection failed: " . $conn->connect_error);
    }
    error_log("Database connection verified");

    // Test database with a simple query
    $test_query = $conn->query("SELECT 1 FROM users LIMIT 1");
    if (!$test_query) {
        error_log("Database test query failed: " . $conn->error);
        die("Database error: " . $conn->error);
    }
    error_log("Database test query successful");

    $firstName = isset($_POST['firstName']) ? trim($_POST['firstName']) : '';
    $lastName = isset($_POST['lastName']) ? trim($_POST['lastName']) : '';
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $roleId = isset($_POST['roleId']) ? (int)$_POST['roleId'] : 4;

    error_log("Form data received - Username: $username, Role: $roleId, Email: $email");

    try {
        // Validate required fields
        if(empty($firstName) || empty($lastName) || empty($username) || empty($email) || empty($password)) {
            throw new Exception("All fields are required");
        }

        // Validate role ID
        if(!in_array($roleId, [2, 3, 4])) {
            throw new Exception("Invalid role selected");
        }

        // Check if username exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        if($stmt->get_result()->num_rows > 0) {
            throw new Exception("Username already exists");
        }

        // Check if email exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if($stmt->get_result()->num_rows > 0) {
            throw new Exception("Email already exists");
        }

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        try {
            error_log("Starting user creation process");
            
            // Start transaction
            $conn->begin_transaction();
            error_log("Transaction started");

            // Hash the password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            error_log("Password hashed successfully");

            // Log the values being inserted
            error_log("Inserting user with values: " . json_encode([
                'FirstName' => $firstName,
                'LastName' => $lastName,
                'username' => $username,
                'Email' => $email,
                'role_id' => $roleId
            ]));

            // Insert new user with exact column names from database
            $query = "INSERT INTO users (Id, FirstName, LastName, username, Email, Password, role_id) "
                   . "VALUES (NULL, ?, ?, ?, ?, ?, ?)"; 
            error_log("SQL Query: $query");

            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error . " Query: " . $query);
            }
            error_log("Statement prepared successfully");

            if (!$stmt->bind_param("sssssi", $firstName, $lastName, $username, $email, $hashedPassword, $roleId)) {
                throw new Exception("Parameter binding failed: " . $stmt->error);
            }
            error_log("Parameters bound successfully");
            
            error_log("Executing statement with values: " . 
                      "firstName=$firstName, lastName=$lastName, " . 
                      "username=$username, email=$email, roleId=$roleId");

            $success = $stmt->execute();
            if (!$success) {
                error_log("Execute failed with error: " . $stmt->error);
                throw new Exception("Execute failed: " . $stmt->error);
            }

            $affected_rows = $stmt->affected_rows;
            $insert_id = $stmt->insert_id;
            error_log("Statement execution results - Affected rows: $affected_rows, Insert ID: $insert_id");

            if ($affected_rows <= 0) {
                throw new Exception("No rows were inserted");
            }

            error_log("User inserted successfully with ID: " . $insert_id);

            // Get the newly inserted user's ID
            $newUserId = $stmt->insert_id;

            // Log the successful insertion
            error_log("New user created - ID: $newUserId, Username: $username, Role: $roleId");

            // Commit the transaction
            $conn->commit();
            error_log("Transaction committed successfully");

            // Set a session message for the notification
            $_SESSION['message'] = 'User account created successfully';
            $_SESSION['message_type'] = 'success';

            // Redirect to prevent form resubmission
            header("Location: admin_pg.php?section=roles&success=1");
            exit();
        } catch (Exception $e) {
            // Rollback the transaction on error
            $conn->rollback();
            error_log("Failed to create user: " . $e->getMessage());
            throw new Exception("Failed to create user account: " . $e->getMessage());
        }

    } catch (Exception $e) {
        echo "<script>showNotification('Error', '" . htmlspecialchars($e->getMessage()) . "', 'error');</script>";
    }
}

// Menu Creation Functionality
if(isset($_POST['add_product'])){
   $p_name = $_POST['p_name'];
   $p_category = $_POST['p_category'];
   $p_price = $_POST['p_price'];
   $p_image = $_FILES['p_image']['name'];
   $p_image_tmp_name = $_FILES['p_image']['tmp_name'];
   $p_image_folder = '../uploaded_img/'.$p_image;

   $insert_query = mysqli_query($conn, "INSERT INTO `products`(name, category, price, image) VALUES('$p_name', '$p_category', '$p_price', '$p_image')") or die('query failed');

   if($insert_query){
      move_uploaded_file($p_image_tmp_name, $p_image_folder);
      $message[] = 'product added successfully';
   }else{
      $message[] = 'could not add the product';
   }
}

if(isset($_GET['delete'])){
   $delete_id = $_GET['delete'];
   $delete_query = mysqli_query($conn, "DELETE FROM `products` WHERE id = $delete_id ") or die('query failed');
   if($delete_query){
      $message[] = 'product has been deleted';
   }else{
      $message[] = 'product could not be deleted';
   }
}

if(isset($_POST['update_product'])){
   $update_p_id = $_POST['update_p_id'];
   $update_p_name = $_POST['update_p_name'];
   $update_p_price = $_POST['update_p_price'];
   
   // Check if a new image was uploaded
   if(!empty($_FILES['update_p_image']['name'])){
      $update_p_image = $_FILES['update_p_image']['name'];
      $update_p_image_tmp_name = $_FILES['update_p_image']['tmp_name'];
      $update_p_image_folder = '../uploaded_img/'.$update_p_image;
      
      // Include image in update
      $update_query = mysqli_query($conn, "UPDATE `products` SET name = '$update_p_name', category = '$update_p_category', price = '$update_p_price', image = '$update_p_image' WHERE id = '$update_p_id'");
      
      if($update_query){
         move_uploaded_file($update_p_image_tmp_name, $update_p_image_folder);
         header('Location: admin_pg.php?section=menu-creation&action=update');
         exit();
      }else{
         echo "<script>alert('Error: Product could not be updated');</script>";
      }
   } else {
      // Update without changing the image
      $update_query = mysqli_query($conn, "UPDATE `products` SET name = '$update_p_name', price = '$update_p_price' WHERE id = '$update_p_id'");
      
      if($update_query){
         header('Location: admin_pg.php?section=menu-creation&action=update');
         exit();
      }else{
         header('Location: admin_pg.php?section=menu-creation&action=error');
         exit();
      }
   }
   
   // Prevent duplicate form submission
   header('Location: admin_pg.php?section=menu-creation');
   exit();
}

// Get admin details based on role
$admin_data = [];
if ($_SESSION['role_id'] == 1) {
    // Super admin data
    $admin_id = $_SESSION['admin_id'];
    $query = "SELECT username, full_name FROM super_admin_users WHERE super_admin_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin_data = $result->fetch_assoc();
} else {
    // Regular admin data
    $admin_data = [
        'username' => $_SESSION['username'],
        'full_name' => $_SESSION['firstName'] . ' ' . $_SESSION['lastName']
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="css/inventory.css">
    <link rel="stylesheet" href="css/role-features.css">
    <link rel="stylesheet" href="css/menu-creation-enhanced.css">
    <link rel="stylesheet" href="css/add-product-form.css">
    <link rel="stylesheet" href="css/inventory-stats.css">
    <link rel="stylesheet" href="css/theme-variables.css">
    <link rel="stylesheet" href="css/dark-mode-components.css">
    <link rel="stylesheet" href="css/dark-mode.css">
    <link rel="stylesheet" href="css/dark-mode-text.css">
    <link rel="stylesheet" href="css/dark-mode-override.css">
    <link rel="stylesheet" href="css/dark-mode-final.css">
    <link rel="stylesheet" href="css/image-upload.css">
    <link rel="stylesheet" href="css/adm-style.css">
    <link rel="stylesheet" href="css/admin-enhanced.css">
    <link rel="stylesheet" href="css/notification-style.css">
    <link rel="stylesheet" href="css/edit-form.css">
    <link rel="stylesheet" href="css/sidebar-enhanced.css">
    <link rel="stylesheet" href="css/user-roles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Role Card Styles */
        .role-card {
            background: #ffffff;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
        }

        [data-theme='dark'] .role-card {
            background: #2a2d3a;
            border-color: rgba(255, 255, 255, 0.1);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
        }

        .role-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(255, 127, 80, 0.1);
        }

        .role-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 20px;
            background: rgba(255, 127, 80, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .role-icon i {
            font-size: 24px;
            color: #FF7F50;
        }

        .role-title {
            font-size: 28px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 15px;
            letter-spacing: 0.2px;
        }

        [data-theme='dark'] .role-title {
            color: #ffffff !important;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        h3.role-title {
            color: #1a1a1a;
        }

        [data-theme='dark'] h3.role-title {
            color: #ffffff !important;
        }

        .role-description {
            color: #6B7280;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 25px;
        }

        [data-theme='dark'] .role-description {
            color: rgba(255, 255, 255, 0.7);
        }

        .feature-list {
            list-style: none;
            padding: 0;
            margin: 0 0 25px 0;
            text-align: left;
        }

        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            color: #4B5563;
            font-size: 14px;
        }

        [data-theme='dark'] .feature-item {
            color: rgba(255, 255, 255, 0.8);
        }

        .feature-item i {
            color: #10B981;
            margin-right: 10px;
            font-size: 16px;
        }

        .create-button {
            background: #FF7F50;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            width: fit-content;
            margin: 0 auto;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .create-button:hover {
            background: #ff6b3d;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 127, 80, 0.2);
        }

        [data-theme='dark'] .create-button {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .roles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            padding: 20px;
        }

        .feature-check {
            color: #10B981;
            margin-right: 8px;
        }

        [data-theme='dark'] .feature-check {
            color: #34D399;
        }

        /* Global Styles */
        body {
            background-color: #f8f9fa;
            color: #333;
        }

        [data-theme="dark"] body {
            background-color: var(--bg-primary);
            color: var(--text-primary);
        }
        
        /* Sidebar and Menu Item Styles */
        .menu-item {
            position: relative;
            transition: all 0.3s ease;
        }

        .menu-item.active {
            background: rgba(255, 127, 80, 0.1);
        }

        .menu-item.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: #FF7F50;
        }

        .menu-item.active a {
            color: #FF7F50;
        }

        .menu-item:hover {
            background: rgba(255, 127, 80, 0.05);
        }

        /* Global Font Styles */
        * {
            font-family: 'Inter', sans-serif;
        }

        /* Dashboard Styles */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #ffffff;
            border-radius: 10px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 2px 4px rgba(255, 127, 80, 0.1);
            transition: transform 0.2s;
            border: 1px solid rgba(255, 127, 80, 0.1);
            position: relative;
            overflow: hidden;
        }

        [data-theme="dark"] .stat-card {
            background: #2a2d3a;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #FFB75E, #FF7F50);
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 127, 80, 0.15);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            background: rgba(255, 127, 80, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #FF7F50;
            font-size: 1.5rem;
        }

        .stat-info {
            flex: 1;
        }

        .stat-info h3 {
            font-size: 1.5rem;
            color: #FF6B3D;
            margin: 0 0 5px 0;
            font-weight: 700;
            text-shadow: 0 1px 1px rgba(0,0,0,0.1);
        }

        [data-theme="dark"] .stat-info h3 {
            color: #ffffff !important;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        [data-theme="dark"] .stat-info p {
            color: rgba(255, 255, 255, 0.8) !important;
        }

        .stat-info p {
            color: #4a4a4a;
            margin: 0;
            font-size: 0.95rem;
            font-weight: 500;
        }
        [data-theme="dark"] .stat-info p {
            color: rgba(255, 255, 255, 0.9);
        }

        .stat-trend {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9rem;
            padding: 4px 8px;
            border-radius: 15px;
        }

        .stat-trend.positive {
            background: #e8f5e9;
            color: #2e7d32;
            font-weight: 600;
            padding: 6px 12px;
        }

        [data-theme="dark"] .stat-trend.positive {
            background: rgba(46, 125, 50, 0.2);
            color: #4CAF50;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        .stat-trend.negative {
            background: #ffebee;
            color: #c62828;
        }

        .charts-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-card {
            background: var(--bg-primary);
            border-radius: 10px;
            padding: 20px;
            box-shadow: var(--shadow-sm);
            position: relative;
            overflow: hidden;
            border: 1px solid var(--border-color);
        }

        [data-theme="dark"] .chart-card {
            background: #262833;
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .chart-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #FFB75E, #FF7F50);
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .chart-header h3 {
            font-size: 1.25rem;
            color: #1a1a1a;
            margin: 0;
            font-weight: 600;
            letter-spacing: 0.2px;
        }
        [data-theme="dark"] .chart-header h3 {
            color: #ffffff;
            text-shadow: 0 1px 1px rgba(0,0,0,0.2);
        }

        .chart-period {
            padding: 8px 12px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 5px;
            background: #32364a;
            color: rgba(255, 255, 255, 0.9);
        }

        .chart-legend {
            display: flex;
            gap: 15px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.95rem;
            color: #4a4a4a;
            font-weight: 500;
        }
        [data-theme="dark"] .legend-item {
            color: rgba(255, 255, 255, 0.9);
        }

        [data-theme="dark"] .legend-item {
            color: rgba(255, 255, 255, 0.8) !important;
        }

        [data-theme="dark"] .chart-header h3 {
            color: rgba(255, 255, 255, 0.95) !important;
            font-weight: 500;
        }

        .legend-color {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }

        .legend-color.completed {
            background: #4CAF50;
        }

        .legend-color.pending {
            background: #FFC107;
        }

        .completion-chart {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
            height: 100%;
        }

        .pie-chart-container {
            width: 200px;
            height: 200px;
            position: relative;
            margin: 20px auto;
        }

        .pie-chart {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            position: relative;
            box-shadow: 0 0 20px rgba(255, 127, 80, 0.15);
        }

        .pie-center {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: var(--bg-primary);
            border-radius: 50%;
            width: 80%;
            height: 80%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .completion-percentage {
            font-size: 2.5rem;
            font-weight: 600;
            color: #FF7F50;
            line-height: 1;
            margin-bottom: 5px;
        }

        .completion-label {
            font-size: 0.9rem;
            color: var(--text-secondary);
            text-align: center;
            opacity: 0.8;
        }

        [data-theme="dark"] .pie-center {
            background: #1a1c23;
        }

        [data-theme="dark"] .completion-label {
            color: rgba(255, 255, 255, 0.7);
        }

        .progress-circle svg {
            width: 160px;
            height: 160px;
            transform: rotate(-90deg);
        }

        .progress-circle circle {
            fill: none;
            stroke-width: 8;
        }

        .progress-background {
            stroke: #e5e7eb;
        }
        [data-theme="dark"] .progress-background {
            stroke: #32364a;
        }

        .progress-bar {
            stroke: #FF7F50;
            stroke-dasharray: 440;
            stroke-dashoffset: 66; /* 440 * (1 - progress%) */
            stroke-width: 10;
            transition: stroke-dashoffset 0.5s ease;
        }

        [data-theme="dark"] .progress-bar {
            stroke: #FF7F50;
            filter: drop-shadow(0 0 4px rgba(255, 127, 80, 0.3));
        }

        .progress-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            background: transparent;
        }
        .progress-content h4 {
            color: #FF7F50;
            font-size: 2rem;
            font-weight: 600;
            margin: 0;
        }
        .progress-content p {
            color: #6B7280;
            margin: 5px 0 0;
            font-size: 0.9rem;
        }
        [data-theme="dark"] .progress-content p {
            color: rgba(255, 255, 255, 0.7);
        }

        .progress-content h4 {
            margin: 0;
            font-size: 1.8rem;
            color: var(--text-primary);
            font-weight: 600;
        }

        [data-theme="dark"] .progress-content h4 {
            color: rgba(255, 255, 255, 0.95) !important;
        }

        [data-theme="dark"] .progress-content p {
            color: rgba(255, 255, 255, 0.7) !important;
        }

        [data-theme="dark"] .completion-rate {
            color: #FF7F50 !important;
            font-weight: 600;
            font-size: 2rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .progress-content p {
            margin: 5px 0 0;
            font-size: 0.9rem;
            color: #666;
        }

        .recent-orders {
            background: var(--bg-primary);
            border-radius: 10px;
            padding: 20px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
        }

        [data-theme="dark"] .recent-orders {
            background: #262833;
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .recent-orders .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .recent-orders h3 {
            font-size: 1.2rem;
            color: var(--text-primary);
            margin: 0;
        }

        .view-all {
            color: #FF7F50;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .dashboard-orders-table {
            width: 100%;
            border-collapse: collapse;
            background-color: var(--bg-primary);
            border-radius: 10px;
            overflow: hidden;
        }

        .dashboard-orders-table th,
        .dashboard-orders-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        [data-theme="dark"] .dashboard-orders-table {
            background-color: #262833;
        }

        [data-theme="dark"] .dashboard-orders-table tr:nth-child(even) {
            background-color: #2a2d3a;
        }

        [data-theme="dark"] .dashboard-orders-table tr:hover {
            background-color: #32364a;
            transition: background-color 0.2s ease;
        }

        [data-theme="dark"] .dashboard-orders-table th {
            background-color: #32364a;
            color: rgba(255, 255, 255, 1) !important;
            font-weight: 600;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
            border-bottom: 2px solid rgba(255, 127, 80, 0.3);
            letter-spacing: 0.5px;
        }

        [data-theme="dark"] .dashboard-orders-table td {
            color: rgba(255, 255, 255, 0.7);
        }

        .dashboard-orders-table th {
            font-weight: 600;
            color: #333;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background-color: #f8f9fa;
        }

        .dashboard-orders-table td {
            font-size: 0.9rem;
            color: var(--text-primary);
        }

        .dashboard-orders-table .status-badge {
            padding: 4px 8px;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .dashboard-orders-table .status-badge.pending {
            background: rgba(255, 183, 94, 0.15);
            color: #FF9F50;
            font-weight: 600;
        }

        [data-theme="dark"] .dashboard-orders-table .status-badge.pending {
            background: rgba(255, 183, 94, 0.2);
            color: #FFB75E;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        .dashboard-orders-table .status-badge.completed {
            background: rgba(255, 127, 80, 0.15);
            color: #FF7F50;
            font-weight: 600;
        }

        [data-theme="dark"] .dashboard-orders-table .status-badge.completed {
            background: rgba(255, 127, 80, 0.2);
            color: #FFB75E;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        .dashboard-orders-table .status-badge.cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        @media (max-width: 1200px) {
            .charts-container {
                grid-template-columns: 1fr;
            }
        }

        /* Enhanced Dashboard Styles */
        .stat-card {
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 127, 80, 0.1);
        }

        .stat-icon {
            position: relative;
            overflow: hidden;
        }

        .stat-icon::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, rgba(255,255,255,0.2), transparent);
            transform: rotate(45deg);
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%) rotate(45deg); }
            100% { transform: translateX(100%) rotate(45deg); }
        }

        .chart-card {
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 127, 80, 0.1);
            transition: all 0.3s ease;
            background: #ffffff;
            padding: 20px;
        }

        [data-theme="dark"] .chart-card {
            background: #2a2d3a;
        }

        .chart-content {
            position: relative;
            height: 300px;
            width: 100%;
        }

        .revenue-chart .chart-content {
            background-color: #ffffff;
            border-radius: 8px;
        }

        [data-theme="dark"] .revenue-chart .chart-content {
            background-color: #2a2d3a;
        }

        .chart-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(255, 127, 80, 0.1);
        }

        .chart-period {
            position: relative;
            background: linear-gradient(45deg, #FFB75E, #FF7F50);
            color: white;
            border: none;
            padding: 8px 30px 8px 15px;
            border-radius: 20px;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
        }

        .chart-period::after {
            content: '▼';
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 12px;
        }

        .legend-item {
            position: relative;
            padding-left: 20px;
        }

        .legend-color {
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
        }

        .legend-color.completed {
            background: #FFB75E;
        }

        .legend-color.pending {
            background: #FF7F50;
        }

        .dashboard-orders-table tbody tr {
            transition: all 0.3s ease;
        }

        .dashboard-orders-table tbody tr:hover {
            background-color: rgba(255, 183, 94, 0.05);
        }

        .status-badge {
            position: relative;
            overflow: hidden;
        }

        .status-badge::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, rgba(255,255,255,0.2), transparent);
            transform: rotate(45deg);
            animation: shimmer 2s infinite;
        }

        /* All stat icons now use the same background color */
        .stats-container .stat-card .stat-icon { background: rgba(255, 127, 80, 0.15); }

        .recent-orders {
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 127, 80, 0.1);
        }

        .recent-orders::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #FFB75E, #FF7F50);
        }

        .view-all {
            position: relative;
            padding: 5px 15px;
            border-radius: 15px;
            background: linear-gradient(45deg, #FFB75E, #FF7F50);
            color: white !important;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .view-all:hover {
            transform: translateY(-2px);
            box-shadow: 0 3px 8px rgba(255, 127, 80, 0.2);
        }

        @media (max-width: 768px) {
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .chart-card {
                margin-bottom: 20px;
            }
            
            .recent-orders {
                overflow-x: auto;
            }
        }

        /* Section Description Style */
        .section-description {
            font-size: 14px;
            color: #666;
            font-weight: 400;
            margin-top: 0;
            font-family: 'Inter', sans-serif;
        }

        /* Header and Profile Styles */
        [data-theme="dark"] .admin-panel-title {
            color: rgba(255, 255, 255, 0.9) !important;
        }

        [data-theme="dark"] .dashboard-title {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 600;
        }

        .main-header {
            background: var(--bg-primary);
            padding: 0 24px;
            box-shadow: none;
            position: relative;
            height: 48px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
        }

        [data-theme="dark"] .main-header {
            background: #1a1c23;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            margin: 0 auto;
            gap: 24px;
        }
        
        .header-left {
            min-width: 200px;
            flex-shrink: 0;
        }

        .header-content h1 {
            font-size: 15px;
            color: #1a1a1a;
            font-weight: 500;
            margin: 0;
            min-width: 100px;
        }

        .search-container {
            position: relative;
            margin-right: 20px;
        }

        .search-container input {
            width: 100%;
            padding: 6px 12px 6px 32px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            font-size: 13px;
            color: #666;
            background: #f9fafb;
            transition: all 0.2s ease;
        }

        .search-container input:focus {
            background: #ffffff;
            border-color: #94a3b8;
            outline: none;
            box-shadow: none;
        }

        .search-container input:hover {
            border-color: #d1d5db;
        }

        .profile-info:hover {
            background: #f8fafc;
        }

        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #888;
            font-size: 14px;
            z-index: 1;
        }

        .header-title {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-title i {
            font-size: 20px;
            color: #FF7F50;
            background: rgba(255, 127, 80, 0.1);
            padding: 8px;
            border-radius: 8px;
        }

        .header-content h1 {
            font-size: 20px;
            color: #000000 !important;
            font-weight: 600;
            margin: 0;
            font-family: 'Inter', sans-serif;
            letter-spacing: -0.5px;
        }

        [data-theme="dark"] .header-content h1 {
            color: #FFFFFF !important;
        }

        .enhanced-search {
            width: 800px !important;
            padding: 8px 12px 8px 38px !important;
            border: 2px solid transparent !important;
            border-radius: 50px !important;
            font-size: 14px !important;
            color: var(--text-primary) !important;
            background: var(--bg-tertiary) !important;

        }

        [data-theme="dark"] .enhanced-search {
            background: #2d303a !important;
            color: rgba(255, 255, 255, 0.9) !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
        }
            transition: all 0.3s ease !important;
            box-shadow: 0 2px 8px rgba(255, 127, 80, 0.08) !important;
            margin-right: 300px !important;
        }

        .enhanced-search:focus {
            background: #ffffff !important;
            border-color: rgba(255, 127, 80, 0.3) !important;
            box-shadow: 0 4px 12px rgba(255, 127, 80, 0.12) !important;
        }

        .enhanced-search:hover {
            background: #ffffff !important;
            box-shadow: 0 4px 12px rgba(255, 127, 80, 0.15) !important;
        }

        .profile-section {
            display: flex;
            align-items: center;
            padding: 4px;
            margin-left: auto;
        }

        .profile-info {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px 4px 4px;
            border-radius: 24px;
            transition: all 0.2s ease;
            background-color: var(--bg-tertiary);
        }

        [data-theme="dark"] .profile-info {
            background-color: #2d303a;
        }

        [data-theme="dark"] .admin-name {
            color: rgba(255, 255, 255, 0.9);
        }

        .profile-info:hover {
            background: rgba(255, 127, 80, 0.1);
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(255, 127, 80, 0.1);
        }

        .profile-pic {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #fff;
            transition: transform 0.2s ease;
        }

        .profile-info:hover {
            background-color: #ffe4d9;
        }

        .admin-name {
            font-size: 14px;
            color: #1a1a1a;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            margin-left: 4px;
            white-space: nowrap;
            letter-spacing: 0.2px;
            text-shadow: 0 1px 1px rgba(255,255,255,0.5);
        }
        [data-theme="dark"] .admin-name {
            color: #ffffff;
            text-shadow: 0 1px 1px rgba(0,0,0,0.2);
        }

        /* Section Styles */
        .section-header {
            margin-bottom: 30px;
        }

        .section-header h2 {
            font-size: 24px;
            color: #1a1a1a;
            font-weight: 600;
            margin-bottom: 8px;
            font-family: 'Inter', sans-serif;
        }

        .section-description {
            color: #7f8c8d;
            font-size: 14px;
            margin: 0;
        }

        .content-section {
            display: none;
            background: #ffffff;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            padding: 25px;
            margin: 20px;
        }

        [data-theme="dark"] .content-section {
            background: #2a2d3a;
        }
        .content-section.active {
            display: block;
        }
        #notificationContainer {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }

        .notification {
            background: #2a2d3a;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            margin-bottom: 10px;
            padding: 16px;
            width: 300px;
            display: flex;
            align-items: flex-start;
            animation: slideIn 0.3s ease-out;
            position: relative;
            overflow: hidden;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .notification.success {
            border-left: 4px solid #4CAF50;
            background-color: #f8fdf8;
        }

        .notification.error {
            border-left: 4px solid #f44336;
            background-color: #fef8f8;
        }

        .notification-icon {
            margin-right: 12px;
            font-size: 20px;
        }

        .notification.success .notification-icon {
            color: #4CAF50;
        }

        .notification.error .notification-icon {
            color: #f44336;
        }

        .notification-content {
            flex-grow: 1;
        }

        .notification-title {
            font-weight: 600;
            margin-bottom: 4px;
            color: #333;
        }

        .notification-message {
            color: #666;
            font-size: 14px;
        }

        .notification-close {
            background: transparent;
            border: none;
            color: #999;
            cursor: pointer;
            font-size: 16px;
            padding: 0;
            position: absolute;
            right: 12px;
            top: 12px;
        }

        .notification-close:hover {
            color: #666;
        }

        .notification-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: #eee;
        }

        .notification-progress::after {
            content: '';
            position: absolute;
            left: 0;
            width: 100%;
            height: 100%;
            background: #ddd;
            animation: progress 3s linear;
        }

        /* Theme Toggle Styles */
        .theme-toggle {
            cursor: pointer;
            transition: all 0.3s ease;
            background: none;
            border: none;
            padding: 0;
            margin-left: 15px;
            margin-right: 15px;
            line-height: 1;
        }

        .theme-toggle:hover {
            transform: scale(1.1);
        }

        .theme-toggle i {
            font-size: 20px;
            color: #FF7F50;
            transition: all 0.3s ease;
            filter: drop-shadow(0 2px 4px rgba(255, 127, 80, 0.2));
            display: inline-block;
            line-height: 1;
        }

        [data-theme="dark"] .theme-toggle i {
            color: #FF7F50;
        }

        .theme-toggle:active {
            transform: scale(0.95);
        }

        @keyframes progress {
            from { width: 100%; }
            to { width: 0%; }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div id="notificationContainer"></div>

    <!-- Out of Stock Top Notification -->
    <div id="outOfStockAlert" class="alert-modal">
        <div class="alert-content">
            <div class="alert-header">
                <i class="fas fa-exclamation-circle"></i>
                <span>Out of Stock Items Alert</span>
            </div>
            <div id="outOfStockList">
                <!-- Items will be inserted here dynamically -->
            </div>
        </div>
        <button onclick="closeOutOfStockModal()" class="alert-button">
            Acknowledge
        </button>
    </div>

    <style>
        .alert-modal {
            display: none;
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            width: 400px;
            overflow: hidden;
            border: 1px solid #e5e7eb;
        }

        [data-theme="dark"] .alert-modal {
            background: #2a2d3a;
            border-color: rgba(255,255,255,0.1);
        }

        .alert-content {
            padding: 15px;
            border-bottom: 1px solid #e5e7eb;
        }

        [data-theme="dark"] .alert-content {
            border-color: rgba(255,255,255,0.1);
        }

        .alert-header {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
            gap: 8px;
        }

        .alert-header i {
            color: #FF7F50;
        }

        .alert-header span {
            font-weight: 600;
            color: #1a1a1a;
        }

        [data-theme="dark"] .alert-header span {
            color: rgba(255,255,255,0.9);
        }

        #outOfStockList {
            max-height: 150px;
            overflow-y: auto;
            margin-top: 10px;
            color: #4a5568;
        }

        [data-theme="dark"] #outOfStockList {
            color: rgba(255,255,255,0.7);
        }

        .alert-button {
            width: 100%;
            border: none;
            padding: 8px;
            background: #FF7F50;
            color: white;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: background-color 0.2s;
        }

        .alert-button:hover {
            background: #ff6b3d;
        }

        /* Success notification styling */
        .notification.success {
            background-color: #ffffff;
            border-left: 4px solid #4CAF50;
        }

        [data-theme="dark"] .notification.success {
            background-color: #2a2d3a;
            border-left: 4px solid #4CAF50;
        }

        .notification-title {
            color: #1a1a1a;
        }

        [data-theme="dark"] .notification-title {
            color: rgba(255,255,255,0.9);
        }

        .notification-message {
            color: #4a5568;
        }

        [data-theme="dark"] .notification-message {
            color: rgba(255,255,255,0.7);
        }
    </style>
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="../images/logo.png" alt="Logo" class="logo">
            <h2>Admin Panel</h2>
        </div>
        <ul class="sidebar-menu">
            <!-- Dashboard -->
            <li class="menu-item" id="dashboard-item">
                <a href="#" data-section="dashboard">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <!-- Content Management -->
            <li class="menu-item" id="landing-item">
                <a href="#" data-section="landing">
                    <i class="fas fa-home"></i>
                    <span>Landing Settings</span>
                </a>
            </li>

            <div class="menu-separator"></div>

            <!-- User Management -->
            <li class="menu-item" id="roles-item">
                <a href="#" data-section="roles">
                    <i class="fas fa-user-shield"></i>
                    <span>User Roles</span>
                </a>
            </li>
            <li class="menu-item" id="accounts-item">
                <a href="#" data-section="accounts">
                    <i class="fas fa-users-cog"></i>
                    <span>User Accounts</span>
                </a>
            </li>

            <div class="menu-separator"></div>

            <!-- Product Management -->
            <li class="menu-item" id="inventory-item">
                <a href="#" data-section="inventory">
                    <i class="fas fa-boxes"></i>
                    <span>Inventory</span>
                </a>
            </li>
            <li class="menu-item" id="menu-creation-item">
                <a href="#" data-section="menu-creation">
                    <i class="fas fa-utensils"></i>
                    <span>Menu Creation</span>
                </a>
            </li>

            <div class="menu-separator"></div>

            <!-- Business Intelligence -->
            <li class="menu-item" id="reports-item">
                <a href="#" data-section="reports">
                    <i class="fas fa-chart-line"></i>
                    <span>Reports & Analytics</span>
                </a>
            </li>
            <li class="menu-item" id="orders-item">
                <a href="#" data-section="orders">
                    <i class="fas fa-shopping-basket"></i>
                    <span>Orders</span>
                </a>
            </li>

            <div class="menu-separator"></div>

            <!-- Logout -->
            <li class="menu-item">
                <a href="#" onclick="handleLogout(event)" style="cursor: pointer;">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>
    <div id="pageOverlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.3); z-index: 999;"></div>
    <div class="main-content" id="mainContent">
        <header class="main-header">
            <div class="header-content">
                <div class="header-left">
                    <div class="header-title" id="section-title">
                        <i class="fas fa-chart-pie dashboard-icon"></i>
                        <h1>Dashboard</h1>
                    </div>
                </div>
                <div class="search-container" style="position: relative; margin: 0 auto;">
                    <i class="fas fa-search search-icon" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #888; font-size: 14px;"></i>
                    <input type="text" placeholder="Search anything..." class="enhanced-search" style="width: 600px !important; padding-left: 40px;">
                </div>
                <div class="notification-icon" style="margin-left: 20px; position: relative; cursor: pointer;" onclick="toggleNotifications()">
                    <i class="fas fa-bell" style="font-size: 20px; color: #FF7F50; filter: drop-shadow(0 2px 4px rgba(255, 127, 80, 0.2));"></i>
                    <span id="notifCount" class="notification-badge" style="position: absolute; top: -5px; right: -5px; background: #FF7F50; color: white; border-radius: 50%; width: 18px; height: 18px; font-size: 11px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(255, 127, 80, 0.2);">0</span>
                </div>
                <!-- Dark Mode Toggle -->
                <div class="theme-toggle" onclick="toggleDarkMode()">
                    <i class="fas fa-sun" id="themeIcon"></i>
                </div>
                <!-- Notification Dropdown -->
                <div id="notificationDropdown" style="display: none; position: absolute; top: 50px; right: 20px; width: 320px; background: white; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); z-index: 1000;">
                    <div style="padding: 15px; border-bottom: 1px solid #f0f0f0;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div style="font-weight: 600; color: #333;">Stock Notifications</div>
                            <button onclick="markAllAsRead()" id="markAllBtn" style="padding: 4px 8px; font-size: 12px; color: #FF7F50; background: none; border: 1px solid #FF7F50; border-radius: 4px; cursor: pointer; display: none;">Mark all as read</button>
                        </div>
                    </div>
                    <div id="notificationList" style="max-height: 300px; overflow-y: auto;">
                        <!-- Notifications will be inserted here -->
                    </div>
                </div>
                <div class="profile-section">
                    <a href="profile.php" class="profile-info" style="text-decoration: none; cursor: pointer;">
                        <img src="<?php 
                            if (isset($_SESSION['profile_picture']) && !empty($_SESSION['profile_picture'])) {
                                echo '../uploaded_img/' . htmlspecialchars($_SESSION['profile_picture']);
                            } else {
                                echo '../images/user.png';
                            }
                        ?>" 
                        alt="Admin Profile" class="profile-pic">
                        <span class="admin-name"><?php 
                            if (isset($_SESSION['firstName']) && isset($_SESSION['lastName'])) {
                                echo htmlspecialchars($_SESSION['firstName'] . ' ' . $_SESSION['lastName']);
                            } else {
                                echo htmlspecialchars($admin_data['full_name'] ?? $_SESSION['username']);
                            }
                        ?></span>
                    </a>
                </div>
            </div>
        </header>

        <!-- Dashboard Section -->
        <section id="dashboard-section" class="content-section">
            <!-- Statistics Cards -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <div class="stat-info">
                        <?php
                        $total_items = mysqli_query($conn, "SELECT COUNT(*) as count FROM products");
                        $items_count = mysqli_fetch_assoc($total_items)['count'];
                        ?>
                        <h3><?php echo $items_count; ?></h3>
                        <p>Total Items</p>
                    </div>
                    <div class="stat-trend positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>12%</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-peso-sign"></i>
                    </div>
                    <div class="stat-info">
                        <?php
                        $total_revenue = mysqli_query($conn, "SELECT SUM(total_price) as total FROM orders WHERE status = 'completed'");
                        $revenue = mysqli_fetch_assoc($total_revenue)['total'] ?? 0;
                        ?>
                        <h3>₱<?php echo number_format($revenue, 2); ?></h3>
                        <p>Total Revenue</p>
                    </div>
                    <div class="stat-trend positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>8%</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-info">
                        <?php
                        $total_orders = mysqli_query($conn, "SELECT COUNT(*) as count FROM orders");
                        $orders_count = mysqli_fetch_assoc($total_orders)['count'];
                        ?>
                        <h3><?php echo $orders_count; ?></h3>
                        <p>Total Orders</p>
                    </div>
                    <div class="stat-trend positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>5%</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <?php
                        $total_customers = mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role_id = 4");
                        $customers_count = mysqli_fetch_assoc($total_customers)['count'];
                        ?>
                        <h3><?php echo $customers_count; ?></h3>
                        <p>Total Customers</p>
                    </div>
                    <div class="stat-trend positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>15%</span>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="charts-container">
                <div class="chart-card revenue-chart">
                    <div class="chart-header">
                        <h3>Revenue Overview</h3>
                        <div class="chart-actions">
                            <select class="chart-period">
                                <option value="monthly">Monthly</option>
                                <option value="weekly">Weekly</option>
                                <option value="daily">Daily</option>
                            </select>
                        </div>
                    </div>
                    <div class="chart-content">
                        <?php
                        // Fetch monthly revenue data for the last 12 months
                        $monthly_revenue = array();
                        $monthly_labels = array();
                        
                        for ($i = 11; $i >= 0; $i--) {
                            $start_date = date('Y-m-01', strtotime("-$i months"));
                            $end_date = date('Y-m-t', strtotime("-$i months"));
                            
                            $query = "SELECT COALESCE(SUM(total_price), 0) as revenue 
                                     FROM orders 
                                     WHERE status = 'completed' 
                                     AND order_time BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'";
                            
                            $result = mysqli_query($conn, $query);
                            if (!$result) {
                                error_log("MySQL Error: " . mysqli_error($conn));
                            }
                            $row = mysqli_fetch_assoc($result);
                            
                            $monthly_revenue[] = floatval($row['revenue']);
                            $monthly_labels[] = date('M', strtotime($start_date));
                        }

                        // Debug output
                        error_log("Revenue Data: " . json_encode($monthly_revenue));
                        error_log("Labels Data: " . json_encode($monthly_labels));
                        ?>
                        <canvas id="revenueChart"></canvas>
                        <script>
                            // Store the data in variables
                            window.revenueData = <?php echo json_encode($monthly_revenue); ?>;
                            window.labelData = <?php echo json_encode($monthly_labels); ?>;
                        </script>
                    </div>
                </div>

                <div class="chart-card orders-chart">
                    <div class="chart-header">
                        <h3>Order Statistics</h3>
                        <div class="chart-legend">
                            <span class="legend-item">
                                <span class="legend-color" style="background: #FF7F50;"></span>
                                Completed
                            </span>
                            <span class="legend-item">
                                <span class="legend-color" style="background: #FFB75E;"></span>
                                Pending
                            </span>
                        </div>
                    </div>
                    <div class="chart-content">
                        <?php
                        $total_orders = mysqli_query($conn, "SELECT COUNT(*) as total FROM orders");
                        $completed_orders = mysqli_query($conn, "SELECT COUNT(*) as completed FROM orders WHERE status = 'completed'");
                        $total_count = mysqli_fetch_assoc($total_orders)['total'];
                        $completed_count = mysqli_fetch_assoc($completed_orders)['completed'];
                        $completion_rate = $total_count > 0 ? round(($completed_count / $total_count) * 100, 1) : 0;
                        ?>
                        <div class="completion-chart">
                            <div class="pie-chart-container">
                                <div class="pie-chart" style="background: conic-gradient(#FF7F50 <?php echo $completion_rate; ?>%, #FFB75E 0)">
                                    <div class="pie-center">
                                        <span class="completion-percentage"><?php echo round($completion_rate); ?>%</span>
                                        <span class="completion-label">Completion<br>Rate</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Orders Table -->
            <div class="recent-orders">
               
                <div class="orders-table-wrapper">
                    <table class="dashboard-orders-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $recent_orders_query = mysqli_query($conn, "SELECT * FROM orders ORDER BY order_time DESC LIMIT 5");
                            while($order = mysqli_fetch_assoc($recent_orders_query)) {
                                $status_class = strtolower($order['status']);
                                ?>
                                <tr>
                                    <td>#<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></td>
                                    <td><?php echo htmlspecialchars($order['name']); ?></td>
                                    <td><?php echo $order['total_products']; ?> items</td>
                                    <td>₱<?php echo number_format($order['total_price'], 2); ?></td>
                                    <td><span class="status-badge <?php echo $status_class; ?>"><?php echo ucfirst($order['status']); ?></span></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- Inventory Section -->
        <section id="inventory-section" class="content-section hidden">
            <?php
            // Calculate inventory statistics
            $total_products = mysqli_query($conn, "SELECT COUNT(*) as total FROM products");
            $total_count = mysqli_fetch_assoc($total_products)['total'];

            $low_stock = mysqli_query($conn, "SELECT COUNT(*) as total FROM products WHERE stock > 0 AND stock <= 10");
            $low_stock_count = mysqli_fetch_assoc($low_stock)['total'];

            $out_of_stock = mysqli_query($conn, "SELECT COUNT(*) as total FROM products WHERE stock <= 0");
            $out_of_stock_count = mysqli_fetch_assoc($out_of_stock)['total'];

            // Calculate fast-moving products (products with most sales in last 30 days)
            $fast_moving = mysqli_query($conn, "SELECT COUNT(DISTINCT product_id) as total 
                FROM stock_history 
                WHERE type = 'deduct' 
                AND date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY product_id 
                HAVING SUM(quantity) >= 20");
            $fast_moving_count = mysqli_num_rows($fast_moving);
            ?>
            <!-- KPI Cards -->
            <div class="inventory-stats">
                <div class="stat-card total">
                    <div class="stat-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-details">
                        <div class="stat-number"><?php echo $total_count; ?></div>
                        <div class="stat-label">Total Products</div>
                    </div>
                    <div class="stat-chart">
                        <div class="chart-circle" style="--percent: <?php echo ($total_count > 0) ? 100 : 0; ?>%"></div>
                    </div>
                </div>

                <div class="stat-card warning">
                    <div class="stat-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-details">
                        <div class="stat-number"><?php echo $low_stock_count; ?></div>
                        <div class="stat-label">Low Stock</div>
                        <div class="stat-sublabel">Items need restock</div>
                    </div>
                    <div class="stat-chart">
                        <div class="chart-circle" style="--percent: <?php echo ($total_count > 0) ? ($low_stock_count / $total_count) * 100 : 0; ?>%"></div>
                    </div>
                </div>

                <div class="stat-card danger">
                    <div class="stat-icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-details">
                        <div class="stat-number"><?php echo $out_of_stock_count; ?></div>
                        <div class="stat-label">Out of Stock</div>
                        <div class="stat-sublabel">Immediate attention needed</div>
                    </div>
                    <div class="stat-chart">
                        <div class="chart-circle" style="--percent: <?php echo ($total_count > 0) ? ($out_of_stock_count / $total_count) * 100 : 0; ?>%"></div>
                    </div>
                </div>

                <div class="stat-card success">
                    <div class="stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-details">
                        <div class="stat-number"><?php echo $fast_moving_count; ?></div>
                        <div class="stat-label">Fast-moving</div>
                        <div class="stat-sublabel">High demand items</div>
                    </div>
                    <div class="stat-chart">
                        <div class="chart-circle" style="--percent: <?php echo ($total_count > 0) ? ($fast_moving_count / $total_count) * 100 : 0; ?>%"></div>
                    </div>
                </div>
            </div>

            <!-- Inventory Table -->
            <div class="inventory-table-container" style="margin-top: 0; position: relative;">
                <div style="position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(to right, #FFB75E, #FF7F50);"></div>
                <div class="table-header" style="margin-top: 5px; height: 50px; padding: 0.75rem 1rem;">
                    <div class="table-title">
                        <h3 style="font-size: 18px; font-weight: 600; color: #333; margin: 0; white-space: nowrap;">Product Inventory</h3>
                    </div>
                    <div class="inventory-filters" style="padding: 12px; display: flex; align-items: center; position: relative; margin-bottom: 15px;">
                        <div class="search-box" style="position: relative; margin: 0 150px 0 auto;">
                            <i class="fas fa-search" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #888; font-size: 14px;"></i>
                            <input type="text" id="inventorySearch" placeholder="Search products..." style="box-shadow: 0 2px 15px rgba(255, 183, 94, 0.2); border-radius: 50px; width: 600px; padding-left: 40px;">
                        </div>
                        <div class="filter-group" style="margin-left: auto; padding-right: 20px;">
                            <select id="categoryFilter" class="filter-select">
                                <option value="all">All Categories</option>
                                <option value="Main Dishes">Main Dishes</option>
                                <option value="Side Dishes">Side Dishes</option>
                                <option value="Desserts">Desserts</option>
                            </select>
                            <select id="statusFilter" class="filter-select">
                                <option value="all">All Status</option>
                                <option value="out">Out of Stock</option>
                                <option value="low">Low Stock (≤10)</option>
                                <option value="critical">Critical Stock (≤5)</option>
                                <option value="in">In Stock</option>
                            </select>
                        </div>
                    </div>

                    <style>
                        /* Enhanced Inventory Filters */
                        .inventory-filters {
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            margin-bottom: 16px;
                            padding: 0;
                            background: transparent;
                            gap: 16px;
                            width: 100%;
                        }

                        .search-box {
                            position: relative;
                            width: 800px;
                            margin-right: 300px;
                            background: #FFFFFF;
                        }

                        .search-box input {
                            width: 100%;
                            height: 32px;
                            padding: 6px 16px 6px 36px;
                            border: 1px solid #e5e7eb;
                            border-radius: 50px;
                            font-size: 13px;
                            background: #ffffff;
                            color: #555;
                            transition: all 0.2s ease;
                        }

                        .search-box input::placeholder {
                            color: #888;
                        }

                        .search-box i {
                            position: absolute;
                            left: 16px;
                            top: 50%;
                            transform: translateY(-50%);
                            color: #888;
                            font-size: 15px;
                        }

                        .search-box input:hover {
                            border-color: #FFB75E;
                            background: linear-gradient(to right, #ffffff, #fff0e6);
                            box-shadow: 0 2px 15px rgba(255, 183, 94, 0.3);
                        }

                        .search-box input:focus {
                            outline: none;
                            border-color: #FF7F50;
                            background: linear-gradient(to right, #ffffff, #ffe4d9);
                            box-shadow: 0 0 0 3px rgba(255, 127, 80, 0.1);
                        }

                        #inventorySearch {
                            width: 600px !important;
                            border-radius: 50px !important;
                            height: 32px;
                            padding: 6px 16px 6px 36px;
                            border: 1px solid #e5e7eb;
                            font-size: 13px;
                            background: #ffffff;
                            color: #555;
                            transition: all 0.2s ease;
                            margin-right: 300px;
                            box-shadow: 0 2px 15px rgba(255, 183, 94, 0.2);
                        }

                        .search-box i {
                            position: absolute;
                            left: 12px;
                            top: 50%;
                            transform: translateY(-50%);
                            color: #FF7F50;
                            opacity: 0.7;
                            font-size: 13px;
                        }

                        .filter-group {
                            display: flex;
                            gap: 12px;
                            align-items: center;
                            flex-shrink: 0;
                            margin-left: auto;
                            padding-right: 20px;
                        }

                        .filter-select {
                            height: 32px;
                            padding: 0 28px 0 12px;
                            border: 1px solid #e5e7eb;
                            border-radius: 20px;
                            font-size: 13px;
                            color: #666;
                            background: #ffffff;
                            cursor: pointer;
                            appearance: none;
                            min-width: 120px;
                            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 16 16'%3E%3Cpath fill='%23FF7F50' d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
                            background-repeat: no-repeat;
                            background-position: calc(100% - 10px) center;
                            transition: all 0.2s ease;
                        }

                        .filter-select:hover {
                            border-color: #FF7F50;
                            box-shadow: 0 0 0 4px rgba(255, 127, 80, 0.1);
                        }

                        .filter-select:focus {
                            outline: none;
                            border-color: #FF7F50;
                            box-shadow: 0 0 0 4px rgba(255, 127, 80, 0.15);
                        }
                    </style>
                </div>

                <div class="table-responsive">
                    <table class="inventory-table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Current Stock</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $select_products = mysqli_query($conn, "SELECT * FROM products ORDER BY name ASC");
                        if(mysqli_num_rows($select_products) > 0){
                            while($product = mysqli_fetch_assoc($select_products)){
                                // Determine stock status
                                $stock_status = '';
                                $status_class = '';
                                if($product['stock'] <= 0) {
                                    $stock_status = 'Out of Stock';
                                    $status_class = 'out-of-stock';
                                } else if($product['stock'] <= 10) {
                                    $stock_status = 'Low Stock';
                                    $status_class = 'low-stock';
                                } else {
                                    $stock_status = 'In Stock';
                                    $status_class = 'in-stock';
                                }
                        ?>
                            <tr>
                                <td><img src="../uploaded_img/<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>" class="product-img"></td>
                                <td><?php echo $product['name']; ?></td>
                                <td><?php echo $product['category'] ?? 'Uncategorized'; ?></td>
                                <td><?php echo $product['stock']; ?></td>
                                <td>₱<?php echo number_format($product['price'], 2); ?></td>
                                <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $stock_status; ?></span></td>
                                <td class="action-buttons">
                                    <button class="action-btn edit" title="Update Stock" onclick="updateStock(<?php echo $product['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="action-btn history" title="View Stock History" onclick="viewStockHistory(<?php echo $product['id']; ?>)">
                                        <i class="fas fa-history"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php
                            }
                        } else {
                            echo "<tr><td colspan='7' class='no-products'>No products found</td></tr>";
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Stock Update Modal -->
            <div id="stockUpdateModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Update Stock</h3>
                        <span class="close-modal" onclick="closeStockModal()">&times;</span>
                    </div>
                    <form id="stockUpdateForm" method="POST">
                        <input type="hidden" id="product_id" name="product_id">
                        <div class="form-group">
                            <label for="current_stock">Current Stock</label>
                            <input type="number" id="current_stock" readonly>
                        </div>
                        <div class="form-group">
                            <label for="stock_change">Add/Remove Stock</label>
                            <input type="number" id="stock_change" name="stock_change" required>
                            <small>Use positive number to add stock, negative to remove</small>
                        </div>
                        <div class="form-buttons">
                            <button type="button" class="cancel-btn" onclick="closeStockModal()">Cancel</button>
                            <button type="submit" class="submit-btn">Update Stock</button>
                        </div>
                    </form>
                </div>
            </div>
        </section>

        <!-- Menu Creation Section -->
        <section id="menu-creation-section" class="content-section hidden">
            
            <?php if(isset($message) && is_array($message)): ?>
                <?php foreach($message as $msg): ?>
                    <script>
                        showNotification(
                            '<?php echo strpos($msg, 'successfully') !== false ? 'Success' : 'Error' ?>',
                            '<?php echo addslashes($msg) ?>',
                            '<?php echo strpos($msg, 'successfully') !== false ? 'success' : 'error' ?>'
                        );
                    </script>
                <?php endforeach; ?>
            <?php endif; ?>

            <div class="container">
                <form action="" method="post" class="add-product-form" enctype="multipart/form-data">
                    <h3>Add a new product</h3>
                    <input type="text" name="p_name" placeholder="Enter the product name" class="box" required>
                    <select name="p_category" class="box" required>
                        <option value="">Select Category</option>
                        <option value="Main Dishes">Main Dishes</option>
                        <option value="Side Dishes">Side Dishes</option>
                        <option value="Desserts">Desserts</option>
                    </select>
                    <input type="number" name="p_price" min="0" placeholder="Enter the product price" class="box" required>
                    <div class="form-group">
                        <label for="p_image">Product Image: <span class="label-hint">(Add product image here)</span></label>
                        <div class="file-input-container">
                            <input type="file" id="p_image" name="p_image" accept="image/png, image/jpg, image/jpeg" required>
                            <div class="file-input-hint">Click here to upload product image</div>
                        </div>
                    </div>
                    <input type="submit" value="Add Product" name="add_product" class="btn">
                </form>

                <div class="display-product-table">
                    <div class="table-header">
                        <div class="table-title">
                            <i class="fas fa-utensils"></i> Product List
                        </div>
                    </div>
                    <table>
                        <thead>
                            <th width="80">Image</th>
                            <th>Product Name</th>
                            <th width="120">Category</th>
                            <th width="100">Price</th>
                            <th width="120">Actions</th>
                        </thead>
                        <tbody>
                            <?php
                            $select_products = mysqli_query($conn, "SELECT * FROM `products`");
                            if(mysqli_num_rows($select_products) > 0){
                                while($row = mysqli_fetch_assoc($select_products)){
                            ?>
                            <tr>
                                <td><img src="../uploaded_img/<?php echo $row['image']; ?>" class="product-image" alt="<?php echo $row['name']; ?>"></td>
                                <td>
                                    <div style="font-weight: 500;"><?php echo $row['name']; ?></div>
                                </td>
                                <td>
                                    <span class="category-badge <?php echo strtolower(str_replace(' ', '-', $row['category'])); ?>">
                                        <i class="fas fa-circle" style="font-size: 8px;"></i>
                                        <?php echo $row['category'] ?? 'Uncategorized'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="font-weight: 500;">₱<?php echo number_format($row['price'], 2); ?></div>
                                </td>
                                <td class="action-buttons">
                                    <a href="?delete=<?php echo $row['id']; ?>&section=menu-creation" class="action-btn delete-btn" onclick="return confirm('Are you sure you want to delete this item?');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <a href="?edit=<?php echo $row['id']; ?>&section=menu-creation" class="action-btn update-btn">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php
                                }    
                            }else{
                                echo "<div class='empty'>no product added</div>";
                            };
                            ?>
                        </tbody>
                    </table>
                </div>

                <div class="edit-form-container">
                    <?php
                    if(isset($_GET['edit'])){
                        $edit_id = $_GET['edit'];
                        $edit_query = mysqli_query($conn, "SELECT * FROM `products` WHERE id = $edit_id");
                        if(mysqli_num_rows($edit_query) > 0){
                            while($fetch_edit = mysqli_fetch_assoc($edit_query)){
                    ?>
                    <form action="" method="post" enctype="multipart/form-data" id="updateForm">
                        <h3>Update Product</h3>
                        <img src="../uploaded_img/<?php echo $fetch_edit['image']; ?>" height="200" alt="" class="preview-image">
                        <input type="hidden" name="update_p_id" value="<?php echo $fetch_edit['id']; ?>">
                        <div class="form-group">
                            <label for="update_p_name">Product Name</label>
                            <input type="text" id="update_p_name" class="box" required name="update_p_name" value="<?php echo htmlspecialchars($fetch_edit['name']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="update_p_category">Category</label>
                            <select id="update_p_category" class="box" required name="update_p_category">
                                <option value="Main Dishes" <?php echo ($fetch_edit['category'] == 'Main Dishes') ? 'selected' : ''; ?>>Main Dishes</option>
                                <option value="Side Dishes" <?php echo ($fetch_edit['category'] == 'Side Dishes') ? 'selected' : ''; ?>>Side Dishes</option>
                                <option value="Desserts" <?php echo ($fetch_edit['category'] == 'Desserts') ? 'selected' : ''; ?>>Desserts</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="update_p_price">Price (₱)</label>
                            <input type="number" id="update_p_price" min="0" class="box" required name="update_p_price" value="<?php echo $fetch_edit['price']; ?>">
                        </div>
                        <div class="file-input-container">
                            <label for="updateImage">Product Image</label>
                            <input type="file" class="box" name="update_p_image" accept="image/png, image/jpg, image/jpeg" id="updateImage">
                            <small class="file-hint">Leave empty to keep current image</small>
                        </div>
                        <div class="button-group">
                            <button type="submit" name="update_product" class="btn">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                            <button type="button" class="option-btn" id="close-edit">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                        </div>
                    </form>
                    <?php
                            }
                        }
                        echo "<script>document.querySelector('.edit-form-container').style.display = 'flex';</script>";
                        echo "<script>showSection('menu-creation');</script>";
                    }
                    ?>
                </div>
            </div>
        </section>

        <?php
        // Handle menu update notifications
        if (isset($_GET['section']) && $_GET['section'] === 'menu-creation' && isset($_GET['action'])) {
            $action = $_GET['action'];
            if ($action === 'update') {
                echo "<script>alert('Product updated successfully');</script>";
            } else if ($action === 'error') {
                echo "<script>alert('Error: Product could not be updated');</script>";
            }
        }
        ?>
        
        <!-- Dashboard Section -->
        <section id="dashboard-section" class="content-section">
            <div class="welcome-header">
                <h2>Dashboard</h2>
                <p>Welcome to your admin dashboard</p>
            </div>
        </section>

        <!-- User Roles Section -->
        <section id="roles-section" class="content-section hidden">
            <?php
            // Handle login state and notifications
            if (!isset($_SESSION['notifications_initialized'])) {
                // Initialize notification state
                $_SESSION['notifications_initialized'] = true;
                $_SESSION['last_notification_time'] = 0;
            }

            // Check if this is a fresh login (within last 5 seconds)
            $isFreshLogin = isset($_SESSION['login_timestamp']) && 
                           (time() - $_SESSION['login_timestamp']) <= 5;

            // Handle dashboard redirect on fresh login
            if (isset($_SESSION['show_dashboard']) && $_SESSION['show_dashboard'] === true) {
                echo "<script>
                    sessionStorage.removeItem('currentSection');
                    showSection('dashboard-section');
                </script>";
                unset($_SESSION['show_dashboard']);
            }

            // Show welcome message only on fresh login
            if ($isFreshLogin && !isset($_SESSION['welcome_shown'])) {
                if (isset($_SESSION['message'])) {
                    echo "<script>
                        alert('Welcome back! You have successfully logged in.');
                    </script>";
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                }
                $_SESSION['welcome_shown'] = true;
            }
            ?>
            
           

            <div class="role-cards">
                <div class="role-card">
                    <div class="role-icon">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <h3>Administrator</h3>
                    <p>System management with full access to administrative features, including user management, inventory control, and system settings.</p>
                    <ul class="role-features">
                        <li><i class="fas fa-check"></i> Full system access</li>
                        <li><i class="fas fa-check"></i> User management</li>
                        <li><i class="fas fa-check"></i> Reports & Analytics</li>
                    </ul>
                    <button class="create-btn" onclick="showCreateUserForm(2)">
                        <i class="fas fa-plus"></i> Create Admin
                    </button>
                </div>

                <div class="role-card">
                    <div class="role-icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <h3>Crew Member</h3>
                    <p>Staff account for order processing, inventory management, and customer service operations.</p>
                    <ul class="role-features">
                        <li><i class="fas fa-check"></i> Order management</li>
                        <li><i class="fas fa-check"></i> Inventory tracking</li>
                        <li><i class="fas fa-check"></i> Customer support</li>
                    </ul>
                    <button class="create-btn" onclick="showCreateUserForm(3)">
                        <i class="fas fa-plus"></i> Create Crew
                    </button>
                </div>

                <div class="role-card">
                    <div class="role-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <h3>Customer</h3>
                    <p>Regular user account with access to ordering system, order tracking, and profile management.</p>
                    <ul class="role-features">
                        <li><i class="fas fa-check"></i> Place orders</li>
                        <li><i class="fas fa-check"></i> Track deliveries</li>
                        <li><i class="fas fa-check"></i> Manage profile</li>
                    </ul>
                    <button class="create-btn" onclick="showCreateUserForm(4)">
                        <i class="fas fa-plus"></i> Create Customer
                    </button>
                </div>
            </div>



            <!-- Create User Form Modal -->
            <div id="createUserModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Create New User</h3>
                        <span class="close-modal" onclick="hideCreateUserForm()">&times;</span>
                    </div>
                    <form id="createUserForm" method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>?section=roles" onsubmit="return validateForm()">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="firstName">First Name</label>
                                <input type="text" id="firstName" name="firstName" required 
                                       value="<?php echo isset($_POST['firstName']) ? htmlspecialchars($_POST['firstName']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label for="lastName">Last Name</label>
                                <input type="text" id="lastName" name="lastName" required
                                       value="<?php echo isset($_POST['lastName']) ? htmlspecialchars($_POST['lastName']) : ''; ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" required
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" required
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="password">Password</label>
                                <div class="password-input">
                                    <input type="password" id="password" name="password" required>
                                    <button type="button" class="toggle-password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="confirmPassword">Confirm Password</label>
                                <div class="password-input">
                                    <input type="password" id="confirmPassword" name="confirmPassword" required>
                                    <button type="button" class="toggle-password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="createUser" value="1">
                        <input type="hidden" name="roleId" id="roleId">

                        <!-- Admin Permissions Section -->
                        <div id="adminPermissions" class="permissions-section" style="display: none;">
                            <h4>Admin Permissions</h4>
                            <div class="permissions-grid">
                                <div class="permission-item">
                                    <input type="checkbox" id="perm_dashboard" name="permissions[]" value="dashboard">
                                    <label for="perm_dashboard">Dashboard Access</label>
                                </div>
                                <div class="permission-item">
                                    <input type="checkbox" id="perm_landing" name="permissions[]" value="landing">
                                    <label for="perm_landing">Landing Settings</label>
                                </div>
                                <div class="permission-item">
                                    <input type="checkbox" id="perm_inventory" name="permissions[]" value="inventory">
                                    <label for="perm_inventory">Inventory Management</label>
                                </div>
                                <div class="permission-item">
                                    <input type="checkbox" id="perm_menu" name="permissions[]" value="menu">
                                    <label for="perm_menu">Menu Creation</label>
                                </div>
                                <div class="permission-item">
                                    <input type="checkbox" id="perm_orders" name="permissions[]" value="orders">
                                    <label for="perm_orders">Order Management</label>
                                </div>
                                <div class="permission-item">
                                    <input type="checkbox" id="perm_reports" name="permissions[]" value="reports">
                                    <label for="perm_reports">Reports Access</label>
                                </div>
                            </div>
                        </div>

                        <div class="form-buttons">
                            <button type="button" class="cancel-btn" onclick="hideCreateUserForm()">Cancel</button>
                            <button type="submit" name="createUser" class="submit-btn">Create Account</button>
                        </div>
                    </form>
                </div>
            </div>
        </section>

        <!-- Landing Settings Section -->
        <section id="landing-section" class="content-section hidden">
            

            <div class="settings-grid">
                <!-- Restaurant Branding -->
                <div class="settings-card">
                    <h3><i class="fas fa-store"></i> Restaurant Branding</h3>
                    <div class="form-group">
                        <label>Restaurant Name</label>
                        <input type="text" class="settings-input" id="restaurantName" placeholder="K-Food Delight">
                    </div>
                    <div class="form-group">
                        <label>Upload Logo</label>
                        <div class="upload-container">
                            <img id="logoPreview" src="../images/logo.png" alt="Logo Preview">
                            <input type="file" id="logoUpload" accept="image/*" class="file-input">
                            <div class="upload-label">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span>Drop logo here or <span class="browse-text">browse</span></span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Upload Favicon</label>
                        <div class="upload-container small">
                            <img id="faviconPreview" src="../images/logo.png" alt="Favicon Preview">
                            <input type="file" id="faviconUpload" accept="image/*" class="file-input">
                            <div class="upload-label">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span>Drop favicon or <span class="browse-text">browse</span></span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Tagline</label>
                        <textarea class="settings-input" id="tagline" placeholder="Enter your restaurant's tagline"></textarea>
                    </div>
                </div>

                <!-- Hero Section -->
                <div class="settings-card">
                    <h3><i class="fas fa-image"></i> Hero Section</h3>
                    <div class="form-group">
                        <label>Hero Title</label>
                        <input type="text" class="settings-input" id="heroTitle" placeholder="K-FOOD DELIGHTS">
                    </div>
                    <div class="form-group">
                        <label>Hero Subtitle</label>
                        <textarea class="settings-input" id="heroSubtitle" placeholder="Experience authentic Korean cuisine..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Background Image</label>
                        <div class="upload-container hero">
                            <img id="heroPreview" src="../images/lasagna.jpg" alt="Hero Preview">
                            <input type="file" id="heroUpload" accept="image/*" class="file-input">
                            <div class="upload-label">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span>Drop hero image or <span class="browse-text">browse</span></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- About Us Section -->
                <div class="settings-card">
                    <h3><i class="fas fa-info-circle"></i> About Us Section</h3>
                    <div class="form-group">
                        <label>Restaurant Story</label>
                        <textarea class="settings-input" id="aboutUs" rows="5" placeholder="Tell your restaurant's story..."></textarea>
                    </div>
                    <div class="features-container">
                        <label>Features</label>
                        <div id="featuresList" class="features-list">
                            <!-- Features will be added here -->
                        </div>
                        <button type="button" class="add-feature-btn" onclick="addFeature()">
                            <i class="fas fa-plus"></i> Add Feature
                        </button>
                    </div>
                </div>

                <!-- Contact & Footer -->
                <div class="settings-card">
                    <h3><i class="fas fa-address-book"></i> Contact & Footer</h3>
                    <div class="form-group">
                        <label>Address</label>
                        <textarea class="settings-input" id="address" placeholder="Your restaurant's address"></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group half">
                            <label>Phone</label>
                            <input type="tel" class="settings-input" id="phone" placeholder="Contact number">
                        </div>
                        <div class="form-group half">
                            <label>Email</label>
                            <input type="email" class="settings-input" id="email" placeholder="Contact email">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Operating Hours</label>
                        <textarea class="settings-input" id="hours" placeholder="e.g., Mon-Fri: 9AM-10PM"></textarea>
                    </div>
                    <div class="social-links">
                        <label>Social Media Links</label>
                        <div class="form-group">
                            <div class="social-input">
                                <i class="fab fa-facebook"></i>
                                <input type="url" class="settings-input" id="facebook" placeholder="Facebook URL">
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="social-input">
                                <i class="fab fa-instagram"></i>
                                <input type="url" class="settings-input" id="instagram" placeholder="Instagram URL">
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="social-input">
                                <i class="fab fa-tiktok"></i>
                                <input type="url" class="settings-input" id="tiktok" placeholder="TikTok URL">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Theme & Design -->
                <div class="settings-card span-2">
                    <h3><i class="fas fa-paint-brush"></i> Theme & Design</h3>
                    <div class="form-row">
                        <div class="form-group third">
                            <label>Primary Color</label>
                            <input type="color" class="color-picker" id="primaryColor" value="#FF7F50">
                        </div>
                        <div class="form-group third">
                            <label>Secondary Color</label>
                            <input type="color" class="color-picker" id="secondaryColor" value="#FFB75E">
                        </div>
                        <div class="form-group third">
                            <label>Font Style</label>
                            <select class="settings-input" id="fontStyle">
                                <option value="Poppins">Poppins</option>
                                <option value="Roboto">Roboto</option>
                                <option value="Open Sans">Open Sans</option>
                                <option value="Montserrat">Montserrat</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group half">
                            <label>Layout Style</label>
                            <select class="settings-input" id="layoutStyle">
                                <option value="centered">Centered</option>
                                <option value="full-width">Full Width</option>
                                <option value="boxed">Boxed</option>
                            </select>
                        </div>
                        <div class="form-group half">
                            <label>Newsletter Signup</label>
                            <div class="toggle-switch">
                                <input type="checkbox" id="newsletterToggle">
                                <label for="newsletterToggle"></label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="save-section">
                <button type="button" class="save-settings" onclick="saveLandingSettings()">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>

            <style>
                /* Landing Settings Styles */
                .settings-grid {
                    display: grid;
                    grid-template-columns: repeat(2, 1fr);
                    gap: 20px;
                    padding: 20px;
                }

                .settings-card {
                    background: white;
                    border-radius: 12px;
                    padding: 20px;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                }

                .settings-card.span-2 {
                    grid-column: span 2;
                }

                .settings-card h3 {
                    color: #333;
                    font-size: 18px;
                    margin-bottom: 20px;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }

                .settings-card h3 i {
                    color: #FF7F50;
                }

                .form-group {
                    margin-bottom: 20px;
                }

                .form-row {
                    display: flex;
                    gap: 20px;
                    margin-bottom: 20px;
                }

                .form-group.half {
                    flex: 1;
                }

                .form-group.third {
                    flex: 1;
                }

                label {
                    display: block;
                    margin-bottom: 8px;
                    color: #555;
                    font-weight: 500;
                }

                .settings-input {
                    width: 100%;
                    padding: 8px 12px;
                    border: 1px solid #ddd;
                    border-radius: 6px;
                    font-size: 14px;
                    transition: all 0.3s ease;
                }

                .settings-input:focus {
                    border-color: #FF7F50;
                    outline: none;
                    box-shadow: 0 0 0 3px rgba(255, 127, 80, 0.1);
                }

                textarea.settings-input {
                    resize: vertical;
                    min-height: 80px;
                }

                .upload-container {
                    border: 2px dashed #ddd;
                    border-radius: 8px;
                    padding: 20px;
                    text-align: center;
                    position: relative;
                    transition: all 0.3s ease;
                }

                .upload-container:hover {
                    border-color: #FF7F50;
                }

                .upload-container.small {
                    padding: 10px;
                }

                .upload-container.hero {
                    aspect-ratio: 16/9;
                }

                .upload-container img {
                    max-width: 100%;
                    max-height: 200px;
                    object-fit: contain;
                    margin-bottom: 10px;
                }

                .upload-container.small img {
                    max-height: 60px;
                }

                .upload-container.hero img {
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                }

                .file-input {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    opacity: 0;
                    cursor: pointer;
                }

                .upload-label {
                    color: #666;
                }

                .upload-label i {
                    font-size: 24px;
                    color: #FF7F50;
                    margin-bottom: 8px;
                }

                .browse-text {
                    color: #FF7F50;
                    text-decoration: underline;
                    cursor: pointer;
                }

                .features-list {
                    margin: 10px 0;
                }

                .feature-item {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    margin-bottom: 10px;
                }

                .add-feature-btn {
                    background: none;
                    border: 1px dashed #FF7F50;
                    color: #FF7F50;
                    padding: 8px 16px;
                    border-radius: 6px;
                    cursor: pointer;
                    transition: all 0.3s ease;
                }

                .add-feature-btn:hover {
                    background: rgba(255, 127, 80, 0.1);
                }

                .social-input {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }

                .social-input i {
                    font-size: 20px;
                    color: #FF7F50;
                    width: 24px;
                }

                .color-picker {
                    width: 100%;
                    height: 40px;
                    padding: 0;
                    border: none;
                    border-radius: 6px;
                    cursor: pointer;
                }

                .toggle-switch {
                    position: relative;
                    display: inline-block;
                    width: 60px;
                    height: 34px;
                }

                .toggle-switch input {
                    opacity: 0;
                    width: 0;
                    height: 0;
                }

                .toggle-switch label {
                    position: absolute;
                    cursor: pointer;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background-color: #ccc;
                    transition: .4s;
                    border-radius: 34px;
                }

                .toggle-switch label:before {
                    position: absolute;
                    content: "";
                    height: 26px;
                    width: 26px;
                    left: 4px;
                    bottom: 4px;
                    background-color: white;
                    transition: .4s;
                    border-radius: 50%;
                }

                .toggle-switch input:checked + label {
                    background-color: #FF7F50;
                }

                .toggle-switch input:checked + label:before {
                    transform: translateX(26px);
                }

                .save-section {
                    padding: 20px;
                    text-align: right;
                }

                .save-settings {
                    background: #FF7F50;
                    color: white;
                    border: none;
                    padding: 12px 24px;
                    border-radius: 6px;
                    font-weight: 500;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
                }

                .save-settings:hover {
                    background: #ff6b3d;
                    transform: translateY(-1px);
                }

                .save-settings:active {
                    transform: translateY(0);
                }
            </style>
        </section>

        <!-- User Accounts Section -->
        <section id="accounts-section" class="content-section hidden">
          

            <!-- Users Table -->
            <div class="users-table-container">
                <h3>Registered Users</h3>
                <div class="table-actions">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="userSearch" placeholder="Search users..." style="width: 300px;">
                    </div>
                    <select id="roleFilter" class="role-filter">
                        <option value="all">All Roles</option>
                        <option value="2">Administrators</option>
                        <option value="3">Crew Members</option>
                        <option value="4">Customers</option>
                    </select>
                </div>
                <style>
                .table-responsive {
                    overflow-x: auto;
                    border-radius: 8px;
                    background: #ffffff;
                    transition: background-color 0.3s ease;
                }
                .users-table {
                    font-size: 14px;
                    width: 100%;
                    min-width: 1200px;
                    background: transparent;
                }
                .users-table th, .users-table td {
                    padding: 12px 16px;
                    white-space: nowrap;
                    max-width: 200px;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    transition: all 0.3s ease;
                }

                [data-theme="dark"] .table-responsive {
                    background: #262833;
                    border-radius: 8px;
                    overflow: hidden;
                }

                [data-theme="dark"] .users-table {
                    border-collapse: separate;
                    border-spacing: 0 4px;
                    margin-top: -4px;
                }

                [data-theme="dark"] .users-table th {
                    background: #1E1E2D;
                    color: rgba(255, 255, 255, 0.9) !important;
                    font-weight: 500;
                    padding: 12px 16px;
                    font-size: 0.85rem;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    border: none;
                }

                [data-theme="dark"] .users-table td {
                    background: #1A1B25;
                    color: rgba(255, 255, 255, 0.8);
                    padding: 16px;
                    border: none;
                }

                [data-theme="dark"] .users-table tr td:first-child {
                    border-top-left-radius: 8px;
                    border-bottom-left-radius: 8px;
                }

                [data-theme="dark"] .users-table tr td:last-child {
                    border-top-right-radius: 8px;
                    border-bottom-right-radius: 8px;
                }

                [data-theme="dark"] .users-table tr:hover td {
                    background: #1E1E2D;
                    transition: background-color 0.2s ease;
                }

                [data-theme="dark"] .role-badge {
                    padding: 6px 12px;
                    border-radius: 6px;
                    font-size: 0.85rem;
                    font-weight: 500;
                }

                [data-theme="dark"] .role-badge.role-2 {
                    background: rgba(255, 127, 80, 0.15);
                    color: #FF7F50;
                }

                [data-theme="dark"] .role-badge.role-3 {
                    background: rgba(99, 102, 241, 0.15);
                    color: #818cf8;
                }

                [data-theme="dark"] .role-badge.role-4 {
                    background: rgba(16, 185, 129, 0.15);
                    color: #10b981;
                }

                .users-table-container {
                    position: relative;
                    padding: 24px;
                    border-radius: 12px;
                    margin: 15px 0;
                    border: 1px solid rgba(255, 255, 255, 0.05);
                }

                [data-theme="dark"] .users-table-container {
                    background: #1A1B25;
                    box-shadow: 0 2px 6px 0 rgba(0, 0, 0, 0.1);
                }

                .users-table-container::before {
                    content: '';
                    position: absolute;
                    top: -1px;
                    left: -1px;
                    right: -1px;
                    height: 3px;
                    background: linear-gradient(90deg, #FF7F50, #FFB75E);
                    border-radius: 12px 12px 0 0;
                }

                [data-theme="dark"] .users-table-container h3 {
                    color: #ffffff;
                    margin-bottom: 24px;
                    font-size: 1.25rem;
                    font-weight: 600;
                    letter-spacing: 0.3px;
                }

                .table-actions {
                    margin-bottom: 20px;
                    display: flex;
                    gap: 15px;
                    align-items: center;
                }

                [data-theme="dark"] .table-actions input,
                [data-theme="dark"] .table-actions select {
                    background: #151521;
                    border: 1px solid rgba(255, 255, 255, 0.05);
                    color: #ffffff;
                    border-radius: 8px;
                    padding: 10px 15px;
                }

                [data-theme="dark"] .table-responsive {
                    background: transparent;
                }

                [data-theme="dark"] .role-badge.role-2 {
                    background: rgba(255, 127, 80, 0.2);
                    color: #FFB75E;
                }

                [data-theme="dark"] .role-badge {
                    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
                }

                [data-theme="dark"] .search-box input {
                    background: #151521;
                    border: 1px solid #2B2B40;
                    color: #ffffff;
                    border-radius: 6px;
                    padding: 10px 15px 10px 40px;
                }

                [data-theme="dark"] .search-box i {
                    color: #ffffff;
                }

                [data-theme="dark"] .role-filter {
                    background: #151521;
                    border: 1px solid #2B2B40;
                    color: #ffffff;
                    border-radius: 6px;
                    padding: 10px 15px;
                }

                [data-theme="dark"] .users-table-container h3 {
                    color: #ffffff;
                }
                .users-table td img {
                    display: block;
                    margin: 0 auto;
                }
                .users-table .actions {
                    width: 100px;
                }
            </style>
            <div class="table-responsive">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Profile</th>
                                <th>First Name</th>
                                <th>Last Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Address</th>
                                <th>Role</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $users_query = "SELECT 
                                          u.id,
                                          u.FirstName,
                                          u.LastName,
                                          u.username,
                                          u.Email,
                                          u.phone,
                                          u.address,
                                          u.profile_picture,
                                          u.role_id,
                                          CASE 
                                              WHEN u.role_id = 1 THEN 'Super Admin'
                                              WHEN u.role_id = 2 THEN 'Administrator'
                                              WHEN u.role_id = 3 THEN 'Crew Member'
                                              WHEN u.role_id = 4 THEN 'Customer'
                                              ELSE 'Unknown'
                                          END as role_name
                                          FROM users u
                                          ORDER BY u.role_id ASC, u.id DESC";
                            $users_result = mysqli_query($conn, $users_query);

                            while($user = mysqli_fetch_assoc($users_result)) {
                                echo "<tr data-role='{$user['role_id']}'>";
                                echo "<td>{$user['id']}</td>";
                                echo "<td>";
                                if ($user['profile_picture']) {
                                    echo "<img src='../uploaded_img/" . htmlspecialchars($user['profile_picture']) . "' alt='Profile' style='width: 40px; height: 40px; border-radius: 50%; object-fit: cover;'>";
                                } else {
                                    echo "<img src='../images/user.png' alt='Default Profile' style='width: 40px; height: 40px; border-radius: 50%; object-fit: cover;'>";
                                }
                                echo "</td>";
                                echo "<td>" . htmlspecialchars($user['FirstName']) . "</td>";
                                echo "<td>" . htmlspecialchars($user['LastName']) . "</td>";
                                echo "<td>" . htmlspecialchars($user['username']) . "</td>";
                                echo "<td>" . htmlspecialchars($user['Email']) . "</td>";
                                echo "<td>" . htmlspecialchars($user['phone'] ?: 'N/A') . "</td>";
                                echo "<td>" . htmlspecialchars($user['address'] ?: 'N/A') . "</td>";
                                echo "<td><span class='role-badge role-{$user['role_id']}'>" . htmlspecialchars($user['role_name']) . "</span></td>";
                                echo "<td class='actions'>";
                                if($user['role_id'] != 1) { // Don't show actions for super admin
                                    echo "<button class='action-btn edit-btn' onclick='editUser({$user['id']})'><i class='fas fa-edit'></i></button>";
                                    echo "<button class='action-btn delete-btn' onclick='deleteUser({$user['id']})'><i class='fas fa-trash'></i></button>";
                                }
                                echo "</td>";
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- Orders Section -->
        <section id="orders-section" class="content-section hidden">
            

            <div class="orders-container">
                <!-- Order Filters -->
                <div class="filter-bar">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="orderSearch" placeholder="Search orders...">
                    </div>
                    <select id="statusFilter" class="status-filter">
                        <option value="all">All Status</option>
                        <option value="pending" selected>Pending</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <!-- Orders Table -->
                <div class="table-responsive">
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th style="width: 100px;">Order ID</th>
                                <th style="width: 200px;">Customer</th>
                                <th style="width: 200px;">Delivery Address</th>
                                <th style="width: 120px;">Payment</th>
                                <th style="width: 200px;">Ordered Items</th>
                                <th style="width: 100px;">Total</th>
                                <th style="width: 100px;">Status</th>
                                <th style="width: 150px;">Order Time</th>
                                <th style="width: 100px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        // Join with users table to get profile picture and user details
                        $select_orders = mysqli_query($conn, "
                            SELECT 
                                o.*,
                                u.profile_picture,
                                u.FirstName,
                                u.LastName,
                                u.Id as user_id,
                                o.item_name,
                                CONCAT(o.total_products, ' items') as quantity_text
                            FROM orders o 
                            LEFT JOIN users u ON o.name = CONCAT(u.FirstName, ' ', u.LastName)
                            ORDER BY o.order_time DESC
                        ");

                        if (mysqli_num_rows($select_orders) > 0) {
                            while ($row = mysqli_fetch_assoc($select_orders)) {
                                $statusClass = strtolower($row['status']);
                                ?>
                                <tr data-order-id="<?php echo $row['id']; ?>">
                                    <td>
                                        <span class="order-id">#<?php echo str_pad($row['id'], 5, '0', STR_PAD_LEFT); ?></span>
                                    </td>
                                    <td class="customer-info">
                                        <div class="customer-profile">
                                            <?php if ($row['profile_picture']): ?>
                                                <img src="../uploaded_img/<?php echo htmlspecialchars($row['profile_picture']); ?>" alt="Profile" onerror="this.src='../images/user.png'">
                                            <?php else: ?>
                                                <img src="../images/user.png" alt="Default Profile">
                                            <?php endif; ?>
                                            <div class="customer-details">
                                                <?php
                                                $name_parts = explode(' ', $row['name']);
                                                $firstName = array_shift($name_parts);
                                                $lastName = implode(' ', $name_parts);
                                                ?>
                                                <span class="customer-name"><?php echo htmlspecialchars($firstName); ?></span>
                                                <span class="customer-lastname"><?php echo htmlspecialchars($lastName); ?></span>
                                                <small class="customer-id">ID: <?php echo $row['user_id']; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['address']); ?></td>
                                    <td>
                                        <span class="payment-badge <?php echo $row['method']; ?>">
                                            <?php echo $row['method'] === 'cod' ? 'COD' : 'GCash'; ?>
                                        </span>
                                    </td>
                                    <td class="order-items-column">
                                        <div class="order-items-details">
                                            <?php if (!empty($row['item_name'])): ?>
                                                <div class="item-name"><?php echo htmlspecialchars($row['item_name']); ?></div>
                                                <div class="item-quantity"><?php echo htmlspecialchars($row['quantity_text']); ?></div>
                                            <?php else: ?>
                                                <div class="item-quantity"><?php echo htmlspecialchars($row['quantity_text']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>₱<?php echo number_format($row['total_price'], 2); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $statusClass; ?>">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y g:i A', strtotime($row['order_time'])); ?></td>
                                    <td class="actions">
                                        <button class="action-btn view-btn" onclick="viewOrder(<?php echo $row['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($row['status'] === 'pending'): ?>
                                            <button class="action-btn complete-btn" onclick="completeOrder(<?php echo $row['id']; ?>)">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php
                            }
                        } else {
                            echo '<tr><td colspan="9" class="no-orders">No orders found</td></tr>';
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Order Details Modal -->
            <div id="orderModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Order Details</h3>
                        <span class="close-modal">&times;</span>
                    </div>
                    <div class="modal-body">
                        <!-- Order details will be loaded here -->
                    </div>
                </div>
            </div>
        </section>

        <style>
        .orders-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .filter-bar {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            gap: 15px;
        }

        .search-box {
            flex: 1;
            max-width: 300px;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 10px 10px 10px 35px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .search-box i {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }

        .status-filter {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            min-width: 150px;
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
        }

        .orders-table th,
        .orders-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
            vertical-align: middle;
        }

        .orders-table th {
            background-color: #f5f5f5;
            font-weight: 600;
            color: #333;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #ddd;
        }

        .orders-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .orders-table td {
            font-size: 0.9rem;
            color: #444;
        }

        .customer-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .customer-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 5px;
        }

        .customer-profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .customer-details {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .customer-name {
            font-weight: 600;
            color: #333;
            font-size: 0.95rem;
            line-height: 1.2;
        }

        .customer-lastname {
            color: #666;
            font-size: 0.9rem;
            line-height: 1.2;
        }

        .customer-id {
            color: #888;
            font-size: 0.8rem;
            margin-top: 2px;
        }

        .payment-badge.cod {
            background-color: #e3f2fd;
            color: #1976d2;
            font-size: 0.85rem;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 500;
        }

        .order-id {
            font-family: 'Courier New', monospace;
            font-weight: 600;
            color: #555;
            background: #f0f0f0;
            padding: 4px 8px;
            border-radius: 4px;
        }

        .order-items-column {
            max-width: 200px;
            padding: 12px 15px;
        }

        .order-items-details {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .item-name {
            font-weight: 500;
            color: #333;
            font-size: 0.95rem;
        }

        .item-quantity {
            color: #666;
            font-size: 0.85rem;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            font-family: 'Inter', sans-serif;
            display: inline-block;
            text-align: center;
        }

        .status-badge.pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-badge.completed {
            background-color: #d4edda;
            color: #155724;
        }

        .status-badge.cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }

        .payment-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.85rem;
            background: #e9ecef;
        }

        .payment-badge.cod {
            background-color: #e3f2fd;
            color: #0d47a1;
        }

        .payment-badge.gcash {
            background-color: #2196f3;
            color: white;
        }

        .actions {
            display: flex;
            gap: 5px;
        }

        .action-btn {
            padding: 4px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
            background: transparent;
            font-size: 13px;
            margin: 0 2px;
        }
        
        .action-btn:hover {
            background: rgba(0,0,0,0.05);
        }

        .action-btn i {
            font-size: 14px;
        }

        .action-btn.view-btn {
            background-color: #e3f2fd;
            color: #1976d2;
        }

        .action-btn.complete-btn {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .action-btn:hover {
            opacity: 0.8;
        }

        .no-orders {
            text-align: center;
            color: #666;
            padding: 20px;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }

        .modal-content {
            background: white;
            width: 90%;
            max-width: 600px;
            margin: 50px auto;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .modal-header {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-body {
            padding: 20px;
        }

        .close-modal {
            cursor: pointer;
            font-size: 1.5rem;
        }

        .close-modal:hover {
            color: #666;
        }
        </style>
            
        </section>

        <!-- Settings Section -->
        <section id="settings-section" class="content-section hidden">
            <h2>Settings</h2>
            <p>Manage system preferences.</p>
            <label class="switch">
                <input type="checkbox" id="dark-mode-toggle" onclick="toggleDarkMode()">
                <span class="slider"></span>
            </label>
            <p id="dark-mode-status">Dark mode is off</p>
        </section>
    </div>

    <script>
        // Notification function
        // Function to update section title and icon
        function updateSectionTitle(section) {
            const titleElement = document.querySelector('.header-title h1');
            const iconElement = document.querySelector('.header-title i');
            
            // Define section titles and icons
            const sectionInfo = {
                'dashboard': { title: 'Dashboard', icon: 'fas fa-chart-pie' },
                'inventory': { title: 'Inventory Management', icon: 'fas fa-boxes' },
                'menu-creation': { title: 'Menu Creation', icon: 'fas fa-utensils' },
                'roles': { title: 'User Roles', icon: 'fas fa-user-shield' },
                'accounts': { title: 'User Accounts', icon: 'fas fa-users-cog' },
                'reports': { title: 'Reports & Analytics', icon: 'fas fa-chart-line' },
                'orders': { title: 'Order Management', icon: 'fas fa-shopping-basket' },
                'landing': { title: 'Landing Settings', icon: 'fas fa-home' }
            };

            // Store the current section in sessionStorage
            sessionStorage.setItem('currentSection', section);

            const info = sectionInfo[section] || sectionInfo['dashboard'];
            
            // Update title and icon
            titleElement.textContent = info.title;
            iconElement.className = info.icon;
        }

        // Add event listeners to menu items
        document.addEventListener('DOMContentLoaded', function() {
            const menuItems = document.querySelectorAll('.menu-item a');
            menuItems.forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    const section = this.getAttribute('data-section');
                    if (section) {
                        updateSectionTitle(section);
                    }
                });
            });
        });

        function showNotification(title, message, type = 'info') {
            // Check if this is a fresh page load without any action parameters
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('action')) {
                return; // Don't show notifications if this is a redirect with action
            }
            
            const container = document.getElementById('notificationContainer');
            if (!container) {
                console.error('Notification container not found');
                return;
            }
            
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            
            // Create notification content
            notification.innerHTML = `
                <i class="notification-icon fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
                <div class="notification-content">
                    <div class="notification-title">${title}</div>
                    <div class="notification-message">${message}</div>
                </div>
                <button class="notification-close" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
                <div class="notification-progress"></div>
            `;
            
            // Add to container
            container.appendChild(notification);
            
            // Remove after animation
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }

        // Form validation function
        function validateForm() {
            const form = document.getElementById('createUserForm');
            const password = form.querySelector('#password').value;
            const confirmPassword = form.querySelector('#confirmPassword').value;
            const roleId = form.querySelector('#roleId').value;
            const firstName = form.querySelector('#firstName').value;
            const lastName = form.querySelector('#lastName').value;
            const username = form.querySelector('#username').value;
            const email = form.querySelector('#email').value;

            console.log('Form submission started');
            console.log('Form data:', {
                firstName: firstName,
                lastName: lastName,
                username: username,
                email: email,
                roleId: roleId
            });

            if (!firstName || !lastName || !username || !email || !password || !confirmPassword || !roleId) {
                showNotification('Error', 'All fields are required', 'error');
                return false;
            }

            if (password !== confirmPassword) {
                showNotification('Error', 'Passwords do not match', 'error');
                return false;
            }

            // Add hidden input for debug purposes
            const debug = document.createElement('input');
            debug.type = 'hidden';
            debug.name = 'debug_info';
            debug.value = JSON.stringify({
                formSubmitted: true,
                timestamp: new Date().toISOString()
            });
            form.appendChild(debug);

            return true;
        }

        // Function to show create user form
        function showCreateUserForm(roleId) {
            document.getElementById('roleId').value = roleId;
            document.getElementById('createUserModal').style.display = 'block';
            
            // Show/hide admin permissions section based on role
            const adminPermissions = document.getElementById('adminPermissions');
            if(adminPermissions) {
                adminPermissions.style.display = roleId === 2 ? 'block' : 'none';
            }
        }

        // Function to hide create user form
        function hideCreateUserForm() {
            document.getElementById('createUserModal').style.display = 'none';
            document.getElementById('createUserForm').reset();
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('createUserModal');
            if (event.target === modal) {
                hideCreateUserForm();
            }
        }

        function showSection(sectionId) {
            // Map section IDs to their corresponding icons and titles
            const sectionMap = {
                'dashboard': { icon: 'chart-pie', title: 'Dashboard' },
                'inventory': { icon: 'boxes', title: 'Inventory Management' },
                'menu-creation': { icon: 'utensils', title: 'Menu Creation' },
                'orders': { icon: 'shopping-cart', title: 'Orders' },
                'roles': { icon: 'user-shield', title: 'User Roles' },
                'accounts': { icon: 'users-cog', title: 'User Accounts' },
                'landing': { icon: 'home', title: 'Landing Settings' },
                'reports': { icon: 'chart-line', title: 'Reports & Analytics' }
            };

            // Get section info
            const sectionInfo = sectionMap[sectionId] || sectionMap['dashboard'];

            // Update header title - IMPORTANT: This must be done first
            const headerTitleDiv = document.getElementById('section-title');
            if (headerTitleDiv) {
                headerTitleDiv.innerHTML = `
                    <i class="fas fa-${sectionInfo.icon}"></i>
                    <h1>${sectionInfo.title}</h1>
                `;
                headerTitleDiv.style.display = 'flex';
            }

            // Remove active class from all sections and hide them
            document.querySelectorAll('.content-section').forEach(section => {
                section.classList.remove('active');
                section.style.display = 'none';
            });
            
            // Remove active class from all menu items
            document.querySelectorAll('.menu-item').forEach(item => {
                item.classList.remove('active');
            });

            // Add active class to current menu item
            const menuItem = document.getElementById(`${sectionId}-item`);
            if (menuItem) {
                menuItem.classList.add('active');
            }
            
            // Show current section
            const fullSectionId = sectionId.endsWith('-section') ? sectionId : `${sectionId}-section`;
            const sectionToShow = document.getElementById(fullSectionId);
            if (sectionToShow) {
                sectionToShow.classList.add('active');
                sectionToShow.style.display = 'block';
            }
            
            // Update URL without reloading
            const newUrl = window.location.pathname + '?section=' + sectionId;
            window.history.pushState({ section: sectionId }, '', newUrl);
        }

        // Initialize dashboard as default section
        // Stock Management Functions
function updateStock(productId) {
    // Fetch current stock information
    $.ajax({
        url: 'get_product_stock.php',
        type: 'GET',
        data: { id: productId },
        success: function(response) {
            const product = JSON.parse(response);
            document.getElementById('product_id').value = product.id;
            document.getElementById('current_stock').value = product.stock;
            document.getElementById('stockUpdateModal').style.display = 'block';
        },
        error: function() {
            showNotification('Error', 'Failed to fetch product details', 'error');
        }
    });
}

function closeStockModal() {
    document.getElementById('stockUpdateModal').style.display = 'none';
    document.getElementById('stockUpdateForm').reset();
}

// Handle stock update form submission
document.getElementById('stockUpdateForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    $.ajax({
        url: 'update_product_stock.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            try {
                const result = JSON.parse(response);
                if(result.success) {
                    showNotification('Success', 'Stock updated successfully', 'success');
                    closeStockModal();
                    // Reload the page to reflect changes
                    location.reload();
                } else {
                    showNotification('Error', result.error || 'Failed to update stock', 'error');
                }
            } catch(e) {
                showNotification('Error', 'Unexpected error occurred', 'error');
            }
        },
        error: function() {
            showNotification('Error', 'Failed to update stock', 'error');
        }
    });
});

        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const section = urlParams.get('section');
            
            // Get stored section from sessionStorage or default to dashboard
            let storedSection = sessionStorage.getItem('currentSection');
            if (!storedSection && !sessionStorage.getItem('initialized')) {
                storedSection = 'dashboard-section';
                sessionStorage.setItem('initialized', 'true');
            }
            const currentSection = section || storedSection || 'dashboard';
            
            // Show the appropriate section and update header
            showSection(currentSection);
            
            // If no specific section is requested, update URL to dashboard
            if (!section && !storedSection) {
                const newUrl = window.location.pathname + '?section=dashboard';
                window.history.pushState({ section: 'dashboard' }, '', newUrl);
            }

            // Handle browser back/forward navigation
            window.addEventListener('popstate', function(event) {
                const params = new URLSearchParams(window.location.search);
                const section = params.get('section') || 'dashboard';
                showSection(section);
            });            // Add popstate event listener to handle browser back/forward
            window.addEventListener('popstate', function(event) {
                const urlParams = new URLSearchParams(window.location.search);
                const section = urlParams.get('section') || 'dashboard';
                showSection(section);
            });
        });

        function toggleDarkMode() {
            const settingsSection = document.getElementById('settings-section');
            const darkModeStatus = document.getElementById('dark-mode-status');
            const isDarkMode = settingsSection.classList.toggle('dark-mode');
            darkModeStatus.textContent = isDarkMode ? 'Dark mode is on' : 'Dark mode is off';
        }

        // Close edit form
        document.getElementById('close-edit').onclick = () => {
            document.querySelector('.edit-form-container').style.display = 'none';
        }
    </script>
     <script>
        // Redirect function
        function navigateTo(page) {
            window.location.href = page;
        }
    </script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="js/dashboard.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Initialize Revenue Chart
    document.addEventListener('DOMContentLoaded', function() {
        const revenueChart = document.getElementById('revenueChart');
        if (revenueChart) {
            // Get the current month and year
            const now = new Date();
            const currentMonth = now.getMonth();
            const currentYear = now.getFullYear();

            // Generate labels for the last 12 months
            // Get the real data from the canvas element
            const revenueData = JSON.parse(revenueChart.dataset.revenue);
            const monthLabels = JSON.parse(revenueChart.dataset.labels);

            new Chart(revenueChart, {
                type: 'line',
                data: {
                    labels: monthLabels,
                    datasets: [{
                        label: 'Revenue',
                        data: revenueData,
                        fill: true,
                        borderColor: '#FF7F50',
                        backgroundColor: 'rgba(255, 183, 94, 0.1)',
                        tension: 0.4,
                        borderWidth: 2,
                        pointBackgroundColor: '#FF7F50',
                        pointBorderColor: '#FF7F50',
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(255, 255, 255, 0.9)',
                            titleColor: '#333',
                            bodyColor: '#666',
                            borderColor: '#FF7F50',
                            borderWidth: 1,
                            padding: 10,
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return '₱' + context.raw.toLocaleString(undefined, {
                                        minimumFractionDigits: 2,
                                        maximumFractionDigits: 2
                                    });
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)',
                                drawBorder: false
                            },
                            border: {
                                display: false
                            },
                            ticks: {
                                padding: 10,
                                color: '#666',
                                font: {
                                    size: 11
                                },
                                callback: function(value) {
                                    return '₱' + value.toLocaleString();
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            border: {
                                display: false
                            },
                            ticks: {
                                padding: 10,
                                color: '#666',
                                font: {
                                    size: 11
                                }
                            }
                        }
                    },
                    elements: {
                        line: {
                            borderJoinStyle: 'round'
                        }
                    },
                    layout: {
                        padding: {
                            top: 20,
                            right: 20,
                            bottom: 20,
                            left: 20
                        }
                    }
                }
            });
        }

        // Add event listener for period change
        const chartPeriod = document.querySelector('.chart-period');
        if (chartPeriod) {
            chartPeriod.addEventListener('change', function(e) {
                const period = e.target.value;
                fetch(`get_revenue_data.php?period=${period}`)
                    .then(response => response.json())
                    .then(data => {
                        // Update the chart with new data
                        chart.data.labels = data.labels;
                        chart.data.datasets[0].data = data.revenue;
                        chart.update();
                    })
                    .catch(error => console.error('Error:', error));
            });
        }
    });
<script>
// Order search functionality
document.getElementById('orderSearch')?.addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    document.querySelectorAll('.orders-table tbody tr').forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});

// Status filter functionality
document.getElementById('statusFilter')?.addEventListener('change', function(e) {
    const status = e.target.value;
    document.querySelectorAll('.orders-table tbody tr').forEach(row => {
        if (status === 'all') {
            row.style.display = '';
        } else {
            const rowStatus = row.querySelector('.status-badge').textContent.toLowerCase();
            row.style.display = rowStatus === status ? '' : 'none';
        }
    });
});

// Complete order function
function completeOrder(orderId) {
    if (!confirm('Are you sure you want to complete this order?')) return;

    $.ajax({
        url: 'complet_orders.php',
        type: 'GET',
        data: { id: orderId },
        success: function(response) {
            try {
                const result = JSON.parse(response);
                if (result.success) {
                    showNotification('Success', 'Order completed successfully!', 'success');
                    // Update the status badge
                    const row = document.querySelector(`tr[data-order-id="${orderId}"]`);
                    const statusBadge = row.querySelector('.status-badge');
                    statusBadge.className = 'status-badge completed';
                    statusBadge.textContent = 'Completed';
                    // Remove the complete button
                    row.querySelector('.complete-btn')?.remove();
                } else {
                    showNotification('Error', result.error || 'Unable to complete the order.', 'error');
                }
            } catch (e) {
                showNotification('Error', 'Unexpected error occurred', 'error');
            }
        },
        error: function() {
            showNotification('Error', 'Failed to send request', 'error');
        }
    });
}

// View order details
function viewOrder(orderId) {
    const modal = document.getElementById('orderModal');
    const modalBody = modal.querySelector('.modal-body');
    
    // Show loading state
    modalBody.innerHTML = '<div class="loading">Loading...</div>';
    modal.style.display = 'block';

    // Fetch order details
    $.ajax({
        url: 'get_order_details.php',
        type: 'GET',
        data: { id: orderId },
        success: function(response) {
            try {
                const order = JSON.parse(response);
                modalBody.innerHTML = `
                    <div class="order-details">
                        <div class="order-header">
                            <h4>Order #${order.id}</h4>
                            <span class="status-badge ${order.status}">${order.status}</span>
                        </div>
                        <div class="customer-details">
                            <h5>Customer Information</h5>
                            <p><strong>Name:</strong> ${order.name}</p>
                            <p><strong>Address:</strong> ${order.address}</p>
                            <p><strong>Payment Method:</strong> ${order.method}</p>
                        </div>
                        <div class="order-items">
                            <h5>Order Summary</h5>
                            <p><strong>Total Items:</strong> ${order.total_products}</p>
                            <p><strong>Total Amount:</strong> ₱${order.total_price}</p>
                            <p><strong>Order Date:</strong> ${new Date(order.order_time).toLocaleString()}</p>
                        </div>
                    </div>
                `;
            } catch (e) {
                modalBody.innerHTML = '<div class="error">Error loading order details</div>';
            }
        },
        error: function() {
            modalBody.innerHTML = '<div class="error">Failed to load order details</div>';
        }
    });
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('orderModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
}

// Close modal with × button
document.querySelector('.close-modal')?.addEventListener('click', function() {
    document.getElementById('orderModal').style.display = 'none';
});
</script>

<script src="js/user-roles.js"></script>

<script>
function showNotification(title, message, type = 'info') {
    const container = document.getElementById('notificationContainer');
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    
    // Create notification content
    notification.innerHTML = `
        <i class="notification-icon fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
        <div class="notification-content">
            <div class="notification-title">${title}</div>
            <div class="notification-message">${message}</div>
        </div>
        <button class="notification-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
        <div class="notification-progress"></div>
    `;
    
    // Add to container
    container.appendChild(notification);
    
    // Remove after animation
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Function to handle AJAX responses
function handleAjaxResponse(response, action) {
    let title, message, type;
    
    if (response.success) {
        title = 'Success';
        message = `Item ${action} successfully`;
        type = 'success';
    } else {
        title = 'Error';
        message = response.error || `Failed to ${action} item`;
        type = 'error';
    }
    
    showNotification(title, message, type);
}

// Update message handling for form submissions
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        // Don't prevent form submission, but prepare for notification
        const action = this.querySelector('[name="add_product"]') ? 'created' : 
                      this.querySelector('[name="update_product"]') ? 'updated' : 'processed';
        
        // We'll show notification after the form submits and page reloads
        sessionStorage.setItem('pendingNotification', JSON.stringify({
            action: action,
            timestamp: Date.now()
        }));
    });
});

// Check for pending notifications on page load
window.addEventListener('load', function() {
    const pending = sessionStorage.getItem('pendingNotification');
    if (pending) {
        const {action, timestamp} = JSON.parse(pending);
        if (Date.now() - timestamp < 1000) { // Only show if recent
            showNotification('Success', `Item ${action} successfully`, 'success');
        }
        sessionStorage.removeItem('pendingNotification');
    }
});
</script>

        <script>
            function showOutOfStockModal() {
                // Check if modal has already been shown in this session
                if (sessionStorage.getItem('outOfStockModalShown')) {
                    return;
                }

                const outOfStockList = document.getElementById('outOfStockList');
                const tableRows = document.querySelectorAll('.inventory-table tbody tr');
                let outOfStockItems = [];
                
                // Don't show overlay or blur until we confirm there are out-of-stock items

                tableRows.forEach(row => {
                    // Clean up the stock text and handle potential NaN values
                    const stockText = row.querySelector('td:nth-child(4)').textContent.trim();
                    const stock = parseInt(stockText);
                    
                    // Check if stock is actually 0 (not NaN) and add to outOfStockItems
                    if (!isNaN(stock) && stock === 0) {
                        const productName = row.querySelector('td:nth-child(2)').textContent.trim();
                        const category = row.querySelector('td:nth-child(3)').textContent.trim();
                        outOfStockItems.push({ name: productName, category: category });
                    }
                });

                // Only show modal and effects if there are actually out of stock items
                if (outOfStockItems.length > 0) {
                    // Now show overlay and blur effects
                    document.getElementById('pageOverlay').style.display = 'block';
                    document.getElementById('mainContent').style.filter = 'blur(4px)';
                    const alert = document.getElementById('outOfStockAlert');
                    
                    let listHTML = '';
                    outOfStockItems.forEach(item => {
                        listHTML += `
                            <div style="padding: 8px 0; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #f0f0f0;">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span style="font-weight: 500;">${item.name}</span>
                                    <span style="color: #666; font-size: 0.9em;"> - ${item.category}</span>
                                </div>
                                <span style="color: #FF4444; font-size: 0.85em;">Out of Stock</span>
                            </div>`;
                    });
                    outOfStockList.innerHTML = listHTML;
                    alert.style.display = 'block';
                }
            }

            function closeOutOfStockModal() {
                const alert = document.getElementById('outOfStockAlert');
                const overlay = document.getElementById('pageOverlay');
                const mainContent = document.getElementById('mainContent');
                
                // Set flag in sessionStorage to prevent reshowing
                sessionStorage.setItem('outOfStockModalShown', 'true');
                
                alert.style.opacity = '0';
                overlay.style.opacity = '0';
                mainContent.style.filter = 'none';
                
                setTimeout(() => {
                    alert.style.display = 'none';
                    overlay.style.display = 'none';
                    alert.style.opacity = '1';
                    overlay.style.opacity = '1';
                }, 300);
            }

            document.addEventListener('DOMContentLoaded', function() {
                // Show the modal when page loads
                showOutOfStockModal();

                const categoryFilter = document.getElementById('categoryFilter');
                const statusFilter = document.getElementById('statusFilter');
                const tableRows = document.querySelectorAll('.inventory-table tbody tr');

                function filterTable() {
                    const selectedCategory = categoryFilter.value;
                    const selectedStatus = statusFilter.value;

                    tableRows.forEach(row => {
                        const category = row.querySelector('td:nth-child(3)').textContent.trim();
                        const stockCell = row.querySelector('td:nth-child(4)');
                        const statusCell = row.querySelector('td:nth-child(6) .status-badge');
                        const stock = parseInt(stockCell.textContent);
                        const status = statusCell.textContent.trim();

                        let showRow = true;

                        if (selectedCategory !== 'all' && category !== selectedCategory) {
                            showRow = false;
                        }

                        if (selectedStatus !== 'all') {
                            switch(selectedStatus) {
                                case 'out':
                                    if (status !== 'Out of Stock') showRow = false;
                                    break;
                                case 'low':
                                    if (stock > 10 || stock <= 0) showRow = false;
                                    break;
                                case 'critical':
                                    if (stock > 5 || stock <= 0) showRow = false;
                                    break;
                                case 'in':
                                    if (stock <= 0) showRow = false;
                                    break;
                            }
                        }

                        row.style.display = showRow ? '' : 'none';
                    });
                }

                categoryFilter.addEventListener('change', filterTable);
                statusFilter.addEventListener('change', filterTable);

                // Add search functionality
                const searchInput = document.getElementById('inventorySearch');
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();

                    tableRows.forEach(row => {
                        const productName = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                        const category = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                        const showRow = productName.includes(searchTerm) || category.includes(searchTerm);
                        
                        // Consider current filter selections
                        if (showRow) {
                            filterTable();
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            });

            function handleLogout(event) {
                event.preventDefault();
                window.location.href = '../logout.php';
            }

            function toggleNotifications() {
                const dropdown = document.getElementById('notificationDropdown');
                dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
                if (dropdown.style.display === 'block') {
                    updateNotifications();
                }
            }

            // Store read notifications
            let readNotifications = new Set();

            function markAllAsRead() {
                // Clear all notifications
                document.getElementById('notifCount').style.display = 'none';
                document.getElementById('notificationList').innerHTML = '<div style="padding: 15px; color: #666; text-align: center;">No new notifications</div>';
                document.getElementById('markAllBtn').style.display = 'none';
                
                // Make an AJAX call to store read status in session
                fetch('mark_notifications_read.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ action: 'mark_all_read' })
                });
                
                // Clear local notifications
                readNotifications = new Set();
                // Store in session storage to persist during page navigation
                sessionStorage.setItem('notificationsRead', 'true');
            }

            function updateNotifications() {
                // Check if notifications were marked as read
                if (sessionStorage.getItem('notificationsRead') === 'true') {
                    document.getElementById('notifCount').style.display = 'none';
                    document.getElementById('notificationList').innerHTML = '<div style="padding: 15px; color: #666; text-align: center;">No new notifications</div>';
                    document.getElementById('markAllBtn').style.display = 'none';
                    return;
                }

                const tableRows = document.querySelectorAll('.inventory-table tbody tr');
                let notifications = [];
                let count = 0;

                tableRows.forEach(row => {
                    const productName = row.querySelector('td:nth-child(2)').textContent.trim();
                    const category = row.querySelector('td:nth-child(3)').textContent.trim();
                    const stock = parseInt(row.querySelector('td:nth-child(4)').textContent);
                    
                    if (stock === 0) {
                        notifications.push({ name: productName, category: category, type: 'out', stock: stock });
                        count++;
                    } else if (stock <= 10 && stock > 0) {
                        notifications.push({ name: productName, category: category, type: 'low', stock: stock });
                        count++;
                    }
                });

                // Update notification count
                document.getElementById('notifCount').textContent = count;
                
                // Update notification list
                const notificationList = document.getElementById('notificationList');
                let listHTML = '';
                
                // Filter out read notifications
                notifications = notifications.filter(item => !readNotifications.has(item.name));
                
                // Update notification count badge
                const notifCount = document.getElementById('notifCount');
                if (notifications.length > 0) {
                    notifCount.textContent = notifications.length;
                    notifCount.style.display = 'flex';
                    document.getElementById('markAllBtn').style.display = 'block';
                } else {
                    notifCount.style.display = 'none';
                    document.getElementById('markAllBtn').style.display = 'none';
                }

                if (notifications.length === 0) {
                    listHTML = '<div style="padding: 15px; color: #666; text-align: center;">No new notifications</div>';
                } else {
                    notifications.forEach(item => {
                        const isOutOfStock = item.type === 'out';
                        const isRead = readNotifications.has(item.name);
                        const backgroundColor = isRead ? '#f8f9fa' : (isOutOfStock ? '#fff5f5' : '#fff8e6');
                        const textColor = isRead ? '#999' : (isOutOfStock ? '#dc3545' : '#ffa500');
                        const status = isOutOfStock ? 'Out of Stock' : 'Low Stock';
                        
                        listHTML += `
                            <div style="padding: 12px 15px; border-bottom: 1px solid #f0f0f0; background: ${backgroundColor};">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <div style="font-weight: 500; color: #333;">${item.name}</div>
                                        <div style="font-size: 0.85em; color: #666;">${item.category}</div>
                                    </div>
                                    <div style="color: ${textColor}; font-size: 0.85em; font-weight: 500;">${status}</div>
                                </div>
                            </div>`;
                    });
                }
                
                notificationList.innerHTML = listHTML;
            }

            // Initial update of notifications
            document.addEventListener('DOMContentLoaded', function() {
                // Check if notifications were marked as read
                if (sessionStorage.getItem('notificationsRead') === 'true') {
                    document.getElementById('notifCount').style.display = 'none';
                    document.getElementById('notificationList').innerHTML = '<div style="padding: 15px; color: #666; text-align: center;">No new notifications</div>';
                    document.getElementById('markAllBtn').style.display = 'none';
                } else {
                    updateNotifications();
                }
            });

            // Theme Management System
            const ThemeManager = {
                STORAGE_KEY: 'admin-theme-preference',
                DARK_THEME: 'dark',
                LIGHT_THEME: 'light',

                init() {
                    this.htmlElement = document.documentElement;
                    this.themeIcon = document.getElementById('themeIcon');
                    this.setupSystemPreferenceListener();
                    this.loadAndApplyTheme();
                },

                setupSystemPreferenceListener() {
                    window.matchMedia('(prefers-color-scheme: dark)').addListener(e => {
                        if (!localStorage.getItem(this.STORAGE_KEY)) {
                            this.setTheme(e.matches ? this.DARK_THEME : this.LIGHT_THEME, true);
                        }
                    });
                },

                loadAndApplyTheme() {
                    const savedTheme = localStorage.getItem(this.STORAGE_KEY);
                    const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                    const themeToApply = savedTheme || (systemPrefersDark ? this.DARK_THEME : this.LIGHT_THEME);
                    
                    this.setTheme(themeToApply, true);
                },

                setTheme(theme, isInitial = false) {
                    const isDark = theme === this.DARK_THEME;
                    
                    // Update DOM
                    if (isDark) {
                        this.htmlElement.setAttribute('data-theme', 'dark');
                        this.themeIcon.className = 'fas fa-sun';
                        document.body.style.backgroundColor = 'var(--bg-primary)';
                    } else {
                        this.htmlElement.removeAttribute('data-theme');
                        this.themeIcon.className = 'fas fa-moon';
                        document.body.style.backgroundColor = '#ffffff';
                    }

                    // Save preference
                    localStorage.setItem(this.STORAGE_KEY, theme);

                    // Update charts if they exist
                    if (window.revenueChart) {
                        window.revenueChart.update();
                    }

                    // Show notification unless it's the initial load
                    if (!isInitial) {
                        showNotification(
                            'Theme Updated',
                            `Switched to ${isDark ? 'dark' : 'light'} mode`,
                            'success'
                        );
                    }

                    // Dispatch theme change event
                    const event = new CustomEvent('themechange', { 
                        detail: { theme, isInitial } 
                    });
                    document.dispatchEvent(event);

                    // Update meta theme color for mobile browsers
                    const metaThemeColor = document.querySelector('meta[name="theme-color"]');
                    if (metaThemeColor) {
                        metaThemeColor.setAttribute('content', isDark ? '#1a1a1a' : '#ffffff');
                    }
                },

                toggle() {
                    const currentTheme = this.htmlElement.getAttribute('data-theme');
                    this.setTheme(currentTheme === this.DARK_THEME ? this.LIGHT_THEME : this.DARK_THEME);
                }
            };

            // Initialize theme management
            document.addEventListener('DOMContentLoaded', () => {
                ThemeManager.init();
                
                // Configure Chart.js defaults for dark mode
                const updateChartTheme = (isDark) => {
                    Chart.defaults.color = isDark ? 'rgba(255, 255, 255, 0.7)' : '#666666';
                    Chart.defaults.borderColor = isDark ? 'rgba(255, 255, 255, 0.1)' : '#e5e7eb';
                    Chart.defaults.backgroundColor = isDark ? 'rgba(255, 127, 80, 0.1)' : 'rgba(255, 183, 94, 0.1)';
                    
                    // Update existing charts
                    Chart.instances.forEach(chart => {
                        chart.options.scales.x.grid.color = isDark ? 'rgba(255, 255, 255, 0.1)' : '#e5e7eb';
                        chart.options.scales.y.grid.color = isDark ? 'rgba(255, 255, 255, 0.1)' : '#e5e7eb';
                        chart.options.scales.x.ticks.color = isDark ? 'rgba(255, 255, 255, 0.7)' : '#666666';
                        chart.options.scales.y.ticks.color = isDark ? 'rgba(255, 255, 255, 0.7)' : '#666666';
                        chart.update();
                    });
                };

                // Listen for theme changes
                document.addEventListener('themechange', (e) => {
                    updateChartTheme(e.detail.theme === 'dark');
                });

                // Initial chart theme setup
                updateChartTheme(document.documentElement.getAttribute('data-theme') === 'dark');
            });

            // Theme toggle function
            function toggleDarkMode() {
                ThemeManager.toggle();
            }

            // Initialize theme from localStorage
            document.addEventListener('DOMContentLoaded', function() {
                // Get saved theme or system preference
                const savedTheme = localStorage.getItem('theme') || 
                                 (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                const themeIcon = document.getElementById('themeIcon');
                
                if (savedTheme === 'dark') {
                    document.documentElement.setAttribute('data-theme', 'dark');
                    themeIcon.className = 'fas fa-sun';
                    document.body.style.backgroundColor = 'var(--background-primary)';
                }

                // Listen for system theme changes
                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
                    if (!localStorage.getItem('theme')) { // Only if user hasn't manually set theme
                        const newTheme = e.matches ? 'dark' : 'light';
                        document.documentElement.setAttribute('data-theme', newTheme);
                        themeIcon.className = `fas fa-${newTheme === 'dark' ? 'sun' : 'moon'}`;
                        document.body.style.backgroundColor = newTheme === 'dark' ? 'var(--background-primary)' : '#ffffff';
                    }
                });

                // Add smooth transitions for theme changes
                const styleSheet = document.createElement('style');
                styleSheet.textContent = `
                    * {
                        transition: background-color 0.3s ease, 
                                  color 0.3s ease, 
                                  border-color 0.3s ease, 
                                  box-shadow 0.3s ease !important;
                    }
                `;
                document.head.appendChild(styleSheet);
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(event) {
                const dropdown = document.getElementById('notificationDropdown');
                const notificationIcon = document.querySelector('.notification-icon');
                
                if (!notificationIcon.contains(event.target) && !dropdown.contains(event.target)) {
                    dropdown.style.display = 'none';
                }
            });
        </script>

        <!-- Landing Settings JavaScript -->
        <script>
            // Image upload preview functionality
            function setupImagePreview(inputId, previewId) {
                const input = document.getElementById(inputId);
                const preview = document.getElementById(previewId);

                if (input && preview) {
                    input.addEventListener('change', function(e) {
                        const file = e.target.files[0];
                        if (file) {
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                preview.src = e.target.result;
                            };
                            reader.readAsDataURL(file);
                        }
                    });
                }
            }

            // Initialize image previews
            document.addEventListener('DOMContentLoaded', function() {
                setupImagePreview('logoUpload', 'logoPreview');
                setupImagePreview('faviconUpload', 'faviconPreview');
                setupImagePreview('heroUpload', 'heroPreview');
            });

            // Feature management
            function addFeature() {
                const featuresList = document.getElementById('featuresList');
                const featureCount = featuresList.children.length;

                const featureItem = document.createElement('div');
                featureItem.className = 'feature-item';
                featureItem.innerHTML = `
                    <input type="text" class="settings-input" placeholder="Feature ${featureCount + 1}">
                    <button type="button" class="remove-feature" onclick="removeFeature(this)" style="background: none; border: none; color: #FF7F50; cursor: pointer;">
                        <i class="fas fa-times"></i>
                    </button>
                `;

                featuresList.appendChild(featureItem);
            }

            function removeFeature(button) {
                button.closest('.feature-item').remove();
            }

            // Save settings
            function saveLandingSettings() {
                const formData = new FormData();
                
                // Show loading notification
                showNotification('Info', 'Saving settings...', 'info');

                // Add file uploads
                const logoFile = document.getElementById('logoUpload').files[0];
                const faviconFile = document.getElementById('faviconUpload').files[0];
                const heroFile = document.getElementById('heroUpload').files[0];

                if (logoFile) formData.append('logo', logoFile);
                if (faviconFile) formData.append('favicon', faviconFile);
                if (heroFile) formData.append('hero_image', heroFile);

                // Add text inputs
                const settings = {
                    branding: {
                        restaurantName: document.getElementById('restaurantName').value,
                        tagline: document.getElementById('tagline').value
                    },
                    hero: {
                        title: document.getElementById('heroTitle').value,
                        subtitle: document.getElementById('heroSubtitle').value
                    },
                    about: {
                        story: document.getElementById('aboutUs').value,
                        features: Array.from(document.querySelectorAll('#featuresList input')).map(input => input.value)
                    },
                    contact: {
                        address: document.getElementById('address').value,
                        phone: document.getElementById('phone').value,
                        email: document.getElementById('email').value,
                        hours: document.getElementById('hours').value,
                        social: {
                            facebook: document.getElementById('facebook').value,
                            instagram: document.getElementById('instagram').value,
                            tiktok: document.getElementById('tiktok').value
                        }
                    },
                    theme: {
                        primaryColor: document.getElementById('primaryColor').value,
                        secondaryColor: document.getElementById('secondaryColor').value,
                        fontStyle: document.getElementById('fontStyle').value,
                        layoutStyle: document.getElementById('layoutStyle').value,
                        newsletter: document.getElementById('newsletterToggle').checked
                    }
                };

                formData.append('settings', JSON.stringify(settings));

                // Send settings to server
                fetch('save_landing_settings.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        showNotification('Success', 'Landing page settings saved successfully', 'success');
                    } else {
                        throw new Error(data.message || 'Failed to save settings');
                    }
                })
                .catch(error => {
                    showNotification('Error', error.message || 'Failed to save settings', 'error');
                    console.error('Error:', error);
                });
                
                // Log the data being sent for debugging
                console.log('Settings being saved:', JSON.parse(formData.get('settings')));
            }

            // Load settings
            function loadLandingSettings() {
                fetch('get_landing_settings.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const settings = data.settings;
                            
                            // Update text inputs
                            document.getElementById('restaurantName').value = settings.branding?.restaurantName || '';
                            document.getElementById('tagline').value = settings.branding?.tagline || '';
                            document.getElementById('heroTitle').value = settings.hero?.title || '';
                            document.getElementById('heroSubtitle').value = settings.hero?.subtitle || '';
                            document.getElementById('aboutUs').value = settings.about?.story || '';
                            document.getElementById('address').value = settings.contact?.address || '';
                            document.getElementById('phone').value = settings.contact?.phone || '';
                            document.getElementById('email').value = settings.contact?.email || '';
                            document.getElementById('hours').value = settings.contact?.hours || '';
                            
                            // Update social media links
                            document.getElementById('facebook').value = settings.contact?.social?.facebook || '';
                            document.getElementById('instagram').value = settings.contact?.social?.instagram || '';
                            document.getElementById('tiktok').value = settings.contact?.social?.tiktok || '';
                            
                            // Update theme settings
                            document.getElementById('primaryColor').value = settings.theme?.primaryColor || '#FF7F50';
                            document.getElementById('secondaryColor').value = settings.theme?.secondaryColor || '#FFB75E';
                            document.getElementById('fontStyle').value = settings.theme?.fontStyle || 'Poppins';
                            document.getElementById('layoutStyle').value = settings.theme?.layoutStyle || 'centered';
                            document.getElementById('newsletterToggle').checked = settings.theme?.newsletter || false;
                            
                            // Update features
                            const featuresList = document.getElementById('featuresList');
                            featuresList.innerHTML = '';
                            settings.about?.features?.forEach(feature => {
                                const featureItem = document.createElement('div');
                                featureItem.className = 'feature-item';
                                featureItem.innerHTML = `
                                    <input type="text" class="settings-input" value="${feature}">
                                    <button type="button" class="remove-feature" onclick="removeFeature(this)" style="background: none; border: none; color: #FF7F50; cursor: pointer;">
                                        <i class="fas fa-times"></i>
                                    </button>
                                `;
                                featuresList.appendChild(featureItem);
                            });
                            
                            // Update image previews if URLs are provided
                            if (settings.branding?.logoUrl) {
                                document.getElementById('logoPreview').src = settings.branding.logoUrl;
                            }
                            if (settings.branding?.faviconUrl) {
                                document.getElementById('faviconPreview').src = settings.branding.faviconUrl;
                            }
                            if (settings.hero?.imageUrl) {
                                document.getElementById('heroPreview').src = settings.hero.imageUrl;
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error loading settings:', error);
                        showNotification('Error', 'Failed to load settings', 'error');
                    });
            }

            // Load settings when the landing section is shown
            document.addEventListener('DOMContentLoaded', function() {
                const landingSection = document.getElementById('landing-section');
                if (landingSection) {
                    const observer = new MutationObserver(function(mutations) {
                        mutations.forEach(function(mutation) {
                            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                                if (!landingSection.classList.contains('hidden')) {
                                    loadLandingSettings();
                                }
                            }
                        });
                    });

                    observer.observe(landingSection, { attributes: true });
                }
            });
        </script>
    </body>
</html>
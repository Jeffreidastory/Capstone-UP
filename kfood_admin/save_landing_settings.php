<?php
session_start();
include "../connect.php";

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in and has appropriate permissions
if (!isset($_SESSION['role_id']) || ($_SESSION['role_id'] != 1 && $_SESSION['role_id'] != 2)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

try {
    // Check if settings data is present
    if (!isset($_POST['settings'])) {
        throw new Exception('No settings data received');
    }

    // Get and validate settings data
    $settings = json_decode($_POST['settings'], true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data: ' . json_last_error_msg());
    }
    
    // Handle file uploads
    $uploadDir = '../uploaded_img/';
    $uploadedFiles = [];
    
    // Function to handle file upload
    function handleFileUpload($file, $type) {
        global $uploadDir;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $fileName = uniqid() . '_' . basename($file['name']);
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return $fileName;
        }
        return null;
    }

    // Handle file uploads if present
    if (isset($_FILES['logo'])) {
        $uploadedFiles['logo'] = handleFileUpload($_FILES['logo'], 'logo');
    }
    if (isset($_FILES['favicon'])) {
        $uploadedFiles['favicon'] = handleFileUpload($_FILES['favicon'], 'favicon');
    }
    if (isset($_FILES['hero_image'])) {
        $uploadedFiles['hero_image'] = handleFileUpload($_FILES['hero_image'], 'hero');
    }

    // Start transaction
    $conn->begin_transaction();

    // Update or insert settings
    $query = "INSERT INTO landing_settings (
        restaurant_name, 
        tagline, 
        logo_path, 
        favicon_path,
        hero_title,
        hero_subtitle,
        hero_image_path,
        about_story,
        features,
        address,
        phone,
        email,
        operating_hours,
        social_facebook,
        social_instagram,
        social_tiktok,
        primary_color,
        secondary_color,
        font_style,
        layout_style,
        newsletter_enabled,
        updated_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ON DUPLICATE KEY UPDATE 
        restaurant_name = VALUES(restaurant_name),
        tagline = VALUES(tagline),
        logo_path = COALESCE(NULLIF(VALUES(logo_path), ''), logo_path),
        favicon_path = COALESCE(NULLIF(VALUES(favicon_path), ''), favicon_path),
        hero_title = VALUES(hero_title),
        hero_subtitle = VALUES(hero_subtitle),
        hero_image_path = COALESCE(NULLIF(VALUES(hero_image_path), ''), hero_image_path),
        about_story = VALUES(about_story),
        features = VALUES(features),
        address = VALUES(address),
        phone = VALUES(phone),
        email = VALUES(email),
        operating_hours = VALUES(operating_hours),
        social_facebook = VALUES(social_facebook),
        social_instagram = VALUES(social_instagram),
        social_tiktok = VALUES(social_tiktok),
        primary_color = VALUES(primary_color),
        secondary_color = VALUES(secondary_color),
        font_style = VALUES(font_style),
        layout_style = VALUES(layout_style),
        newsletter_enabled = VALUES(newsletter_enabled),
        updated_at = NOW()";

    $stmt = $conn->prepare($query);

    // Prepare values for binding
    $features = json_encode($settings['about']['features']);
    $newsletterEnabled = $settings['theme']['newsletter'] ? 1 : 0;

    $stmt->bind_param(
        "ssssssssssssssssssssb",
        $settings['branding']['restaurantName'],
        $settings['branding']['tagline'],
        $uploadedFiles['logo'] ?? '',
        $uploadedFiles['favicon'] ?? '',
        $settings['hero']['title'],
        $settings['hero']['subtitle'],
        $uploadedFiles['hero_image'] ?? '',
        $settings['about']['story'],
        $features,
        $settings['contact']['address'],
        $settings['contact']['phone'],
        $settings['contact']['email'],
        $settings['contact']['hours'],
        $settings['contact']['social']['facebook'],
        $settings['contact']['social']['instagram'],
        $settings['contact']['social']['tiktok'],
        $settings['theme']['primaryColor'],
        $settings['theme']['secondaryColor'],
        $settings['theme']['fontStyle'],
        $settings['theme']['layoutStyle'],
        $newsletterEnabled
    );

    // Execute the statement
    $stmt->execute();

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Settings saved successfully'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    if ($conn && !$conn->connect_error) {
        $conn->rollback();
    }
    
    // Log error to custom log file
    $error_message = date('Y-m-d H:i:s') . " Error saving landing settings: " . $e->getMessage() . "\n";
    $error_message .= "Stack trace: " . $e->getTraceAsString() . "\n";
    file_put_contents(__DIR__ . '/landing_settings_error.log', $error_message, FILE_APPEND);
    
    echo json_encode([
        'success' => false,
        'message' => 'Error saving settings: ' . $e->getMessage(),
        'debug_info' => [
            'error_type' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}

$conn->close();
?>
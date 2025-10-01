<?php
session_start();
include "../connect.php";

// Check if user is logged in and has appropriate permissions
if (!isset($_SESSION['role_id']) || ($_SESSION['role_id'] != 1 && $_SESSION['role_id'] != 2)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

try {
    // Get settings from database
    $query = "SELECT * FROM landing_settings LIMIT 1";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Format data for response
        $settings = [
            'branding' => [
                'restaurantName' => $row['restaurant_name'],
                'tagline' => $row['tagline'],
                'logoUrl' => $row['logo_path'] ? '../uploaded_img/' . $row['logo_path'] : null,
                'faviconUrl' => $row['favicon_path'] ? '../uploaded_img/' . $row['favicon_path'] : null
            ],
            'hero' => [
                'title' => $row['hero_title'],
                'subtitle' => $row['hero_subtitle'],
                'imageUrl' => $row['hero_image_path'] ? '../uploaded_img/' . $row['hero_image_path'] : null
            ],
            'about' => [
                'story' => $row['about_story'],
                'features' => json_decode($row['features'], true)
            ],
            'contact' => [
                'address' => $row['address'],
                'phone' => $row['phone'],
                'email' => $row['email'],
                'hours' => $row['operating_hours'],
                'social' => [
                    'facebook' => $row['social_facebook'],
                    'instagram' => $row['social_instagram'],
                    'tiktok' => $row['social_tiktok']
                ]
            ],
            'theme' => [
                'primaryColor' => $row['primary_color'],
                'secondaryColor' => $row['secondary_color'],
                'fontStyle' => $row['font_style'],
                'layoutStyle' => $row['layout_style'],
                'newsletter' => (bool)$row['newsletter_enabled']
            ]
        ];

        echo json_encode([
            'success' => true,
            'settings' => $settings
        ]);
    } else {
        // Return default settings if none exist
        echo json_encode([
            'success' => true,
            'settings' => [
                'branding' => [
                    'restaurantName' => 'K-Food Delight',
                    'tagline' => '',
                    'logoUrl' => '../images/logo.png',
                    'faviconUrl' => '../images/logo.png'
                ],
                'hero' => [
                    'title' => 'K-FOOD DELIGHTS',
                    'subtitle' => 'Experience authentic Korean cuisine',
                    'imageUrl' => '../images/lasagna.jpg'
                ],
                'about' => [
                    'story' => '',
                    'features' => []
                ],
                'contact' => [
                    'address' => '',
                    'phone' => '',
                    'email' => '',
                    'hours' => '',
                    'social' => [
                        'facebook' => '',
                        'instagram' => '',
                        'tiktok' => ''
                    ]
                ],
                'theme' => [
                    'primaryColor' => '#FF7F50',
                    'secondaryColor' => '#FFB75E',
                    'fontStyle' => 'Poppins',
                    'layoutStyle' => 'centered',
                    'newsletter' => false
                ]
            ]
        ]);
    }

} catch (Exception $e) {
    error_log("Error getting landing settings: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error loading settings: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
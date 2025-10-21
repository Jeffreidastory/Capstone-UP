<?php
session_start();
include "../connect.php";

// Check if user is logged in and has appropriate permissions
if (!isset($_SESSION['role_id']) || ($_SESSION['role_id'] != 1 && $_SESSION['role_id'] != 2)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['categoryName'])) {
    $categoryName = trim($_POST['categoryName']);
    
    // Validate category name
    if (empty($categoryName)) {
        echo json_encode(['success' => false, 'message' => 'Category name is required']);
        exit();
    }
    
    // Basic validation for category name
    if (strlen($categoryName) > 50) {
        echo json_encode(['success' => false, 'message' => 'Category name is too long. Maximum 50 characters allowed.']);
        exit();
    }

    // Check if category already exists
    $check_query = "SELECT id FROM product_categories WHERE category_name = ?";
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "s", $categoryName);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        echo json_encode(['success' => false, 'message' => 'Category already exists']);
        exit();
    }

    // Insert new category
    $insert_query = "INSERT INTO product_categories (category_name) VALUES (?)";
    $stmt = mysqli_prepare($conn, $insert_query);
    mysqli_stmt_bind_param($stmt, "s", $categoryName);
    
    if (mysqli_stmt_execute($stmt)) {
        $categoryId = mysqli_insert_id($conn);
        echo json_encode([
            'success' => true,
            'message' => 'Category added successfully',
            'categoryId' => $categoryId
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to add category: ' . mysqli_error($conn)
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>

<?php
session_start();
include "../connect.php";

// Check if user is logged in and has admin privileges
if (!isset($_SESSION['role_id']) || ($_SESSION['role_id'] != 1 && $_SESSION['role_id'] != 2)) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$status = isset($_GET['status']) ? $_GET['status'] : 'all';

$query = "SELECT u.id as user_id, u.FirstName as first_name, u.LastName as last_name, 
                 u.username, u.verification_status, u.profile_picture,
                 u.verification_date, u.verification_notes, u.id_document 
          FROM users u 
          WHERE u.verification_status = 'pending' 
          AND u.id_document IS NOT NULL 
          AND u.id_document != ''";

if ($status !== 'all') {
    $query .= " AND verification_status = ?";
}

$query .= " ORDER BY FIELD(verification_status, 'pending', 'rejected', 'approved'), verification_date DESC";

$stmt = $conn->prepare($query);
if ($status !== 'all') {
    $stmt->bind_param("s", $status);
}

$stmt->execute();
$result = $stmt->get_result();
$requests = [];

while ($row = $result->fetch_assoc()) {
    // Sanitize the data
    $requests[] = array(
        'user_id' => (int)$row['user_id'],
        'first_name' => htmlspecialchars($row['first_name']),
        'last_name' => htmlspecialchars($row['last_name']),
        'username' => htmlspecialchars($row['username']),
        'verification_status' => htmlspecialchars($row['verification_status']),
        'verification_date' => $row['verification_date'],
        'verification_notes' => htmlspecialchars($row['verification_notes']),
        'id_document' => htmlspecialchars($row['id_document']),
        'profile_picture' => htmlspecialchars($row['profile_picture'])
    );
}

echo json_encode($requests);
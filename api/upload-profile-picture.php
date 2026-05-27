<?php
// api/upload-profile-picture.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!isset($_FILES['profile_picture'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No file provided']);
    exit;
}

$user_id = $_SESSION['user_id'];
$file = $_FILES['profile_picture'];

try {
    // Validate file
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowed_types)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid file type']);
        exit;
    }

    if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'File too large']);
        exit;
    }

    // Create uploads directory if it doesn't exist
    $upload_dir = 'uploads/profiles/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'profile_' . $user_id . '_' . time() . '.' . $extension;
    $filepath = $upload_dir . $filename;

    // Move file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
        exit;
    }

    // Update profile with new picture URL
    $picture_url = '/' . $filepath;

    $checkStmt = $pdo->prepare("SELECT id FROM user_profiles WHERE user_id = ?");
    $checkStmt->execute([$user_id]);
    $exists = $checkStmt->fetch();

    if ($exists) {
        $stmt = $pdo->prepare("UPDATE user_profiles SET profile_picture_url = ? WHERE user_id = ?");
    } else {
        $stmt = $pdo->prepare("INSERT INTO user_profiles (user_id, profile_picture_url) VALUES (?, ?)");
    }

    $stmt->execute($exists ? [$picture_url, $user_id] : [$user_id, $picture_url]);

    echo json_encode([
        'success' => true,
        'message' => 'Profile picture uploaded successfully',
        'url' => $picture_url
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

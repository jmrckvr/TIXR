<?php
// api/update-user-profile.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

try {
    // Check if user profile exists
    $checkStmt = $pdo->prepare("SELECT id FROM user_profiles WHERE user_id = ?");
    $checkStmt->execute([$user_id]);
    $existsProfile = $checkStmt->fetch();

    if ($existsProfile) {
        // Update existing profile
        $stmt = $pdo->prepare("
            UPDATE user_profiles SET
                phone_number = ?,
                address = ?,
                city = ?,
                province = ?,
                postal_code = ?,
                country = ?,
                profile_picture_url = COALESCE(?, profile_picture_url),
                bio = ?,
                date_of_birth = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE user_id = ?
        ");
        $stmt->execute([
            $data['phone_number'] ?? null,
            $data['address'] ?? null,
            $data['city'] ?? null,
            $data['province'] ?? null,
            $data['postal_code'] ?? null,
            $data['country'] ?? null,
            $data['profile_picture_url'] ?? null,
            $data['bio'] ?? null,
            $data['date_of_birth'] ?? null,
            $user_id
        ]);
    } else {
        // Create new profile
        $stmt = $pdo->prepare("
            INSERT INTO user_profiles (
                user_id, phone_number, address, city, province, 
                postal_code, country, profile_picture_url, bio, date_of_birth
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $user_id,
            $data['phone_number'] ?? null,
            $data['address'] ?? null,
            $data['city'] ?? null,
            $data['province'] ?? null,
            $data['postal_code'] ?? null,
            $data['country'] ?? null,
            $data['profile_picture_url'] ?? null,
            $data['bio'] ?? null,
            $data['date_of_birth'] ?? null
        ]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

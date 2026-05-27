<?php
// Handle preflight OPTIONS request
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
// Accept localhost on any port
if (strpos($origin, 'http://localhost') === 0 || strpos($origin, 'http://127.0.0.1') === 0) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
}
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header("Content-Type: application/json");

session_start();
require_once "db.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Get request data
$data = json_decode(file_get_contents("php://input"), true);
$username = $data["username"] ?? "";
$email = $data["email"] ?? "";

// Validate input
if (!$username || !$email) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

try {
    // Check if new email is already taken by another user
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id != :user_id LIMIT 1");
    $checkStmt->execute(['email' => $email, 'user_id' => $user_id]);

    if ($checkStmt->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Email is already in use']);
        exit;
    }

    // Update user profile
    $stmt = $pdo->prepare("UPDATE users SET username = :username, email = :email WHERE id = :user_id");

    $result = $stmt->execute([
        'username' => $username,
        'email' => $email,
        'user_id' => $user_id
    ]);

    if ($result) {
        // Update session data
        $_SESSION['username'] = $username;
        $_SESSION['email'] = $email;

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Profile updated successfully',
            'username' => $username,
            'email' => $email
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

<?php
// Handle preflight OPTIONS request
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
// Accept localhost on any port
if (strpos($origin, 'http://localhost') === 0 || strpos($origin, 'http://127.0.0.1') === 0) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
}
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, OPTIONS");

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

try {
    // Fetch all bookings for the logged-in user
    $stmt = $pdo->prepare("
        SELECT 
            id,
            property_id,
            check_in_date,
            check_out_date,
            total_price,
            status,
            created_at
        FROM bookings 
        WHERE user_id = :user_id
        ORDER BY created_at DESC
    ");

    $stmt->execute(['user_id' => $user_id]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'bookings' => $bookings
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

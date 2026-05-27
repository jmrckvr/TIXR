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

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied. Admin only.']);
    exit;
}

try {
    // Fetch all bookings with customer information
    $stmt = $pdo->prepare("
        SELECT 
            b.id,
            b.user_id,
            b.property_id,
            b.check_in_date,
            b.check_out_date,
            b.total_price,
            b.number_of_guests,
            b.guest_first_name,
            b.guest_last_name,
            b.guest_email,
            b.guest_phone,
            b.special_requests,
            b.status,
            b.created_at,
            u.username as guest,
            u.email as email
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        ORDER BY b.created_at DESC
    ");

    $stmt->execute();
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'bookings' => $bookings,
        'total' => count($bookings)
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

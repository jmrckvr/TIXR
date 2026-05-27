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
    // Get total users count
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_users FROM users WHERE role = 'customer'");
    $stmt->execute();
    $userCount = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get all user details with their booking information
    $stmt = $pdo->prepare("
        SELECT 
            u.id,
            u.username,
            u.email,
            u.created_at,
            u.is_active,
            COUNT(b.id) as total_bookings,
            SUM(CASE WHEN b.status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_bookings,
            SUM(b.total_price) as total_spent,
            MAX(b.created_at) as last_booking_date
        FROM users u
        LEFT JOIN bookings b ON u.id = b.user_id
        WHERE u.role = 'customer'
        GROUP BY u.id, u.username, u.email, u.created_at, u.is_active
        ORDER BY u.created_at DESC
    ");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get booking statistics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_bookings,
            COUNT(CASE WHEN status = 'confirmed' THEN 1 END) as confirmed_bookings,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_bookings,
            COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_bookings,
            SUM(total_price) as total_revenue
        FROM bookings
    ");
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get active stays (check-in date <= today AND check-out date >= today)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as active_stays
        FROM bookings
        WHERE status = 'confirmed'
        AND check_in_date <= CURDATE()
        AND check_out_date >= CURDATE()
    ");
    $stmt->execute();
    $activeStays = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get average rating
    $stmt = $pdo->prepare("
        SELECT AVG(rating) as avg_rating
        FROM properties
    ");
    $stmt->execute();
    $avgRating = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'totalUsers' => (int)$userCount['total_users'],
        'users' => $users,
        'stats' => $stats,
        'activeStays' => (int)($activeStays['active_stays'] ?? 0),
        'avgRating' => (float)($avgRating['avg_rating'] ?? 0),
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching user statistics: ' . $e->getMessage()
    ]);
}

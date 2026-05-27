<?php
// api/get-booking-timeline.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_GET['booking_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Booking ID required']);
    exit;
}

$booking_id = $_GET['booking_id'];

try {
    // Get booking first to check access
    $bookingStmt = $pdo->prepare("SELECT user_id FROM bookings WHERE id = ?");
    $bookingStmt->execute([$booking_id]);
    $booking = $bookingStmt->fetch();

    if (!$booking) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
        exit;
    }

    // Check if user has access (is owner or admin)
    if (isset($_SESSION['user_id'])) {
        if ($_SESSION['user_id'] !== $booking['user_id'] && $_SESSION['user_role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Forbidden']);
            exit;
        }
    }

    // Get timeline
    $stmt = $pdo->prepare("
        SELECT 
            bst.id,
            bst.booking_id,
            bst.status,
            bst.reason,
            bst.notes,
            bst.changed_by,
            u.username as changed_by_name,
            bst.created_at
        FROM booking_status_timeline bst
        LEFT JOIN users u ON bst.changed_by = u.id
        WHERE bst.booking_id = ?
        ORDER BY bst.created_at ASC
    ");
    $stmt->execute([$booking_id]);
    $timeline = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'timeline' => $timeline
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

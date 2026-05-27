<?php
// api/admin/update-booking-status.php
session_start();
require_once '../db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['booking_id']) || !isset($data['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$valid_statuses = ['pending', 'confirmed', 'active', 'completed', 'cancelled', 'refunded'];
if (!in_array($data['status'], $valid_statuses)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

try {
    // Update booking status
    $stmt = $pdo->prepare("
        UPDATE bookings SET
            status = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    $stmt->execute([$data['status'], $data['booking_id']]);

    // Add to timeline
    $timelineStmt = $pdo->prepare("
        INSERT INTO booking_status_timeline (
            booking_id, status, reason, notes, changed_by
        ) VALUES (?, ?, ?, ?, ?)
    ");
    $timelineStmt->execute([
        $data['booking_id'],
        $data['status'],
        $data['reason'] ?? null,
        $data['notes'] ?? null,
        $_SESSION['user_id']
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Booking status updated successfully'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

<?php
// api/cancel-booking.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['booking_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Booking ID required']);
    exit;
}

$booking_id = $data['booking_id'];
$user_id = $_SESSION['user_id'];
$cancellation_reason = $data['reason'] ?? 'User requested cancellation';

try {
    // Get booking
    $bookingStmt = $pdo->prepare("
        SELECT b.id, b.user_id, b.total_price, b.status, b.property_id, b.check_in_date
        FROM bookings b
        WHERE b.id = ?
    ");
    $bookingStmt->execute([$booking_id]);
    $booking = $bookingStmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
        exit;
    }

    // Check if user can cancel (is owner or admin)
    if ($booking['user_id'] !== $user_id && $_SESSION['user_role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden']);
        exit;
    }

    // Check if booking can be cancelled
    if ($booking['status'] === 'cancelled') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Booking already cancelled']);
        exit;
    }

    // Get cancellation policy
    $policyStmt = $pdo->prepare("
        SELECT 
            id, policy_type, full_refund_days_before,
            partial_refund_days_before, partial_refund_percentage
        FROM cancellation_policies
        WHERE property_id = ?
    ");
    $policyStmt->execute([$booking['property_id']]);
    $policy = $policyStmt->fetch(PDO::FETCH_ASSOC);

    // Calculate refund
    $refundInfo = calculateBookingRefund($booking, $policy);

    // Start transaction
    $pdo->beginTransaction();

    try {
        // Update booking status
        $updateStmt = $pdo->prepare("
            UPDATE bookings SET
                status = 'cancelled',
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $updateStmt->execute([$booking_id]);

        // Create cancellation record
        $cancelStmt = $pdo->prepare("
            INSERT INTO booking_cancellations (
                booking_id, cancellation_policy_id, cancelled_by,
                cancellation_reason, requested_amount, refund_amount, refund_percentage
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $cancelStmt->execute([
            $booking_id,
            $policy['id'] ?? null,
            $user_id,
            $cancellation_reason,
            $booking['total_price'],
            $refundInfo['refund_amount'],
            $refundInfo['refund_percentage']
        ]);

        // Add timeline entry
        $timelineStmt = $pdo->prepare("
            INSERT INTO booking_status_timeline (
                booking_id, status, reason, notes, changed_by
            ) VALUES (?, 'cancelled', ?, ?, ?)
        ");
        $timelineStmt->execute([
            $booking_id,
            $cancellation_reason,
            'Refund: ₱' . $refundInfo['refund_amount'],
            $user_id
        ]);

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Booking cancelled successfully',
            'refund' => $refundInfo
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function calculateBookingRefund($booking, $policy)
{
    $now = new DateTime();
    $checkin = new DateTime($booking['check_in_date']);
    $daysBefore = $checkin->diff($now)->days;

    $total_price = floatval($booking['total_price']);
    $refund_amount = 0;
    $refund_percentage = 0;

    if (!$policy) {
        // Default: no refund if less than 2 days before check-in
        $refund_amount = $daysBefore >= 2 ? $total_price : 0;
        $refund_percentage = $daysBefore >= 2 ? 100 : 0;
    } else {
        switch ($policy['policy_type']) {
            case 'flexible':
                if ($daysBefore >= 7) {
                    $refund_amount = $total_price;
                    $refund_percentage = 100;
                }
                break;
            case 'moderate':
                if ($daysBefore >= $policy['full_refund_days_before']) {
                    $refund_amount = $total_price;
                    $refund_percentage = 100;
                } elseif ($daysBefore >= $policy['partial_refund_days_before']) {
                    $refund_percentage = intval($policy['partial_refund_percentage']);
                    $refund_amount = $total_price * ($refund_percentage / 100);
                }
                break;
            case 'strict':
                if ($daysBefore >= 14) {
                    $refund_percentage = 50;
                    $refund_amount = $total_price * 0.5;
                }
                break;
            case 'non_refundable':
                $refund_amount = 0;
                $refund_percentage = 0;
                break;
        }
    }

    return [
        'refund_amount' => round($refund_amount, 2),
        'refund_percentage' => $refund_percentage,
        'original_amount' => $total_price
    ];
}

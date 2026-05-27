<?php
// api/calculate-refund.php
require_once 'db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['booking_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Booking ID required']);
    exit;
}

try {
    // Get booking and policy
    $stmt = $pdo->prepare("
        SELECT 
            b.id,
            b.total_price,
            b.check_in_date,
            cp.policy_type,
            cp.full_refund_days_before,
            cp.partial_refund_days_before,
            cp.partial_refund_percentage
        FROM bookings b
        JOIN cancellation_policies cp ON b.property_id = cp.property_id
        WHERE b.id = ?
    ");
    $stmt->execute([$data['booking_id']]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
        exit;
    }

    $refund = calculateRefundAmount($booking);

    echo json_encode([
        'success' => true,
        'refund' => $refund
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function calculateRefundAmount($booking)
{
    $now = new DateTime();
    $checkin = new DateTime($booking['check_in_date']);
    $daysBefore = $checkin->diff($now)->days;

    $total_price = floatval($booking['total_price']);
    $refund_amount = 0;
    $refund_percentage = 0;
    $refund_status = 'no_refund';

    switch ($booking['policy_type']) {
        case 'flexible':
            // Full refund up to 7 days before
            if ($daysBefore >= 7) {
                $refund_amount = $total_price;
                $refund_percentage = 100;
                $refund_status = 'full_refund';
            } else {
                $refund_status = 'no_refund_within_7_days';
            }
            break;

        case 'moderate':
            // Full refund if > full_refund_days_before
            if ($daysBefore >= $booking['full_refund_days_before']) {
                $refund_amount = $total_price;
                $refund_percentage = 100;
                $refund_status = 'full_refund';
            }
            // Partial refund if > partial_refund_days_before
            elseif ($daysBefore >= $booking['partial_refund_days_before']) {
                $refund_percentage = intval($booking['partial_refund_percentage']);
                $refund_amount = $total_price * ($refund_percentage / 100);
                $refund_status = 'partial_refund';
            } else {
                $refund_status = 'no_refund';
            }
            break;

        case 'strict':
            // Only partial refund up to 14 days before
            if ($daysBefore >= 14) {
                $refund_percentage = 50;
                $refund_amount = $total_price * 0.5;
                $refund_status = 'partial_refund';
            } else {
                $refund_status = 'no_refund';
            }
            break;

        case 'non_refundable':
            $refund_status = 'no_refund_non_refundable';
            break;

        default:
            $refund_status = 'unknown_policy';
    }

    return [
        'booking_id' => $booking['id'],
        'total_price' => $total_price,
        'refund_amount' => round($refund_amount, 2),
        'refund_percentage' => $refund_percentage,
        'refund_status' => $refund_status,
        'days_until_checkin' => $daysBefore,
        'policy_type' => $booking['policy_type'],
        'can_cancel' => $refund_status !== 'non_refundable' || $daysBefore > 1
    ];
}

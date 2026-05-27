<?php
// api/get-cancellation-policy.php
require_once 'db.php';
header('Content-Type: application/json');

$property_id = $_GET['property_id'] ?? null;
$booking_id = $_GET['booking_id'] ?? null;

try {
    if ($booking_id) {
        // Get policy for a booking
        $stmt = $pdo->prepare("
            SELECT 
                cp.id,
                cp.property_id,
                cp.policy_name,
                cp.description,
                cp.policy_type,
                cp.full_refund_days_before,
                cp.partial_refund_days_before,
                cp.partial_refund_percentage,
                cp.custom_rules,
                b.total_price,
                b.check_in_date
            FROM cancellation_policies cp
            JOIN bookings b ON cp.property_id = b.property_id
            WHERE b.id = ?
        ");
        $stmt->execute([$booking_id]);
    } elseif ($property_id) {
        // Get policy for a property
        $stmt = $pdo->prepare("
            SELECT 
                id,
                property_id,
                policy_name,
                description,
                policy_type,
                full_refund_days_before,
                partial_refund_days_before,
                partial_refund_percentage,
                custom_rules
            FROM cancellation_policies
            WHERE property_id = ? AND is_active = TRUE
        ");
        $stmt->execute([$property_id]);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Property ID or Booking ID required']);
        exit;
    }

    $policy = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($policy && $policy['custom_rules']) {
        $policy['custom_rules'] = json_decode($policy['custom_rules'], true);
    }

    echo json_encode([
        'success' => true,
        'policy' => $policy
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

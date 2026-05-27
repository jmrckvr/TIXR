<?php
// api/get-transactions.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$booking_id = $_GET['booking_id'] ?? null;

try {
    if ($booking_id) {
        // Check access
        $checkStmt = $pdo->prepare("SELECT user_id FROM bookings WHERE id = ?");
        $checkStmt->execute([$booking_id]);
        $booking = $checkStmt->fetch();

        if (!$booking || ($booking['user_id'] !== $user_id && $_SESSION['user_role'] !== 'admin')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Forbidden']);
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT 
                t.id,
                t.booking_id,
                t.amount,
                t.payment_method,
                t.transaction_id,
                t.reference_number,
                t.status,
                t.refund_amount,
                t.refund_reason,
                t.created_at,
                t.updated_at,
                pm.method_type,
                CASE 
                    WHEN pm.method_type = 'paypal' THEN pm.paypal_email
                    WHEN pm.method_type = 'gcash' THEN pm.gcash_number
                    ELSE CONCAT('****', pm.card_last_four)
                END as payment_detail
            FROM transactions t
            LEFT JOIN payment_methods pm ON t.payment_method_id = pm.id
            WHERE t.booking_id = ?
            ORDER BY t.created_at DESC
        ");
        $stmt->execute([$booking_id]);
    } else {
        // Get user's transactions
        $stmt = $pdo->prepare("
            SELECT 
                t.id,
                t.booking_id,
                t.amount,
                t.payment_method,
                t.transaction_id,
                t.reference_number,
                t.status,
                t.refund_amount,
                t.created_at,
                t.updated_at,
                b.check_in_date,
                b.check_out_date,
                p.name as property_name
            FROM transactions t
            JOIN bookings b ON t.booking_id = b.id
            JOIN properties p ON b.property_id = p.id
            WHERE b.user_id = ?
            ORDER BY t.created_at DESC
            LIMIT 50
        ");
        $stmt->execute([$user_id]);
    }

    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'transactions' => $transactions
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

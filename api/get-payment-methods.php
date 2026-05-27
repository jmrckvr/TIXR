<?php
// api/get-payment-methods.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        SELECT 
            id,
            method_type,
            is_primary,
            CASE 
                WHEN method_type = 'paypal' THEN paypal_email
                WHEN method_type = 'gcash' THEN gcash_number
                ELSE CONCAT('****', card_last_four)
            END as display_value,
            paypal_email,
            gcash_number,
            card_last_four,
            card_holder_name,
            is_verified,
            created_at
        FROM payment_methods
        WHERE user_id = ?
        ORDER BY is_primary DESC, created_at DESC
    ");
    $stmt->execute([$user_id]);
    $methods = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'methods' => $methods
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

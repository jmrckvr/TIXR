<?php
// api/save-payment-method.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['method_type'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Method type required']);
    exit;
}

$user_id = $_SESSION['user_id'];
$method_type = $data['method_type'];

try {
    // Validate method type
    $valid_methods = ['paypal', 'gcash', 'credit_card', 'debit_card', 'bank_transfer'];
    if (!in_array($method_type, $valid_methods)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid method type']);
        exit;
    }

    // If setting as primary, unset others first
    if (isset($data['is_primary']) && $data['is_primary']) {
        $unsetStmt = $pdo->prepare("
            UPDATE payment_methods SET is_primary = FALSE 
            WHERE user_id = ? AND method_type = ?
        ");
        $unsetStmt->execute([$user_id, $method_type]);
    }

    // Prepare statement based on method type
    $stmt = $pdo->prepare("
        INSERT INTO payment_methods (
            user_id, method_type, is_primary,
            paypal_email, gcash_number, card_last_four,
            card_expiry_month, card_expiry_year, card_holder_name, payment_token
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            paypal_email = VALUES(paypal_email),
            gcash_number = VALUES(gcash_number),
            card_last_four = VALUES(card_last_four),
            is_primary = VALUES(is_primary),
            updated_at = CURRENT_TIMESTAMP
    ");

    $paypal_email = null;
    $gcash_number = null;
    $card_last_four = null;
    $card_expiry_month = null;
    $card_expiry_year = null;
    $card_holder_name = null;
    $payment_token = null;

    switch ($method_type) {
        case 'paypal':
            $paypal_email = $data['email'] ?? null;
            $payment_token = $data['token'] ?? null;
            break;
        case 'gcash':
            $gcash_number = $data['phone_number'] ?? null;
            $payment_token = $data['token'] ?? null;
            break;
        case 'credit_card':
        case 'debit_card':
            $card_last_four = substr($data['card_number'] ?? '', -4);
            $card_expiry_month = $data['expiry_month'] ?? null;
            $card_expiry_year = $data['expiry_year'] ?? null;
            $card_holder_name = $data['card_holder_name'] ?? null;
            $payment_token = $data['token'] ?? null;
            break;
    }

    $stmt->execute([
        $user_id,
        $method_type,
        $data['is_primary'] ?? FALSE,
        $paypal_email,
        $gcash_number,
        $card_last_four,
        $card_expiry_month,
        $card_expiry_year,
        $card_holder_name,
        $payment_token
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Payment method saved successfully'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

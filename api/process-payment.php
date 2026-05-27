<?php
// api/process-payment.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['booking_id']) || !isset($data['payment_method'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$user_id = $_SESSION['user_id'];
$booking_id = $data['booking_id'];
$payment_method = $data['payment_method'];
$payment_method_id = $data['payment_method_id'] ?? null;

try {
    // Get booking details
    $bookingStmt = $pdo->prepare("
        SELECT b.id, b.user_id, b.total_price, b.status
        FROM bookings b
        WHERE b.id = ?
    ");
    $bookingStmt->execute([$booking_id]);
    $booking = $bookingStmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking || $booking['user_id'] !== $user_id) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden']);
        exit;
    }

    // Create transaction record
    $transactionStmt = $pdo->prepare("
        INSERT INTO transactions (
            booking_id, payment_method_id, amount, payment_method, 
            status, reference_number
        ) VALUES (?, ?, ?, ?, 'processing', ?)
    ");

    $reference = 'TXN-' . $booking_id . '-' . time();
    $transactionStmt->execute([
        $booking_id,
        $payment_method_id,
        $booking['total_price'],
        $payment_method,
        $reference
    ]);

    $transaction_id = $pdo->lastInsertId();

    // Process payment based on method
    $gateway_response = processPaymentGateway(
        $payment_method,
        $booking['total_price'],
        $booking_id,
        $reference,
        $data['token'] ?? null,
        $pdo
    );

    if ($gateway_response['success']) {
        // Update transaction status
        $updateStmt = $pdo->prepare("
            UPDATE transactions SET
                status = 'completed',
                transaction_id = ?,
                gateway_response = ?
            WHERE id = ?
        ");
        $updateStmt->execute([
            $gateway_response['transaction_id'] ?? $reference,
            json_encode($gateway_response),
            $transaction_id
        ]);

        // Update booking payment status
        $bookingUpdateStmt = $pdo->prepare("
            UPDATE bookings SET
                payment_status = 'paid',
                status = 'confirmed'
            WHERE id = ?
        ");
        $bookingUpdateStmt->execute([$booking_id]);

        // Add timeline entry
        $timelineStmt = $pdo->prepare("
            INSERT INTO booking_status_timeline (
                booking_id, status, notes, changed_by
            ) VALUES (?, 'confirmed', 'Payment processed successfully', ?)
        ");
        $timelineStmt->execute([$booking_id, $user_id]);

        echo json_encode([
            'success' => true,
            'message' => 'Payment processed successfully',
            'transaction_id' => $transaction_id
        ]);
    } else {
        // Update transaction with failure
        $updateStmt = $pdo->prepare("
            UPDATE transactions SET
                status = 'failed',
                failure_reason = ?,
                gateway_response = ?
            WHERE id = ?
        ");
        $updateStmt->execute([
            $gateway_response['error'] ?? 'Unknown error',
            json_encode($gateway_response),
            $transaction_id
        ]);

        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $gateway_response['error'] ?? 'Payment processing failed'
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function processPaymentGateway($method, $amount, $booking_id, $reference, $token, $pdo)
{
    // This is a mock implementation
    // In production, integrate with actual PayPal/GCash APIs

    switch ($method) {
        case 'paypal':
            return processPayPalPayment($amount, $booking_id, $reference, $token);
        case 'gcash':
            return processGCashPayment($amount, $booking_id, $reference, $token);
        default:
            return ['success' => false, 'error' => 'Unsupported payment method'];
    }
}

function processPayPalPayment($amount, $booking_id, $reference, $token)
{
    // Mock PayPal API call
    // In production, use https://developer.paypal.com/

    // Simulate API call
    $response = [
        'success' => true,
        'transaction_id' => 'PAYPAL-' . uniqid(),
        'amount' => $amount,
        'currency' => 'PHP',
        'status' => 'COMPLETED',
        'timestamp' => date('Y-m-d H:i:s')
    ];

    return $response;
}

function processGCashPayment($amount, $booking_id, $reference, $token)
{
    // Mock GCash API call
    // In production, use GCash merchant API

    // Simulate API call
    $response = [
        'success' => true,
        'transaction_id' => 'GCASH-' . uniqid(),
        'amount' => $amount,
        'currency' => 'PHP',
        'status' => 'COMPLETED',
        'timestamp' => date('Y-m-d H:i:s')
    ];

    return $response;
}

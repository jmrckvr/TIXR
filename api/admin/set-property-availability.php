<?php
// api/admin/set-property-availability.php
session_start();
require_once '../db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['property_id']) || !isset($data['date']) || !isset($data['is_available'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    // Check if entry exists
    $checkStmt = $pdo->prepare("
        SELECT id FROM property_availability 
        WHERE property_id = ? AND date = ?
    ");
    $checkStmt->execute([$data['property_id'], $data['date']]);
    $exists = $checkStmt->fetch();

    if ($exists) {
        // Update existing
        $stmt = $pdo->prepare("
            UPDATE property_availability SET
                is_available = ?,
                price_override = ?,
                notes = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE property_id = ? AND date = ?
        ");
        $stmt->execute([
            $data['is_available'],
            $data['price_override'] ?? null,
            $data['notes'] ?? null,
            $data['property_id'],
            $data['date']
        ]);
    } else {
        // Insert new
        $stmt = $pdo->prepare("
            INSERT INTO property_availability (
                property_id, date, is_available, price_override, notes
            ) VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['property_id'],
            $data['date'],
            $data['is_available'],
            $data['price_override'] ?? null,
            $data['notes'] ?? null
        ]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Availability updated successfully'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

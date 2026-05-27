<?php
// api/admin/manage-cancellation-policies.php
session_start();
require_once '../db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        // Get all policies or specific property's policy
        $property_id = $_GET['property_id'] ?? null;

        if ($property_id) {
            $stmt = $pdo->prepare("
                SELECT * FROM cancellation_policies
                WHERE property_id = ?
                ORDER BY created_at DESC
            ");
            $stmt->execute([$property_id]);
        } else {
            $stmt = $pdo->prepare("
                SELECT cp.*, p.name as property_name
                FROM cancellation_policies cp
                LEFT JOIN properties p ON cp.property_id = p.id
                ORDER BY cp.created_at DESC
            ");
            $stmt->execute();
        }

        $policies = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'policies' => $policies
        ]);
    } elseif ($method === 'POST') {
        // Create or update policy
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['property_id']) || !isset($data['policy_type'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit;
        }

        if (isset($data['id'])) {
            // Update
            $stmt = $pdo->prepare("
                UPDATE cancellation_policies SET
                    policy_name = ?,
                    description = ?,
                    policy_type = ?,
                    full_refund_days_before = ?,
                    partial_refund_days_before = ?,
                    partial_refund_percentage = ?,
                    custom_rules = ?,
                    is_active = ?
                WHERE id = ? AND property_id = ?
            ");
            $stmt->execute([
                $data['policy_name'] ?? 'Default Policy',
                $data['description'] ?? null,
                $data['policy_type'],
                $data['full_refund_days_before'] ?? 7,
                $data['partial_refund_days_before'] ?? 3,
                $data['partial_refund_percentage'] ?? 50,
                isset($data['custom_rules']) ? json_encode($data['custom_rules']) : null,
                $data['is_active'] ?? true,
                $data['id'],
                $data['property_id']
            ]);
            $message = 'Policy updated successfully';
        } else {
            // Create
            $stmt = $pdo->prepare("
                INSERT INTO cancellation_policies (
                    property_id, policy_name, description, policy_type,
                    full_refund_days_before, partial_refund_days_before,
                    partial_refund_percentage, custom_rules, is_active
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['property_id'],
                $data['policy_name'] ?? 'Default Policy',
                $data['description'] ?? null,
                $data['policy_type'],
                $data['full_refund_days_before'] ?? 7,
                $data['partial_refund_days_before'] ?? 3,
                $data['partial_refund_percentage'] ?? 50,
                isset($data['custom_rules']) ? json_encode($data['custom_rules']) : null,
                $data['is_active'] ?? true
            ]);
            $message = 'Policy created successfully';
        }

        echo json_encode([
            'success' => true,
            'message' => $message
        ]);
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

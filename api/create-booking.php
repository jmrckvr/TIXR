<?php
// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start session first
session_start();

// Set CORS headers with proper origin handling
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (strpos($origin, 'http://localhost') === 0 || strpos($origin, 'http://127.0.0.1') === 0) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
}
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'User not authenticated. Session: ' . print_r($_SESSION, true)]);
    exit;
}

// Load database connection
require_once 'db.php';

$user_id = $_SESSION['user_id'];

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$required_fields = ['property_id', 'check_in_date', 'check_out_date', 'total_price', 'guests'];
foreach ($required_fields as $field) {
    if (!isset($input[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => "Missing required field: $field"]);
        exit;
    }
}

$property_id = $input['property_id'];
$check_in_date = $input['check_in_date'];
$check_out_date = $input['check_out_date'];
$total_price = $input['total_price'];
$guests = $input['guests'];
$guest_details = $input['guest_details'] ?? [];

try {
    // Insert booking into database
    $sql = "INSERT INTO bookings (user_id, property_id, check_in_date, check_out_date, total_price, number_of_guests, guest_first_name, guest_last_name, guest_email, guest_phone, special_requests, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed', NOW())";

    $stmt = $pdo->prepare($sql);

    $success = $stmt->execute([
        $user_id,
        $property_id,
        $check_in_date,
        $check_out_date,
        $total_price,
        $guests,
        $guest_details['firstName'] ?? '',
        $guest_details['lastName'] ?? '',
        $guest_details['email'] ?? '',
        $guest_details['phone'] ?? '',
        $guest_details['specialRequests'] ?? ''
    ]);

    if ($success) {
        $booking_id = $pdo->lastInsertId();

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Booking created successfully',
            'booking_id' => $booking_id
        ]);
    } else {
        throw new Exception('Failed to insert booking');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

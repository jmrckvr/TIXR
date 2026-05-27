<?php
// api/get-property-availability.php
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_GET['property_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Property ID required']);
    exit;
}

$property_id = $_GET['property_id'];
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('m');

try {
    // Get availability calendar for the specified month
    $stmt = $pdo->prepare("
        SELECT 
            date,
            is_available,
            price_override,
            notes
        FROM property_availability
        WHERE property_id = ?
        AND YEAR(date) = ?
        AND MONTH(date) = ?
        ORDER BY date ASC
    ");
    $stmt->execute([$property_id, $year, $month]);
    $availability = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get booked dates from bookings
    $bookingStmt = $pdo->prepare("
        SELECT 
            DISTINCT DATE(check_in_date) as booking_date
        FROM bookings
        WHERE property_id = ?
        AND status IN ('confirmed', 'pending')
        AND (YEAR(check_in_date) = ? AND MONTH(check_in_date) = ?)
        OR (YEAR(check_out_date) = ? AND MONTH(check_out_date) = ?)
    ");
    $bookingStmt->execute([$property_id, $year, $month, $year, $month]);
    $booked_dates = $bookingStmt->fetchAll(PDO::FETCH_COLUMN);

    // Get property base price
    $priceStmt = $pdo->prepare("SELECT price FROM properties WHERE id = ?");
    $priceStmt->execute([$property_id]);
    $base_price = $priceStmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'availability' => $availability,
        'booked_dates' => $booked_dates,
        'base_price' => $base_price,
        'year' => $year,
        'month' => $month
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

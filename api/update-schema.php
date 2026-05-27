<?php
require_once 'db.php';

// Add missing columns to bookings table if they don't exist
$columns_to_add = [
    'number_of_guests' => "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS number_of_guests INT DEFAULT 1",
    'guest_first_name' => "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS guest_first_name VARCHAR(100)",
    'guest_last_name' => "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS guest_last_name VARCHAR(100)",
    'guest_email' => "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS guest_email VARCHAR(255)",
    'guest_phone' => "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS guest_phone VARCHAR(20)",
    'special_requests' => "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS special_requests TEXT"
];

try {
    foreach ($columns_to_add as $column => $sql) {
        $pdo->exec($sql);
        echo "✓ Column '$column' checked/added\n";
    }
    echo "\nDatabase schema update completed successfully!";
} catch (Exception $e) {
    echo "Error updating database: " . $e->getMessage();
}

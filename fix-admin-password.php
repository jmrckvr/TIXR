<?php
$hash = password_hash('admin123', PASSWORD_DEFAULT);
echo "New hash: " . $hash . "\n";

$conn = new mysqli('localhost', 'root', '', 'tixr');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

$stmt = $conn->prepare('UPDATE users SET password = ? WHERE role = ?');
$stmt->bind_param('ss', $hash, $role);
$role = 'admin';
$stmt->execute();
echo "Admin password updated successfully!\n";

// Verify it works
$stmt = $conn->prepare('SELECT password FROM users WHERE role = ?');
$stmt->bind_param('s', $role);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$dbHash = $row['password'];

if (password_verify('admin123', $dbHash)) {
    echo "✓ Password verification successful! Password 'admin123' is correct.\n";
} else {
    echo "✗ Password verification failed!\n";
}

$stmt->close();
$conn->close();

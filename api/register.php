<?php
// Handle preflight OPTIONS request
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
// Accept localhost on any port
if (strpos($origin, 'http://localhost') === 0 || strpos($origin, 'http://127.0.0.1') === 0) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
}
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header("Content-Type: application/json");

session_start();

require_once "db.php";

$data = json_decode(file_get_contents("php://input"), true);
$username = $data["username"] ?? "";
$email = $data["email"] ?? "";
$password = $data["password"] ?? "";
// All new registrations are automatically assigned as 'customer' role
$role = "customer";

// Validate input
if (!$username || !$email || !$password) {
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Invalid email format"]);
    exit;
}

// Validate password strength (minimum 6 characters)
if (strlen($password) < 6) {
    echo json_encode(["success" => false, "message" => "Password must be at least 6 characters"]);
    exit;
}

$hashed = password_hash($password, PASSWORD_DEFAULT);

// All new users are inserted into the unified users table with customer role
try {
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, :role)");

    $stmt->execute(["username" => $username, "email" => $email, "password" => $hashed, "role" => $role]);

    // Get the newly inserted user ID
    $userId = $pdo->lastInsertId();

    // Set session data for automatic login after registration
    $_SESSION["user_id"] = $userId;
    $_SESSION["is_admin"] = false;
    $_SESSION["email"] = $email;
    $_SESSION["username"] = $username;

    http_response_code(200);
    echo json_encode([
        "success" => true,
        "message" => "Registration successful",
        "userId" => $userId,
        "username" => $username,
        "email" => $email,
        "role" => $role
    ]);
} catch (PDOException $e) {
    // Check if it's a duplicate entry error
    if (strpos($e->getMessage(), "Duplicate") !== false || strpos($e->getMessage(), "UNIQUE") !== false) {
        http_response_code(409);
        echo json_encode(["success" => false, "message" => "Email or username already registered"]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Registration failed: " . $e->getMessage()]);
    }
}

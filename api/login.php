<?php
// ✅ CORS headers FIRST — before anything else
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

// Decode payload
$data = json_decode(file_get_contents("php://input"), true);
$email = $data["email"] ?? "";
$password = $data["password"] ?? "";
$type = $data["type"] ?? "customer";

// Validate input
if (!$email || !$password) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Email and password required"]);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid email format"]);
    exit;
}

try {
    // Query the unified users table
    $stmt = $pdo->prepare("SELECT id, username, email, password, role FROM users WHERE email = :email LIMIT 1");
    $stmt->execute(["email" => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Account not found"]);
        exit;
    }

    // Verify password
    if (!password_verify($password, $user["password"])) {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Invalid password"]);
        exit;
    }

    // Check if user type matches their role
    if ($type === "admin" && $user["role"] !== "admin") {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Admin account not found"]);
        exit;
    }

    if ($type === "customer" && $user["role"] !== "customer") {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Customer account not found"]);
        exit;
    }

    // Login successful - set session
    $_SESSION["user_id"] = $user["id"];
    $_SESSION["is_admin"] = $user["role"] === "admin";
    $_SESSION["email"] = $user["email"];
    $_SESSION["username"] = $user["username"];

    http_response_code(200);
    echo json_encode([
        "success" => true,
        "message" => ucfirst($user["role"]) . " login successful",
        "role" => $user["role"],
        "userId" => $user["id"],
        "username" => $user["username"],
        "email" => $user["email"]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}

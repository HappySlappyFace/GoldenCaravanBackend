<?php
include 'config.php'; // Your PDO configuration file


header("Access-Control-Allow-Origin: http://localhost:5173"); // Allow only your frontend to access
header("Access-Control-Allow-Credentials: true"); // Allow cookies
header("Access-Control-Allow-Headers: Content-Type"); // Allow only headers of type Content-Type
header("Access-Control-Allow-Methods: POST, OPTIONS");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Required for CORS preflight check
    header("Access-Control-Allow-Origin: http://localhost:5173"); // Adjust this to your front-end's actual origin
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    header("Access-Control-Allow-Credentials: true");
    header("Content-Length: 0");
    header("Content-Type: text/plain");
    http_response_code(200); // You need to send back an HTTP 200 OK status
    exit;
}
header('Content-Type: application/json');

// Function to safely fetch and validate POSTed data
function get_post_data($key) {
    return isset($_POST[$key]) ? trim($_POST[$key]) : null;
}

// Start the session
session_start();

// Decode the JSON received from the frontend
$data = json_decode(file_get_contents('php://input'), true);

// Validate and sanitize inputs
$firstName = filter_var($data['firstName'], FILTER_SANITIZE_STRING);
$lastName = filter_var($data['lastName'], FILTER_SANITIZE_STRING);
$email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
// $password = password_hash($data['password'], PASSWORD_DEFAULT); // Hashing the password
$password = filter_var($data['password'], FILTER_SANITIZE_STRING); // Hashing the password
$phone = filter_var($data['phone'], FILTER_SANITIZE_NUMBER_INT);
error_log($password);
// Check if the email already exists
$query = $pdo->prepare("SELECT * FROM Users WHERE email = ?");
$query->execute([$email]);
if ($query->fetch()) {
    echo json_encode(['error' => 'Email already exists']);
    exit;
}

// Insert the new user into the database
$query = $pdo->prepare("INSERT INTO Users (firstName, lastName, email, password, phone) VALUES (?, ?, ?, ?, ?)");
$result = $query->execute([$firstName, $lastName, $email, $password, $phone]);

if ($result) {
    $_SESSION['user_id'] = $pdo->lastInsertId(); // Storing user id in session
    $_SESSION['email'] = $email; // Storing email in session
    echo json_encode(['success' => 'User registered successfully']);
} else {
    echo json_encode(['error' => 'Failed to register user']);
}
?>

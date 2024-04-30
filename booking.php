<?php
session_start(); // Start or resume an existing session

// Include your database connection from config.php
require 'config.php';
header('Content-Type: application/json');
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
// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // If the user is not logged in, return an error
    http_response_code(403);
    echo json_encode(['error' => 'User not authenticated']);
    exit;
}

// Collect data from POST request
$roomId = $_POST['roomId'] ?? '';
$startDate = $_POST['startDate'] ?? '';
$endDate = $_POST['endDate'] ?? '';
$status = $_POST['status'] ?? 'pending'; // Default status
$price = $_POST['price'] ?? 0; // Default price to 0 if not provided

// Validate data - simple validation
if (empty($roomId) || empty($startDate) || empty($endDate)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// Prepare SQL statement to insert booking
$sql = "INSERT INTO booking (idClient, idRoom, startDate, endDate, status, price) 
        VALUES (:idClient, :idRoom, :startDate, :endDate, :status, :price)";
$stmt = $pdo->prepare($sql);

// Bind parameters to statement
$params = [
    ':idClient' => $_SESSION['user_id'], // Assuming 'user_id' is stored in session
    ':idRoom' => $roomId,
    ':startDate' => $startDate,
    ':endDate' => $endDate,
    ':status' => $status,
    ':price' => $price
];

// Execute the statement
try {
    $stmt->execute($params);
    echo json_encode(['success' => 'Reservation created successfully']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>

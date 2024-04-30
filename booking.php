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

$roomId = $_POST['roomId'] ?? '';
$startDate = $_POST['startDate'] ?? '';
$endDate = $_POST['endDate'] ?? '';
error_log($roomId);
error_log($startDate);
error_log($endDate);
if (empty($roomId) || empty($startDate) || empty($endDate)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}
function calculatePrice($pdo, $roomId, $startDate, $endDate) {

    $sql = "SELECT price FROM Rooms WHERE idRoom = :idRoom";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':idRoom' => $roomId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $basePrice = $row['price'];


    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $interval = $start->diff($end);
    $numberOfDays = $interval->days;

    //this will only calculate the price depending on base price, did not implement season pricing yet
    $totalPrice = $basePrice * $numberOfDays;

    return $totalPrice;
}

$price = calculatePrice($pdo, $roomId, $startDate, $endDate);
$sql = "INSERT INTO Booking (idClient, idRoom, startDate, endDate, status, price) 
        VALUES (:idClient, :idRoom, :startDate, :endDate, :status, :price)";
$stmt = $pdo->prepare($sql);
$params = [
    ':idClient' => $_SESSION['user_id'],
    ':idRoom' => $roomId,
    ':startDate' => $startDate,
    ':endDate' => $endDate,
    ':status' => 0, 
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

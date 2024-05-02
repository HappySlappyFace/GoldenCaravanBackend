<?php
session_start(); // Start or resume an existing session

// Include your database connection from config.php
require 'config.php';
require 'corsFix.php';

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
    // echo $roomId;
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
    $totalPrice = $basePrice * ($numberOfDays+1);

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
// echo $price;
ini_set('display_errors', 1);
error_reporting(E_ALL);
// Execute the statement
try {
    $stmt->execute($params);
    echo json_encode(['success' => 'Reservation created successfully']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>

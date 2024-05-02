<?php
session_start();
require 'config.php';
require 'corsFix.php';

if (!isset($_SESSION['user_id'])) {
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


    // Determine if any part of the stay falls within the high season
    $highSeasonStart = new DateTime(date("Y") . "-06-01");
    $highSeasonEnd = new DateTime(date("Y") . "-08-31");

    // Adjust date objects to ensure they only include the year of the reservation
    $highSeasonStart->setDate($start->format('Y'), 6, 1);
    $highSeasonEnd->setDate($start->format('Y'), 8, 31);

    // Calculate the total days in high season
    $daysInHighSeason = 0;

    // Loop from start to end date and count days within high season
    for ($date = clone $start; $date <= $end; $date->modify('+1 day')) {
        if ($date >= $highSeasonStart && $date <= $highSeasonEnd) {
            $daysInHighSeason++;
        }
    }

    // Calculate total price
    if ($daysInHighSeason > 0) {
        // Calculate high season price and off-season price
        $highSeasonPrice = ($basePrice * 1.20) * $daysInHighSeason; // 20% increase in base price
        $offSeasonDays = $numberOfDays + 1 - $daysInHighSeason;
        $offSeasonPrice = $basePrice * $offSeasonDays;
        $totalPrice = $highSeasonPrice + $offSeasonPrice;
    } else {
        $totalPrice = $basePrice * ($numberOfDays + 1);
    }

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

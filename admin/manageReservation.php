<?php
  // This includes the $pdo connection

include '../corsFix.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);
// session_start();

function finishReservation($pdo, $id) {
    $stmt = $pdo->prepare("UPDATE Booking SET status = 2 WHERE idBooking = ? AND status = 1");
    $result = $stmt->execute([$id]);
    return $result ? ['success' => true, 'message' => 'Reservation finished'] : ['success' => false, 'message' => 'Failed to finish reservation'];
}

function validateReservation($pdo, $id) {
    $stmt = $pdo->prepare("UPDATE Booking SET status = 1 WHERE idBooking = ? AND status = 0");
    $result = $stmt->execute([$id]);
    return $result ? ['success' => true, 'message' => 'Reservation validated'] : ['success' => false, 'message' => 'Failed to validate reservation'];
}

function createReservation($pdo) {
    // Assuming POST data contains reservation details
    $stmt = $pdo->prepare("INSERT INTO Booking (startDate, endDate, hotelName, status, price) VALUES (?, ?, ?, 0, ?)");
    $result = $stmt->execute([$_POST['startDate'], $_POST['endDate'], $_POST['hotelName'], $_POST['price']]);
    return $result ? ['success' => true, 'message' => 'Reservation created'] : ['success' => false, 'message' => 'Failed to create reservation'];
}

function updateReservation($pdo, $id) {
    $stmt = $pdo->prepare("UPDATE Booking SET startDate=?, endDate=?, hotelName=?, price=? WHERE idBooking=?");
    $result = $stmt->execute([$_POST['startDate'], $_POST['endDate'], $_POST['hotelName'], $_POST['price'], $id]);
    echo $result ? ['success' => true, 'message' => 'Reservation updated'] : ['success' => false, 'message' => 'Failed to update reservation'];
}

function deleteReservation($pdo, $id) {
    $stmt = $pdo->prepare("DELETE FROM Booking WHERE idBooking=?");
    $result = $stmt->execute([$id]);
    return $result ? ['success' => true, 'message' => 'Reservation deleted'] : ['success' => false, 'message' => 'Failed to delete reservation'];
}

function router($uri) {
    $parts = explode('/', trim($uri, '/'));
    // $script = array_shift($parts);  // Assuming the first part is "manageReservation.php"
    $action = $parts[0] ?? null;
    $id = $parts[1] ?? null;
    include '../authenticate.php';
    // if (!authorize_user(['Admin', 'SuperAdmin'])) {
    //     echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    //     return;
    // }
    // echo $action;
    include '../config.php';
    switch ($action) {
        case 'finishReservation':
            $result = finishReservation($pdo, $id);
            break;
        case 'validateReservation':
            $result = validateReservation($pdo, $id);
            break;
        case 'createReservation':
            $result = createReservation($pdo);
            break;
        case 'updateReservation':
            $result = updateReservation($pdo, $id);
            break;
        case 'deleteReservation':
            $result = deleteReservation($pdo, $id);
            break;
        default:
            $result = ['success' => false, 'message' => 'Invalid action'];
    }
    
    echo json_encode($result);
    
}

// Get the request URI and trim the base path of the script if necessary
$base_path = str_replace('index.php', '', $_SERVER['SCRIPT_NAME']);
$request_uri = str_replace($base_path, '', $_SERVER['REQUEST_URI']);
router($request_uri);
?>
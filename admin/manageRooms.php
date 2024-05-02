<?php
include '../corsFix.php';
header('Content-Type: application/json');

include '../authenticate.php';
if (!authorize_user(['Admin', 'SuperAdmin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

$method = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];
$pathParts = explode('/', trim($requestUri, '/'));
$roomId = end($pathParts); // Assuming the last part of the path is the room ID

switch ($method) {
    case 'GET':
        fetchRooms();
        break;
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        createRoom($pdo,$data);
        break;
    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        updateRoom($roomId, $data);
        break;
    case 'DELETE':
        deleteRoom($roomId);
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}

function getNextRoomId($pdo, $prefix) {
    // Add a check to ensure prefix is not null or empty
    if (!$prefix) {
        return null; // or handle this case as appropriate
    }

    $stmt = $pdo->prepare("SELECT idRoom FROM Rooms WHERE idRoom LIKE ? ORDER BY idRoom DESC LIMIT 1");
    $stmt->execute([$prefix . '%']);
    $lastRoom = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($lastRoom && isset($lastRoom['idRoom'])) {
        $lastId = $lastRoom['idRoom'];
        // Ensure that $lastId is not null before using strlen()
        $numericPart = substr($lastId, strlen($prefix)); // Extract the numeric part
        $nextNumber = intval($numericPart) + 1; // Increment the number
        $nextId = $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT); // Ensure the ID is correctly padded
    } else {
        // If no existing room, start with the first ID in the sequence
        $nextId = $prefix . '001';
    }

    return $nextId;
}

function createRoom($pdo, $data) {
    // Check if 'idHotelPrefix' is provided
    if (!isset($data['idHotelPrefix']) || !$data['idHotelPrefix']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Hotel prefix is missing']);
        return;
    }

    try {
        $newId = getNextRoomId($pdo, $data['idHotelPrefix']); // Generate the new room ID

        if (!$newId) {
            throw new Exception("Failed to generate a new room ID.");
        }

        $stmt = $pdo->prepare('INSERT INTO Rooms (idRoom, idHotel, numberOfBeds, status, price, roomType, imageUrl) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$newId, $data['idHotel'], $data['numberOfBeds'], $data['status'], $data['price'], $data['roomType'], $data['imageUrl']]);
        echo json_encode(['success' => true, 'message' => 'Room created successfully', 'newId' => $newId]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to create room: ' . $e->getMessage()]);
    }
}

function fetchRooms() {
    global $pdo;
    try {
        $stmt = $pdo->query('SELECT * FROM Rooms');
        $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'rooms' => $rooms]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to fetch rooms']);
    }
}



function updateRoom($id, $data) {
    global $pdo;
    if (empty($id) || !is_array($data)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid data provided']);
        return;
    }
    try {
        $stmt = $pdo->prepare('UPDATE Rooms SET numberOfBeds = ?, status = ?, price = ?, roomType = ?, imageUrl = ? WHERE idRoom = ?');
        $stmt->execute([$data['numberOfBeds'], $data['status'], $data['price'], $data['roomType'], $data['imageUrl'], $id]);
        echo json_encode(['success' => true, 'message' => 'Room updated successfully']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update room']);
    }
}

function deleteRoom($id) {
    global $pdo;
    if (empty($id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid ID']);
        return;
    }
    try {
        $stmt = $pdo->prepare('DELETE FROM Rooms WHERE idRoom = ?');
        $stmt->execute([$id]);
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Room deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No room found with that ID']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to delete room']);
    }
}
?>

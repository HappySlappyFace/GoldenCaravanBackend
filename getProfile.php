<?php
session_start();
include 'config.php';
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
  

function getUserData($pdo, $userId) {
    try {
        $stmt = $pdo->prepare("SELECT idUser, firstName, lastName, email FROM Users WHERE idUser = :userId");
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        return $userData;
    } catch(PDOException $e) {
        
        error_log("Error fetching user data: " . $e->getMessage());
        return null; 
    }
}

// $userId = 1;
// $userData = getUserData($pdo, $userId);

if (isset($_SESSION['user_id'])) {
    $userData = getUserData($pdo,$_SESSION['user_id']);
    
    if ($userData) {
        // Send user data as JSON response
        echo json_encode([
            'status' => 'success',
            'data' => $userData
        ]);
    } else {
        // Send error if user data cannot be fetched
        echo json_encode([
            'status' => 'error',
            'message' => 'Could not retrieve user data.'
        ]);
    }
} else {
    // Send error if user is not logged in
    echo json_encode([
        'status' => 'error',
        'message' => 'User is not logged in.'
    ]);
}
?>

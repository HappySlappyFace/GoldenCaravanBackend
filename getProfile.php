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
        // Prepare a SELECT statement to fetch user data
        $stmt = $pdo->prepare("SELECT idUser, firstName, lastName, email FROM Users WHERE idUser = :userId");
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);

        // Execute the statement
        $stmt->execute();

        // Fetch the user data
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        // Return the user data
        return $userData;
    } catch(PDOException $e) {
        // Handle error
        error_log("Error fetching user data: " . $e->getMessage());
        return null; // Or you could throw an exception depending on your error handling strategy
    }
}

// Example usage:
// $userId = 1; // The user ID you want to retrieve data for
// $userData = getUserData($pdo, $userId);

if (isset($_SESSION['user_id'])) {
    // Assuming you have a function that gets user data based on user_id
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

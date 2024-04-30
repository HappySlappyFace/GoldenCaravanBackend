<?php
include 'config.php'; // Include your database configuration as needed
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

if (isset($_FILES['profilePicture'])) {
    $targetDirectory = "uploads/";
    $fileName = basename($_FILES['profilePicture']['name']);
    $targetFilePath = $targetDirectory . $fileName;

    // Attempt to move the uploaded file
    if (move_uploaded_file($_FILES['profilePicture']['tmp_name'], $targetFilePath)) {
        $url = 'http://localhost/Web2/Project/uploads/' . $fileName;  // Construct the URL or path to the file

        // Assuming $pdo is your PDO database connection object
        // Prepare an SQL statement to update the user's profile picture
        $stmt = $pdo->prepare("UPDATE Users SET profilePicture = ? WHERE idUser = ?");
        session_start();
        // Bind parameters and execute
        $user_id = $_SESSION['user_id'];  // This should be dynamically determined based on the logged-in user
        $stmt->execute([$url, $user_id]);

        if ($stmt->rowCount()) {
            echo json_encode(['success' => 'Profile picture updated.', 'url' => $url]);
        } else {
            echo json_encode(['error' => 'Failed to update profile picture in database.']);
        }

        echo json_encode(['url' => $url]);
    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode(['error' => 'Server permission error: Unable to save file.']);
    }
} else {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'No file uploaded.']);
}

?>

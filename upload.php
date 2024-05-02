<?php
include 'config.php';
include 'corsFix.php';

session_start(); // Start the session at the top of the script

if (!isset($_SESSION['user_id'])) {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'User not logged in or session expired']);
    exit; // Stop script execution if user ID is not set
}

if (isset($_FILES['profilePicture'])) {
    $targetDirectory = "uploads/";
    $fileName = basename($_FILES['profilePicture']['name']);
    $targetFilePath = $targetDirectory . $fileName;

    if (move_uploaded_file($_FILES['profilePicture']['tmp_name'], $targetFilePath)) {
        $url = 'http://localhost/Web2/Project/uploads/' . $fileName;  // Construct the URL or path to the file

        $stmt = $pdo->prepare("UPDATE Users SET profilePicture = ? WHERE idUser = ?");
        $user_id = $_SESSION['user_id']; // Retrieve the user ID from session
        $stmt->execute([$url, $user_id]);

        if ($stmt->rowCount()) {
            echo json_encode(['success' => 'Profile picture updated.', 'url' => $url]);
        } else {
            echo json_encode(['error' => 'Failed to update profile picture in database.']);
        }
    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode(['error' => 'Server permission error: Unable to save file.']);
    }
} else {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'No file uploaded.']);
}
?>

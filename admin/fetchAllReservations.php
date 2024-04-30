<?php
// Include authentication file
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');
require_once '../authenticate.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Set the appropriate CORS headers for preflight requests
    header('Access-Control-Allow-Origin: http://localhost:5173');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    header('Access-Control-Allow-Credentials: true');
    exit; // Stop script execution for preflight requests
}

// Verify admin session before proceeding
$requiredRoles = ['Admin', 'SuperAdmin']; // Assuming 'admin' role is required for this action
if(authorize_user($requiredRoles)){
    require '../config.php';

    // Fetch all reservations from the database
    $sql = "SELECT * FROM Booking";
    $stmt = $pdo->query($sql);
    
    // Check if there are any reservations
    if ($stmt->rowCount() > 0) {
        // Fetch all rows as an associative array
        $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Output the reservations as JSON
        echo json_encode($reservations);
    } else {
        // No reservations found
        echo json_encode(['message' => 'No reservations found']);
    }
} else {
    // echo"tet"
    http_response_code(403); // Forbidden
    echo json_encode(array('status' => 'fail', 'message' => 'Not authorized.'));
    exit;
}
?>

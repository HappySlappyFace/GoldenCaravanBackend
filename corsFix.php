<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://localhost:5173"); // Allow only your frontend to access
header("Access-Control-Allow-Credentials: true"); // Allow cookies
header("Access-Control-Allow-Headers: Content-Type"); // Allow only headers of type Content-Type
header("Access-Control-Allow-Methods: POST, OPTIONS");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Required for CORS preflight check
    header("Access-Control-Allow-Origin: http://localhost:5173"); // Adjust this to your front-end's actual origin
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    header("Access-Control-Allow-Credentials: true");
    header("Content-Length: 0");
    header("Content-Type: text/plain");
    http_response_code(200); // You need to send back an HTTP 200 OK status
    exit;
}
?>
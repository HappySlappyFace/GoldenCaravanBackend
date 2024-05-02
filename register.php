<?php
include 'config.php';
include 'corsFix.php';

function get_post_data($key) {
    return isset($_POST[$key]) ? trim($_POST[$key]) : null;
}

session_start();

$data = json_decode(file_get_contents('php://input'), true);

// Validate and sanitize inputs
$firstName = filter_var($data['firstName'], FILTER_SANITIZE_STRING);
$lastName = filter_var($data['lastName'], FILTER_SANITIZE_STRING);
$email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
// $password = password_hash($data['password'], PASSWORD_DEFAULT); // Hashing the password
$password = filter_var($data['password'], FILTER_SANITIZE_STRING); // Hashing the password
$phone = filter_var($data['phone'], FILTER_SANITIZE_NUMBER_INT);
error_log($password);


$query = $pdo->prepare("SELECT * FROM Users WHERE email = ?");
$query->execute([$email]);
if ($query->fetch()) {
    echo json_encode(['error' => 'Email already exists']);
    exit;
}

$query = $pdo->prepare("INSERT INTO Users (firstName, lastName, email, password, phone) VALUES (?, ?, ?, ?, ?)");
$result = $query->execute([$firstName, $lastName, $email, $password, $phone]);

if ($result) {
    $_SESSION['user_id'] = $pdo->lastInsertId();
    $_SESSION['email'] = $email;
    echo json_encode(['success' => 'User registered successfully']);
} else {
    echo json_encode(['error' => 'Failed to register user']);
}
?>

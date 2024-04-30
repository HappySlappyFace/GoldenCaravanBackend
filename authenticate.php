<?php
require_once 'config.php';
function authenticate_user($pdo,$email, $password) {
    $statement = $pdo->prepare("SELECT * FROM Users WHERE email = :email AND password = :password");
    $statement->execute(array(":email" => $email, ":password" => $password));
    $user = $statement->fetch(PDO::FETCH_ASSOC);
    return $user ? $user : false;
    
    // return $email === 'test@example.com' && $password === 'password' ? ['id' => 1] : false;
}
function authorize_user($requiredRoles) {
    session_start();
    // error_log($_SESSION['user_role']);
    if (empty($requiredRoles)) {
        return true;
    }
    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] && isset($_SESSION['user_role'])) {
        // Check if the user's role is in the array of allowed roles
        if (in_array($_SESSION['user_role'], $requiredRoles)) {
            return true;
        }
    }
    return false;
}
function getUserRoles() {
    session_start();
    // error_log($_SESSION['user_role']);
    return $_SESSION['user_role'];
}

?>
<?php
require_once 'config.php';
function authenticate_user($pdo,$email, $password) {
    $statement = $pdo->prepare("SELECT * FROM Users WHERE email = :email AND password = :password");
    $statement->execute(array(":email" => $email, ":password" => $password));
    $user = $statement->fetch(PDO::FETCH_ASSOC);
    return $user ? $user : false;
    
    // return $email === 'test@example.com' && $password === 'password' ? ['id' => 1] : false;
}
// echo authenticate_user($pdo,"rebai.ayman@gmail.com","ayman123");
?>
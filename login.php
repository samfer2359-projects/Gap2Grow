<?php
session_start();
require '../db.php';
$email = $_POST['email'];
$password = $_POST['password'];

$sql = "SELECT * FROM users WHERE email = :email";
$stmt = $pdo->prepare($sql);
$stmt->execute([':email' => $email]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && $password == $user['password']) {

    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['name'] = $user['name'];

    echo "Login successful";

} else {
    echo "Invalid email or password";
}
?>
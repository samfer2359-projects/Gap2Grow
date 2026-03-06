<?php
session_start();
require 'db.php';

$email = $_POST['email'];
$password = $_POST['password'];

$sql = "SELECT * FROM users WHERE email = :email";
$stmt = $pdo->prepare($sql);
$stmt->execute([':email' => $email]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($password, $user['password'])) {

    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['name'] = $user['name'];

    // Redirect to welcome page
    header("Location: welcome.php");
    exit();

} else {
    die("Invalid email or password");
}
?>
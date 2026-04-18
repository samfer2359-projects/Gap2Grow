<?php
session_start();
require 'db.php';

$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

if (!$email || !$password) {
    header("Location: login.html?error=invalid");
    exit();
}

// Fetch user
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
$stmt->execute([':email' => $email]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($password, $user['password'])) {

    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['name'] = $user['name'];

    header("Location: welcome.php");
    exit();

} else {
    header("Location: login.html?error=invalid&email=" . urlencode($email));
    exit();
}
?>
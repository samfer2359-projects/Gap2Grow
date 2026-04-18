<?php
require 'db.php';

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

if (!$name || !$email || !$password) {
    header("Location: signup.html?error=empty");
    exit();
}

// Check existing email
$stmt = $pdo->prepare("SELECT 1 FROM users WHERE email = :email");
$stmt->execute([':email' => $email]);

if ($stmt->fetch()) {
    header("Location: signup.html?error=exists&email=" . urlencode($email) . "&name=" . urlencode($name));
    exit();
}

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert user
$stmt = $pdo->prepare("
    INSERT INTO users (name, email, password)
    VALUES (:name, :email, :password)
");

$stmt->execute([
    ':name' => $name,
    ':email' => $email,
    ':password' => $hashed_password
]);

// Redirect
header("Location: login.html");
exit();
?>
<?php
require 'db.php';

$name = $_POST['name'];
$email = $_POST['email'];
$password = $_POST['password'];

// Hash password before saving
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Check if email already exists
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
$stmt->execute([':email' => $email]);

if ($stmt->rowCount() > 0) {
    die("Email already registered. Please login or use a different email.");
}

// Insert new user
$sql = "INSERT INTO users (name,email,password)
        VALUES (:name,:email,:password)";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':name' => $name,
    ':email' => $email,
    ':password' => $hashed_password
]);

// Redirect to login page after successful signup
header("Location: login.html");
exit();
?>
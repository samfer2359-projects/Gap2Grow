<?php
require '../db.php';
$name = $_POST['name'];
$email = $_POST['email'];
$password = $_POST['password'];

$sql = "INSERT INTO users (name,email,password)
        VALUES (:name,:email,:password)";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':name' => $name,
    ':email' => $email,
    ':password' => $password
]);

echo "Signup successful";
?>
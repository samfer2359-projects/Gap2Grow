<?php

$host = "localhost";
$dbname = "gap2grow";
$user = "postgres";
$password = "1111";

try {
    $pdo = new PDO("pgsql:host=localhost;dbname=gap2grow", "postgres", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>

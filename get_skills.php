<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user_id'])) {
    die("User not logged in");
}

$user_id = $_SESSION['user_id'];

$sql = "SELECT skill_id, skill_name, skill_type, proficiency
        FROM user_skills
        WHERE user_id = :user_id";

$stmt = $pdo->prepare($sql);
$stmt->execute([':user_id' => $user_id]);

$skills = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($skills);
?>
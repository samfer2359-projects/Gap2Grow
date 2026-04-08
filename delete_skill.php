<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Not logged in"]);
    exit();
}

$user_id = $_SESSION['user_id'];


$skill_id = $_POST['skill_id'] ?? null;

if (!$skill_id) {
    echo json_encode(["status" => "error", "message" => "Skill ID missing"]);
    exit();
}

$stmt = $pdo->prepare("
    DELETE FROM user_skills
    WHERE skill_id = :skill_id
    AND user_id = :user_id
");

$stmt->execute([
    ':skill_id' => $skill_id,
    ':user_id' => $user_id
]);

echo json_encode(["status" => "success"]);
?>
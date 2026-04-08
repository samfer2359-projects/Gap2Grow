<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status"=>"error","message"=>"Not logged in"]);
    exit();
}

$skill_id = $_POST['skill_id'] ?? null;
$status = $_POST['status'] ?? null;

if (!$skill_id || !$status) {
    echo json_encode(["status"=>"error","message"=>"Missing data"]);
    exit();
}

$sql = "UPDATE user_skills
        SET status = :status
        WHERE skill_id = :skill_id
        AND user_id = :user_id";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':status' => $status,
    ':skill_id' => $skill_id,
    ':user_id' => $_SESSION['user_id']
]);

echo json_encode(["status"=>"success"]);

?>
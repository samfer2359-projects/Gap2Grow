<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    die("User not logged in");
}

$user_id = $_SESSION['user_id'];
$skill_id = $_POST['skill_id'];

if (empty($skill_id)) {
    die("Skill ID required");
}

$sql = "DELETE FROM user_skills
        WHERE skill_id = :skill_id
        AND user_id = :user_id";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':skill_id' => $skill_id,
    ':user_id' => $user_id
]);

header("Location: _dashboard.php");
exit();
?>


<?php
session_start();
require_once "db.php";

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) die("Not logged in");

$skill = $_POST['skill_name'] ?? 'custom';
$title = $_POST['title'] ?? '';
$link = $_POST['link'] ?? '';

if (!$title || !$link) {
    die("Missing title or link");
}

$stmt = $pdo->prepare("
INSERT INTO recommendations
(user_id, skill_name, resource_type, resource_title, resource_link, difficulty, run_id)
VALUES
(:user_id, :skill, 'Custom', :title, :link, 'Beginner', :run_id)
");

$stmt->execute([
    ':user_id' => $user_id,
    ':skill' => $skill,
    ':title' => $title,
    ':link' => $link,
    ':run_id' => $_SESSION['last_run_id']
]);

echo "Added successfully";
?>
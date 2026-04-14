<?php
session_start();
require_once "db.php";

$id = $_POST['id'];
$title = $_POST['title'];
$link = $_POST['link'];

$stmt = $pdo->prepare("
UPDATE recommendations
SET resource_title = :title,
    resource_link = :link
WHERE recommendation_id = :id
AND user_id = :user_id
");

$stmt->execute([
    ':title' => $title,
    ':link' => $link,
    ':id' => $id,
    ':user_id' => $_SESSION['user_id']
]);

echo "Updated";
?>
<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user_id'])) {
    die("User not logged in");
}

$skill_id = $_POST['skill_id'];

if(empty($skill_id)){
    die("Skill ID required");
}

$sql = "DELETE FROM user_skills
        WHERE skill_id = :skill_id";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':skill_id' => $skill_id
]);

echo "Skill deleted successfully";
?>
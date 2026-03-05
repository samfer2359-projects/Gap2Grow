<?php
session_start();
require '../db.php';
if (!isset($_SESSION['user_id'])) {
    die("User not logged in");
}

$skill_id = $_POST['skill_id'];
$skill_type = $_POST['skill_type'];
$proficiency = $_POST['proficiency'];

if(empty($skill_id)){
    die("Skill ID required");
}

$sql = "UPDATE user_skills
        SET skill_type = :skill_type,
            proficiency = :proficiency
        WHERE skill_id = :skill_id";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':skill_type' => $skill_type,
    ':proficiency' => $proficiency,
    ':skill_id' => $skill_id
]);

echo "Skill updated successfully";
?>
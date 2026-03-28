<?php
session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id'])) {
    die("User not logged in");
}

$user_id = $_SESSION['user_id'];

$skill_name = $_POST['skill_name'];
$skill_type = $_POST['skill_type'];
$proficiency = $_POST['proficiency'];

if(empty($skill_name) || empty($skill_type) || empty($proficiency)){
    die("All fields required");
}

$sql = "INSERT INTO user_skills (user_id, skill_name, skill_type, proficiency)
        VALUES (:user_id, :skill_name, :skill_type, :proficiency)";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':user_id' => $user_id,
    ':skill_name' => $skill_name,
    ':skill_type' => $skill_type,
    ':proficiency' => $proficiency
]);

echo "Skill added successfully";
?>

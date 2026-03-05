<?php
session_start();
require '../db.php';
if (!isset($_SESSION['user_id'])) {
    die("User not logged in");
}

$user_id = $_SESSION['user_id'];
$job_id = $_POST['job_id'];

if(empty($job_id)){
    die("Job ID required");
}

$sql = "INSERT INTO learning_roadmaps (user_id, job_id)
        VALUES (:user_id, :job_id)";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':user_id' => $user_id,
    ':job_id' => $job_id
]);

echo "Job role saved successfully";
?>
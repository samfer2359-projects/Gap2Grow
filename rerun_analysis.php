<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Not logged in"]);
    exit();
}

$user_id = $_SESSION['user_id'];

// get user's target job
$stmt = $pdo->prepare("SELECT target_job FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$target_job = $stmt->fetchColumn();

if (!$target_job) {
    echo json_encode(["status" => "error", "message" => "No target job"]);
    exit();
}

// RUN PYTHON
$python = "C:\\Python314\\python.exe";
$module2 = __DIR__ . "\\module2.py";

$cmd = "\"$python\" \"$module2\" $user_id \"$target_job\" 2>&1";
$output = shell_exec($cmd);

echo json_encode(["status" => "success"]);
?>
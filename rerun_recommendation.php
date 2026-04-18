<?php
session_start();
require_once "db.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Not logged in"]);
    exit();
}

$user_id = $_SESSION['user_id'];

// Get target job
$stmt = $pdo->prepare("SELECT target_job FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$target_job = $stmt->fetchColumn() ?? '';

// Paths
$python = "C:\\Python314\\python.exe";
$module2 = __DIR__ . "\\module2.py";
$module3 = __DIR__ . "\\recommendation_engine.py";

$log_file = __DIR__ . "\\rerun_recommendations.log";
file_put_contents($log_file, "[".date('Y-m-d H:i:s')."] Starting rerun for user_id=$user_id\n", FILE_APPEND);

// Run module2.py (analysis)
$cmd2 = "\"$python\" \"$module2\" $user_id \"$target_job\" 2>&1";
exec($cmd2, $output2, $return2);
file_put_contents($log_file, "[".date('Y-m-d H:i:s')."] module2 output:\n" . implode("\n", $output2) . "\nReturn: $return2\n", FILE_APPEND);

// Run module3.py (recommendations) only if analysis succeeded
if ($return2 === 0) {
    $cmd3 = "\"$python\" \"$module3\" $user_id 2>&1";
    exec($cmd3, $output3, $return3);
    file_put_contents($log_file, "[".date('Y-m-d H:i:s')."] module3 output:\n" . implode("\n", $output3) . "\nReturn: $return3\n", FILE_APPEND);
} else {
    file_put_contents($log_file, "[".date('Y-m-d H:i:s')."] ERROR: module2 failed, skipping module3\n", FILE_APPEND);
}

// Get latest run_id after analysis
$stmt = $pdo->prepare("
    SELECT run_id 
    FROM skill_gap_results
    WHERE user_id = ?
    ORDER BY analyzed_at DESC
    LIMIT 1
");
$stmt->execute([$user_id]);

$latest_run_id = $stmt->fetchColumn();

if ($latest_run_id) {
    $_SESSION['last_run_id'] = $latest_run_id;
}

// Check if recommendations exist
$stmt = $pdo->prepare("SELECT COUNT(*) FROM recommendations WHERE user_id = ?");
$stmt->execute([$user_id]);
$rec_count = (int)$stmt->fetchColumn();

if ($rec_count > 0 && $return2 === 0 && $return3 === 0) {
    echo json_encode(["status" => "success", "message" => "Recommendations updated", "count" => $rec_count]);
} else {
    echo json_encode(["status" => "error", "message" => "Recommendations not updated"]);
}
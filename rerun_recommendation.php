<?php
session_start();
require_once "db.php";

header('Content-Type: application/json');


if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Not logged in"
    ]);
    exit();
}

$user_id = $_SESSION['user_id'];


$stmt = $pdo->prepare("SELECT target_job FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$target_job = $stmt->fetchColumn() ?? '';


$python = "C:\\Python314\\python.exe";
$module2 = __DIR__ . "\\module2.py";                  // Skill gap analysis
$module3 = __DIR__ . "\\recommendation_engine.py";   // Recommendations
$module4 = __DIR__ . "\\roadmap_engine.py";          // Roadmap

$log_file = __DIR__ . "\\rerun_recommendations.log";

function log_msg($msg) {
    global $log_file;
    file_put_contents(
        $log_file,
        "[" . date('Y-m-d H:i:s') . "] $msg\n",
        FILE_APPEND
    );
}

log_msg("=== START RERUN for user_id=$user_id ===");


$cmd2 = "\"$python\" \"$module2\" $user_id \"$target_job\" 2>&1";
exec($cmd2, $output2, $return2);

log_msg("module2 output:\n" . implode("\n", $output2));
log_msg("module2 return code: $return2");

if ($return2 !== 0) {
    log_msg(" module2 failed. Aborting pipeline.");

    echo json_encode([
        "status" => "error",
        "message" => "Skill analysis failed"
    ]);
    exit();
}


$stmt = $pdo->prepare("
    SELECT run_id 
    FROM skill_gap_results
    WHERE user_id = ?
    ORDER BY analyzed_at DESC
    LIMIT 1
");
$stmt->execute([$user_id]);

$run_id = $stmt->fetchColumn();

if (!$run_id) {
    log_msg(" No run_id found after analysis");

    echo json_encode([
        "status" => "error",
        "message" => "Run ID not generated"
    ]);
    exit();
}

$_SESSION['last_run_id'] = $run_id;

log_msg(" Using run_id = $run_id");


$pdo->prepare("DELETE FROM recommendations WHERE run_id = ?")
    ->execute([$run_id]);

$pdo->prepare("DELETE FROM learning_roadmaps WHERE user_id = ?")
    ->execute([$user_id]);


$cmd3 = "\"$python\" \"$module3\" $run_id 2>&1";
exec($cmd3, $output3, $return3);

log_msg("recommendation_engine output:\n" . implode("\n", $output3));
log_msg("recommendation_engine return code: $return3");

if ($return3 !== 0) {
    log_msg(" recommendation engine failed");

    echo json_encode([
        "status" => "error",
        "message" => "Recommendation generation failed"
    ]);
    exit();
}


$cmd4 = "\"$python\" \"$module4\" $run_id 2>&1";
exec($cmd4, $output4, $return4);

log_msg("roadmap_engine output:\n" . implode("\n", $output4));
log_msg("roadmap_engine return code: $return4");

if ($return4 !== 0) {
    log_msg(" roadmap engine failed");

    echo json_encode([
        "status" => "error",
        "message" => "Roadmap generation failed"
    ]);
    exit();
}


$stmt = $pdo->prepare("SELECT COUNT(*) FROM recommendations WHERE run_id = ?");
$stmt->execute([$run_id]);
$rec_count = (int)$stmt->fetchColumn();

log_msg(" Completed successfully. Recommendations count: $rec_count");


echo json_encode([
    "status" => "success",
    "message" => "Recommendations & roadmap updated",
    "run_id" => $run_id,
    "recommendations" => $rec_count
]);

log_msg("=== END RERUN ===\n");
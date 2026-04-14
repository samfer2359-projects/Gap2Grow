<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: welcome.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit("Invalid request");
}

$target_role = trim($_POST['target_role'] ?? '');
$skills = $_POST['skill_name'] ?? [];

if (!$target_role || empty($skills)) {
    exit("Missing input");
}

/* GET JOB */
$stmt = $pdo->prepare("SELECT job_id, job_title FROM job_roles WHERE job_title = :title");
$stmt->execute([':title' => $target_role]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    exit("Invalid job selected");
}

/* UPDATE USER TARGET */
$pdo->prepare("UPDATE users SET target_job = ? WHERE user_id = ?")
    ->execute([$job['job_title'], $user_id]);

$job_id = $job['job_id'];

/* SAVE SKILLS */
$pdo->beginTransaction();

$stmt = $pdo->prepare("
    INSERT INTO user_skills (user_id, skill_name, skill_type, proficiency)
    VALUES (:uid, :name, 'Education', 3)
    ON CONFLICT (user_id, skill_name)
    DO UPDATE SET proficiency = EXCLUDED.proficiency
");

foreach ($skills as $s) {
    $stmt->execute([
        ':uid' => $user_id,
        ':name' => strtolower(trim($s))
    ]);
}

$pdo->commit();

/* RUN MODULE 2 */
$python = "C:\\Python314\\python.exe";
$module2 = __DIR__ . "\\module2.py";

$cmd = "\"$python\" \"$module2\" $user_id $job_id 2>&1";
$output = shell_exec($cmd);

$result = json_decode(trim($output), true);

if (!$result || $result["status"] !== "success") {
    exit("MODULE2 FAILED:\n" . $output);
}

$run_id = $result['run_id'];

/* STORE RUN */
$_SESSION['last_run_id'] = $run_id;
$_SESSION['current_job_id'] = $job_id;

/* RUN MODULE 3 */
$module3 = __DIR__ . "\\recommendation_engine.py";

$module4 = __DIR__ . "\\roadmap_engine.py";

$cmd3 = "\"$python\" \"$module4\" $run_id 2>&1";
$output3 = shell_exec($cmd3);

$result3 = json_decode(trim($output3), true);

if (!$result3 || $result3["status"] !== "success") {
    exit("MODULE4 FAILED:\n" . $output3);
}

$cmd2 = "\"$python\" \"$module3\" $run_id 2>&1";
$output2 = shell_exec($cmd2);

$result2 = json_decode(trim($output2), true);

if (!$result2 || $result2["status"] !== "success") {
    exit("MODULE3 FAILED:\n" . $output2);
}

header("Location: _recommendations.php");
exit();
?>
<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: welcome.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $skill_names = $_POST['skill_name'] ?? [];
    $skill_type = $_POST['skill_type'] ?? 'Education';
    $proficiency = (int) ($_POST['proficiency'] ?? 1);

    $skills = [];
    foreach ($skill_names as $name) {
        $skills[] = [
            'name' => $name,
            'type' => $skill_type,
            'level' => $proficiency
        ];
    }

    $target_job = $_POST['target_role'] ?? '';
    if (empty($target_job)) {
        die("Error: target job not selected.");
    }

    try {
        $pdo->beginTransaction();

        $skill_stmt = $pdo->prepare("
            INSERT INTO user_skills (user_id, skill_name, skill_type, proficiency)
            VALUES (:user_id, :skill_name, :skill_type, :proficiency)
            ON CONFLICT (user_id, skill_name) 
            DO UPDATE SET proficiency = EXCLUDED.proficiency
        ");

        foreach ($skills as $skill) {
            $skill_stmt->execute([
                ':user_id' => $user_id,
                ':skill_name' => $skill['name'],
                ':skill_type' => $skill['type'],
                ':proficiency' => $skill['level']
            ]);
        }

        $job_stmt = $pdo->prepare("
            UPDATE users SET target_job = :target_job WHERE user_id = :user_id
        ");
        $job_stmt->execute([
            ':target_job' => $target_job,
            ':user_id' => $user_id
        ]);

        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        die("Database error: " . $e->getMessage());
    }

    ob_start();

    $python_path = 'C:\\Python314\\python.exe';
    $module2_path = __DIR__ . '\\module2.py';
    $module3_path = __DIR__ . '\\recommendation_engine.py';

    $module2_cmd = "\"$python_path\" \"$module2_path\" $user_id \"$target_job\" 2>&1";
    $module3_cmd = "\"$python_path\" \"$module3_path\" $user_id 2>&1";

    $module2_output = shell_exec($module2_cmd);
    $module3_output = shell_exec($module3_cmd);

    file_put_contents("debug_module2.log", $module2_output);
    file_put_contents("debug_module3.log", $module3_output);

    // Parse only the last line of output (JSON) to avoid debug text
    $module2_lines = explode("\n", trim($module2_output));
    $module2_data = json_decode(end($module2_lines), true);

    $module3_lines = explode("\n", trim($module3_output));
    $module3_data = json_decode(end($module3_lines), true);

    if (
        isset($module2_data['status'], $module3_data['status']) &&
        $module2_data['status'] === 'success' &&
        $module3_data['status'] === 'success'
    ) {
        ob_end_clean();
        header("Location: _recommendations.php");
        exit();
    } else {
        ob_end_flush();
        echo "<h2>Error running analysis modules</h2>";
        echo "<pre>Module 2: " . htmlentities($module2_output) . "</pre>";
        echo "<pre>Module 3: " . htmlentities($module3_output) . "</pre>";
        exit();
    }
}
?>
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

require_once "db.php";

$user_id = $_SESSION['user_id'];

// Fetch recommendations for user
$stmt = $pdo->prepare("
    SELECT recommendation_id,
           skill_name,
           resource_type,
           resource_title,
           resource_link,
           difficulty
    FROM recommendations
    WHERE user_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$user_id]);
$recommendations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch latest learning roadmap for user
$stmt = $pdo->prepare("
    SELECT roadmap_text
    FROM learning_roadmaps
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 1
");
$stmt->execute([$user_id]);
$roadmap = $stmt->fetchColumn();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Learning Recommendations | Gap2Grow</title>
    <link rel="stylesheet" href="_style.css" />
</head>
<body>

<nav class="navbar">
    <div class="logo">Gap2Grow</div>
    <ul class="nav-links">
        <li><a href="welcome.html">Home</a></li>
        <li><a href="education.html">Education Module</a></li>
        <li><a href="progress.html">My Progress</a></li>
        <li><a href="about.html">About</a></li>
    </ul>
    <div class="user-info">
        <span>Welcome, User!</span>
        <button class="logout-btn" onclick="logout()">Logout</button>
    </div>
</nav>

<main class="container">

    <div class="welcome-box">
        <h1>📘 My Learning Recommendations</h1>
        <p>
            Based on your skill gap analysis, here are your personalized learning resources to improve your career readiness.
        </p>
    </div>

    <?php if ($roadmap): ?>
        <div class="card roadmap-card">
            <h3>🗺 4-Week Learning Roadmap</h3>
            <div style="line-height: 1.6; font-size: 16px;">
                <?= nl2br(htmlspecialchars(trim($roadmap))) ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="card recommendations-card">
        <?php if (count($recommendations) > 0): ?>
            <table class="recommendations-table" cellspacing="0" cellpadding="0">
                <thead>
                    <tr>
                        <th>Skill</th>
                        <th>Platform</th>
                        <th>Resource</th>
                        <th>Difficulty</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recommendations as $rec): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($rec['skill_name']) ?></strong></td>
                            <td><?= htmlspecialchars($rec['resource_type']) ?></td>
                            <td><?= htmlspecialchars($rec['resource_title']) ?></td>
                            <td><?= htmlspecialchars($rec['difficulty']) ?></td>
                            <td>
                                <a class="btn primary-btn" href="<?= htmlspecialchars($rec['resource_link']) ?>" target="_blank" rel="noopener noreferrer">
                                    Start Learning
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align:center; padding:30px; font-weight:600; color:#ef4444;">
                🚫 No recommendations available yet.<br>
                Please complete skill gap analysis first.
            </p>
        <?php endif; ?>
    </div>

</main>

<footer class="main-footer">
    © 2026 Gap2Grow | Turning Potential into Progress 🌱
</footer>

<script>
function logout() {
    // Example logout redirect - replace with your logout PHP script
    window.location.href = 'logout.php';
}
</script>

</body>
</html>

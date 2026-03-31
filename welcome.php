<?php
session_start();
$username = isset($_SESSION['name']) ? $_SESSION['name'] : "";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Gap2Grow</title>
    <link rel="stylesheet" href="welcome.css">
</head>

<body>

<nav>
    <div class="logo">Gap2Grow</div>

    <ul class="nav-links">
        <li><a href="welcome.php">Home</a></li>
        <li><a href="_dashboard.php">Dashboard</a></li>
        <li><a href="_recommendations.php">My Progress</a></li>
        <li><a href="about.html">About</a></li>
    </ul>

    

    <div class="user-info">
        <?php if(isset($_SESSION['user_id'])): ?>
            <span>Welcome, <?= htmlspecialchars($username) ?>!</span>
            <a href="logout.php"><button class="logout-btn">Logout</button></a>
        <?php else: ?>
            <span>Welcome, Guest!</span>
            <a href="login.html"><button class="logout-btn">Login</button></a>
        <?php endif; ?>
    </div>
</nav>

<main class="welcome-section">

    <div class="welcome-box">
        <h1>Welcome to Gap2Grow 🌱</h1>
        <p class="tagline">
            Bridge the gap between where you are and where you want to be.
        </p>

        <div class="welcome-buttons">

            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="skill_form.html" class="primary-btn">Start Learning</a>
                <a href="_recommendations.php" class="primary-btn">View Learning Plan</a>
                <a href="_dashboard.php" class="secondary-btn">📈 Track Progress</a>
            <?php else: ?>
                <a href="login.html" class="primary-btn">Start Learning</a>
            <?php endif; ?>

        </div>
    </div>

    <div class="info-section">
        <div class="info-card">
            <h3>🎯 Set Goals</h3>
            <p>Define clear learning objectives and achieve them step by step.</p>
        </div>

        <div class="info-card">
            <h3>📊 Monitor Growth</h3>
            <p>Track your development and stay consistent every day.</p>
        </div>

        <div class="info-card">
            <h3>🚀 Improve Skills</h3>
            <p>Access structured resources designed for smart progress.</p>
        </div>
    </div>

</main>

<footer class="main-footer">
    © 2026 Gap2Grow | Turning Potential into Progress 🌱
</footer>

</body>
</html>
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

require_once "db.php";
?>



   //FETCH CAREER READINESS SCORE

$stmt = $pdo->prepare("
    SELECT gap_score
    FROM skill_gap_results
    WHERE user_id = ?
    ORDER BY analyzed_at DESC
    LIMIT 1
");
$stmt->execute([$user_id]);

$readiness = (int)($stmt->fetchColumn() ?? 0);


   // FETCH PROGRESS SUMMARY
$stmt = $pdo->prepare("
    SELECT
        COUNT(*) FILTER (WHERE status = 'Completed')   AS completed,
        COUNT(*) FILTER (WHERE status = 'In Progress') AS in_progress,
        COUNT(*) FILTER (WHERE status = 'Pending')     AS pending,
        COUNT(*) AS total
    FROM user_progress
    WHERE user_id = ?
");
$stmt->execute([$user_id]);

$summary = $stmt->fetch(PDO::FETCH_ASSOC);

$completed   = (int)($summary['completed'] ?? 0);
$in_progress = (int)($summary['in_progress'] ?? 0);
$pending     = (int)($summary['pending'] ?? 0);
$total       = (int)($summary['total'] ?? 0);

$completion_percent = $total > 0
    ? round(($completed / $total) * 100)
    : 0;



   // FETCH SKILL PROGRESS

$stmt = $pdo->prepare("
    SELECT r.skill_name, up.progress_percent
    FROM user_progress up
    JOIN recommendations r
        ON up.recommendation_id = r.recommendation_id
    WHERE up.user_id = ?
");
$stmt->execute([$user_id]);

$skills = $stmt->fetchAll(PDO::FETCH_ASSOC);

$labels = [];
$values = [];

foreach ($skills as $row) {
    $labels[] = $row['skill_name'];
    $values[] = (int)$row['progress_percent'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Gap2Grow Dashboard</title>
    <link rel="stylesheet" href="_style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>


<div class="header">
    <h1>Gap2Grow</h1>
    <p>Learning Progress Dashboard</p>
</div>

<div class="container">

    
    <div class="card intro-card">
        <h3>📊 My Progress Dashboard</h3>
        <p>
            This dashboard visually represents your learning progress
            across different skills and tracks your journey toward
            career readiness.
        </p>
    </div>

    <div class="grid">

  
        <div class="card">
            <h4>Overall Skill Completion</h4>
            <div class="kpi-number">
                <?= $completion_percent ?>%
            </div>
        </div>

      
        <div class="card">
            <h4>Career Readiness</h4>
            <div class="chart-box">
                <canvas id="readinessChart"></canvas>
            </div>
        </div>

        
        <div class="card">
            <h4>Status Distribution</h4>
            <div class="chart-box">
                <canvas id="statusChart"></canvas>
            </div>
        </div>

      
        <div class="card">
            <h4>Skill Progress</h4>
            <div class="chart-box">
                <canvas id="skillChart"></canvas>
            </div>
        </div>

    </div>
</div>

<div class="footer">
    © 2026 Gap2Grow | Skill Gap Analysis Platform
</div>


<script>

const readinessValue = <?= $readiness ?>;


new Chart(document.getElementById('readinessChart'), {
    type: 'doughnut',
    data: {
        labels: ['Ready', 'Gap'],
        datasets: [{
            data: [readinessValue, 100 - readinessValue],
            backgroundColor: ['#16a34a', '#e5e7eb'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '75%',
        plugins: {
            legend: { display: false },
            tooltip: { enabled: false }
        }
    },
    plugins: [{
        id: 'centerText',
        beforeDraw(chart) {
            const { width, height, ctx } = chart;

            ctx.save();
            ctx.font = `600 ${height / 5}px Segoe UI`;
            ctx.fillStyle = "#111827";
            ctx.textAlign = "center";
            ctx.textBaseline = "middle";
            ctx.fillText(readinessValue + "%", width / 2, height / 2);
        }
    }]
});



new Chart(document.getElementById('statusChart'), {
    type: 'pie',
    data: {
        labels: ['Completed', 'In Progress', 'Pending'],
        datasets: [{
            data: [<?= $completed ?>, <?= $in_progress ?>, <?= $pending ?>],
            backgroundColor: ['#16a34a', '#f59e0b', '#ef4444']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});



new Chart(document.getElementById('skillChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [{
            label: 'Progress %',
            data: <?= json_encode($values) ?>,
            backgroundColor: '#6366f1',
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                max: 100,
                ticks: { stepSize: 20 }
            }
        },
        plugins: {
            legend: { display: false }
        }
    }
});

</script>

</body>
</html>

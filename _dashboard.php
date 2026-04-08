<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['name'] ?? "User";

// CHECK IF USER HAS SKILLS
$stmt = $pdo->prepare("SELECT COUNT(*) FROM user_skills WHERE user_id = ?");
$stmt->execute([$user_id]);
if ($stmt->fetchColumn() == 0) {
    header("Location: skill_form.html");
    exit();
}

// GET LATEST READINESS SCORE
$stmt = $pdo->prepare("
    SELECT gap_score FROM skill_gap_results
    WHERE user_id = ?
    ORDER BY analyzed_at DESC LIMIT 1
");
$stmt->execute([$user_id]);
$readiness = (int)($stmt->fetchColumn() ?? 0);

// GET PROGRESS SUMMARY
$stmt = $pdo->prepare("
    SELECT
        SUM(CASE WHEN status='Completed' THEN 1 ELSE 0 END) AS completed,
        SUM(CASE WHEN status='In Progress' THEN 1 ELSE 0 END) AS in_progress,
        SUM(CASE WHEN status='Pending' OR status='Incomplete' THEN 1 ELSE 0 END) AS pending,
        COUNT(*) AS total
    FROM user_skills WHERE user_id = ?
");
$stmt->execute([$user_id]);
$summary = $stmt->fetch(PDO::FETCH_ASSOC);

$completed = (int)($summary['completed'] ?? 0);
$in_progress = (int)($summary['in_progress'] ?? 0);
$pending = (int)($summary['pending'] ?? 0);
$total = (int)($summary['total'] ?? 0);

$completion_percent = $total > 0 ? round(($completed / $total) * 100) : 0;

// GET SKILL PROGRESS DATA
$stmt = $pdo->prepare("
    SELECT 
        skill_id,
        skill_name,
        skill_type,
        status,
        proficiency,
        CASE 
            WHEN status='Incomplete' THEN 0
            WHEN status='In Progress' THEN 50
            WHEN status='Completed' THEN 100
            ELSE 0
        END AS progress_percent
    FROM user_skills
    WHERE user_id = ?
    ORDER BY skill_name
");
$stmt->execute([$user_id]);
$skills = $stmt->fetchAll(PDO::FETCH_ASSOC);

$skill_labels = array_column($skills, 'skill_name');
$skill_values = array_map(fn($s) => (int)$s['progress_percent'], $skills);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <link rel="stylesheet" href="_style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }
        .chart-container {
            height: 250px;
            max-width: 300px;
            margin: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px;
            text-align: center;
        }
        th {
            background: #f3f4f6;
        }
        button {
            padding: 6px 10px;
            cursor: pointer;
        }
        form input, form select {
            margin: 5px;
            padding: 5px;
        }
    </style>
</head>
<body>
<nav class="navbar">
    <div class="logo">Gap2Grow</div>
    <ul class="nav-links">
        <li><a href="welcome.php">Home</a></li>
        <li><a href="_dashboard.php">Dashboard</a></li>
        <li><a href="_recommendations.php">My Progress</a></li>
        <li><a href="about.html">About</a></li>
    </ul>
    <div>
        Welcome, <?= htmlspecialchars($username) ?>
        <a href="logout.php"><button>Logout</button></a>
    </div>
</nav>

<div class="container">
    <h2>📊 Dashboard</h2>
    <div class="card">
        <h3>Overall Completion: <?= $completion_percent ?>%</h3>
    </div>

    <div class="grid">
        <div class="card">
            <h4>Career Readiness</h4>
            <div class="chart-container">
                <canvas id="readinessChart"></canvas>
            </div>
        </div>

        <div class="card">
            <h4>Status Distribution</h4>
            <div class="chart-container">
                <canvas id="statusChart"></canvas>
            </div>
        </div>

        <div class="card">
            <h4>Skill Progress</h4>
            <div class="chart-container">
                <canvas id="skillChart"></canvas>
            </div>
        </div>
    </div>

    <div class="card">
        <h3>🛠 Manage Skills</h3>
        <button onclick="toggleForm()">➕ Add New Skill</button>

        <div id="skillFormBox" style="display:none;">
            <h4>Add New Skill</h4>
            <form id="addSkillForm">
                <input type="text" name="skill_name" placeholder="Skill Name" required>
                <select name="skill_type">
                    <option value="Education">Education</option>
                    <option value="Job">Job</option>
                    <option value="Experience">Experience</option>
                </select>
                <select name="proficiency">
                    <option value="1">Beginner</option>
                    <option value="2">Basic</option>
                    <option value="3">Intermediate</option>
                    <option value="4">Advanced</option>
                    <option value="5">Expert</option>
                </select>
                <select name="status">
                    <option value="Incomplete">Incomplete</option>
                    <option value="In Progress">In Progress</option>
                    <option value="Completed">Completed</option>
                </select>
                <button type="submit">Add Skill</button>
            </form>
        </div>

        <?php if ($skills): ?>
            <table border="1">
                <tr>
                    <th>Skill</th>
                    <th>Type</th>
                
                    <th>Status</th>
                    <th>Progress %</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($skills as $skill): ?>
                    <tr>
                        <td><?= htmlspecialchars($skill['skill_name']) ?></td>
                        <td><?= htmlspecialchars($skill['skill_type']) ?></td>
                        
                        <td>
                            <select onchange="updateStatus(<?= $skill['skill_id'] ?>, this.value)">
                                <option value="Incomplete" <?= $skill['status']=='Incomplete'?'selected':'' ?>>Incomplete</option>
                                <option value="In Progress" <?= $skill['status']=='In Progress'?'selected':'' ?>>In Progress</option>
                                <option value="Completed" <?= $skill['status']=='Completed'?'selected':'' ?>>Completed</option>
                            </select>
                        </td>
                        <td><?= $skill['progress_percent'] ?>%</td>
                        <td><button onclick="deleteSkill(<?= $skill['skill_id'] ?>)">Delete</button></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p>No skills found</p>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleForm() {
    let box = document.getElementById("skillFormBox");
    box.style.display = box.style.display === "none" ? "block" : "none";
}

document.getElementById("addSkillForm").addEventListener("submit", function(e){
    e.preventDefault();
    let formData = new FormData(this);
    fetch("add_skill.php", { method: "POST", body: new URLSearchParams(formData) })
        .then(() => fetch("rerun_recommendations.php"))
        .then(() => location.reload());
});

function updateStatus(id, status) {
    fetch("update_status.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "skill_id=" + id + "&status=" + status
    })
    .then(() => fetch("rerun_recommendations.php"))
    .then(() => location.reload());
}

function deleteSkill(id) {
    if (!confirm("Delete this skill?")) return;
    fetch("delete_skill.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "skill_id=" + id
    })
    .then(() => fetch("rerun_recommendations.php"))
    .then(() => location.reload());
}

// CHARTS
new Chart(document.getElementById('readinessChart'), {
    type: 'doughnut',
    data: { labels: ['Ready', 'Gap'], datasets: [{ data: [<?= $readiness ?>, <?= 100-$readiness ?>] }] }
});

new Chart(document.getElementById('statusChart'), {
    type: 'pie',
    data: { labels: ['Completed', 'In Progress', 'Pending'], datasets: [{ data: [<?= $completed ?>, <?= $in_progress ?>, <?= $pending ?>] }] }
});

new Chart(document.getElementById('skillChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($skill_labels) ?>,
        datasets: [{
            label: 'Progress %',
            data: <?= json_encode($skill_values) ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.6)'
        }]
    },
    options: { scales: { y: { beginAtZero: true, max: 100 } } }
});
</script>
</body>
</html>
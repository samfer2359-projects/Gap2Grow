<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['name'] ?? "User";

$run_id = $_SESSION['last_run_id'] ?? null;

if (!$run_id) {
    die("<p style='color:red;text-align:center;padding:20px;'>
        No analysis run found. Please complete skill analysis again.
    </p>");
}

/* GET JOB */
$stmt = $pdo->prepare("SELECT job_id FROM skill_gap_results WHERE run_id = ?");
$stmt->execute([$run_id]);
$job_id = $stmt->fetchColumn();

if (!$job_id) {
    die("<p style='color:red;text-align:center;padding:20px;'>
        Invalid run data. Please re-run analysis.
    </p>");
}

/* ROADMAP */
$stmt = $pdo->prepare("
    SELECT roadmap_text
    FROM learning_roadmaps
    WHERE user_id = ? AND job_id = ?
    ORDER BY created_at DESC
    LIMIT 1
");
$stmt->execute([$user_id, $job_id]);

$roadmapRaw = $stmt->fetchColumn();
$roadmapData = $roadmapRaw ? json_decode($roadmapRaw, true) : null;

/* RECOMMENDATIONS */
$stmt = $pdo->prepare("
    SELECT *
    FROM recommendations
    WHERE run_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$run_id]);

$recommendations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Learning Recommendations</title>
    <link rel="stylesheet" href="_style.css">
</head>

<body>

<!-- NAVBAR -->
<nav class="navbar">
    <div class="logo">Gap2Grow</div>

    <ul class="nav-links">
        <li><a href="welcome.php">Home</a></li>
        <li><a href="_dashboard.php">Dashboard</a></li>
        <li><a href="_recommendations.php">My Progress</a></li>
        <li><a href="about.html">About</a></li>
    </ul>

    <div class="user-info">
        <span>Welcome, <?= htmlspecialchars($username) ?></span>
        <a href="logout.php"><button class="logout-btn">Logout</button></a>
    </div>
</nav>

<main class="container">

<div class="welcome-box">
    <h1>📘 My Learning Recommendations</h1>
    <p>Your personalized roadmap and resources.</p>
</div>

<!-- ROADMAP  -->
<?php if ($roadmapData): ?>
<div class="card roadmap-card">

    <h3>🗺 Learning Roadmap</h3>

    <h4>🎯 <?= htmlspecialchars($roadmapData['job_title'] ?? '') ?></h4>
    <p><b>Readiness:</b> <?= htmlspecialchars($roadmapData['readiness'] ?? 0) ?>%</p>

    <?php foreach ($roadmapData['weeks'] ?? [] as $w): ?>
        <div style="margin-top:10px;">
            <b>Week <?= htmlspecialchars($w['week']) ?> - <?= htmlspecialchars($w['focus']) ?></b>
            <ul>
                <?php foreach ($w['tasks'] as $t): ?>
                    <li><?= htmlspecialchars($t) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endforeach; ?>

    <?php if (!empty($roadmapData['final']['tasks'])): ?>
        <h4>🚀 Final Phase</h4>
        <ul>
            <?php foreach ($roadmapData['final']['tasks'] as $t): ?>
                <li><?= htmlspecialchars($t) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

</div>
<?php endif; ?>


<!--  RESOURCES  -->
<div class="card recommendations-card">

<div style="display:flex; justify-content:space-between; align-items:center;">
    <h3>📚 Learning Resources</h3>

    <button class="primary-btn" onclick="openAddModal()">
        + Add Resource
    </button>
</div>

<br>

<?php if (!empty($recommendations)): ?>

<table class="recommendations-table">
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
            <td><?= htmlspecialchars($rec['skill_name']) ?></td>
            <td><?= htmlspecialchars($rec['resource_type']) ?></td>
            <td><?= htmlspecialchars($rec['resource_title']) ?></td>
            <td><?= htmlspecialchars($rec['difficulty']) ?></td>

            <td>
                <a class="primary-btn"
                   href="<?= htmlspecialchars($rec['resource_link']) ?>"
                   target="_blank">
                   Start
                </a>
                &nbsp;&nbsp;
                <button class="primary-btn"
                    onclick="openEditModal(
                        <?= $rec['recommendation_id'] ?>,
                        '<?= htmlspecialchars($rec['resource_title'], ENT_QUOTES) ?>',
                        '<?= htmlspecialchars($rec['resource_link'], ENT_QUOTES) ?>'
                    )">
                    Edit
                </button>
            </td>
        </tr>
    <?php endforeach; ?>

    </tbody>
</table>

<?php else: ?>
<p style="text-align:center;color:red;">
    No resources found. Please re-run analysis.
</p>
<?php endif; ?>

</div>

</main>

<!--  MODAL  -->
<div id="modal" style="display:none; position:fixed; top:20%; left:35%; background:white; padding:20px; border:1px solid #ccc; width:300px;">

    <input type="hidden" id="rec_id">

    <label>Title</label><br>
    <input id="title"><br><br>

    <label>Link</label><br>
    <input id="link"><br><br>

    <label>Skill (optional)</label><br>
    <input id="skill"><br><br>

    <button onclick="save()">Save</button>
    <button onclick="closeModal()">Cancel</button>
</div>

<script>

function openAddModal(){
    document.getElementById("rec_id").value = "";
    document.getElementById("title").value = "";
    document.getElementById("link").value = "";
    document.getElementById("skill").value = "";
    document.getElementById("modal").style.display = "block";
}

function openEditModal(id,title,link){
    document.getElementById("rec_id").value = id;
    document.getElementById("title").value = title;
    document.getElementById("link").value = link;
    document.getElementById("modal").style.display = "block";
}

function closeModal(){
    document.getElementById("modal").style.display = "none";
}

function save(){

    let id = document.getElementById("rec_id").value;
    let title = document.getElementById("title").value;
    let link = document.getElementById("link").value;
    let skill = document.getElementById("skill").value;

    let url = id ? "update_recommendation.php" : "add_recommendation.php";

    let body = `id=${id}`
        + `&title=${encodeURIComponent(title)}`
        + `&link=${encodeURIComponent(link)}`
        + `&skill_name=${encodeURIComponent(skill)}`;

    fetch(url, {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: body
    })
    .then(r => r.text())
    .then(d => {
        alert(d);
        location.reload();
    });
}

</script>

</body>
</html>
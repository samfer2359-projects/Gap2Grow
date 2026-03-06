<?php

$conn = pg_connect("host=localhost dbname=gap2grow user=postgres password=root");

$result = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $user_id = $_POST["user_id"];
    $job_title = $_POST["job_title"];

    // Run Python Script
    $command = "python module2.py $user_id \"$job_title\"";
    shell_exec($command);
    shell_exec("python recommendation_engine.py $user_id");

    // Fetch latest result
    $query = "
        SELECT *
        FROM skill_gap_results
        WHERE user_id = $1
        ORDER BY analyzed_at DESC
        LIMIT 1
    ";

    $res = pg_query_params($conn, $query, array($user_id));

    if ($res) {
        $result = pg_fetch_assoc($res);
    }
}

?>

<!DOCTYPE html>
<html>
<head>
<title>Gap2Grow - Skill Gap Analysis</title>
<link rel="stylesheet" href="analysis.css">
</head>

<body>

<div class="container">

<h1>Skill Gap Analysis</h1>

<div class="card">

<form method="POST">

<label>User ID</label>
<input type="number" name="user_id" required>

<label>Select Job Role</label>
<select name="job_title">

<option value="Data Analyst">Data Analyst</option>
<option value="Python Developer">Python Developer</option>
<option value="Web Developer">Web Developer</option>

</select>

<button type="submit">Run Skill Gap Analysis</button>

</form>

</div>


<?php if($result){ ?>

<?php

$matched = json_decode($result['matched_skills'], true);
$missing = json_decode($result['missing_skills'], true);
$score = $result['gap_score'];

?>

<div class="card results">

<h2>Analysis Results</h2>

<div class="skills">

<div class="skill-box">

<h3>Matched Skills</h3>

<ul>

<?php
if($matched){
foreach($matched as $skill){
echo "<li class='match'>✔ $skill</li>";
}
}
?>

</ul>

</div>


<div class="skill-box">

<h3>Missing Skills</h3>

<ul>

<?php
if($missing){
foreach($missing as $skill){
echo "<li class='missing'>✘ $skill</li>";
}
}
?>

</ul>

</div>

</div>


<h3>Career Readiness Score</h3>

<div class="progress-bar">

<div class="progress" style="width: <?php echo $score; ?>%;">
<?php echo $score; ?>%
</div>

</div>

</div>

<?php } ?>


</div>

</body>
</html>
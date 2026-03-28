<?php 
// Adapted from class code, debugged with ELM (GPT 5.2), https://elm.edina.ac.uk/elm-new
// Code to display results page based on job ID

session_start();
require_once 'set_cookies.php';
require_once 'login.php';

// Base dir for pretty URLs
$BASE = '/~s2883992/website';

// Verifying required parameters exist
// User hash
$user_hash = $_SESSION['user_hash'] ?? '';
if ($user_hash === '') {
	die("Missing user_hash");
}
// jid from GET
$jid = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
if ($jid <= 0) {
	http_response_code(400); 
	die("Missing job_id"); 
}

// DB connection, adapted from class code
try {
	$dsn = "mysql:host=127.0.0.1;dbname=$database;charset=utf8mb4";
	$conn = new PDO($dsn, $username, $password);
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(Throwable $e) {
	http_response_code(500);
	die("Database connection failed");
}

// Retrieving job and query, verifying user
try {
	$stmt = $conn->prepare("
	SELECT jobs.job_id, jobs.status, jobs.error_message, jobs.query_id, jobs.job_params,
	queries.protein_family, queries.taxon
	FROM jobs
	JOIN queries ON queries.query_id = jobs.query_id
	WHERE jobs.job_id = ?
	AND (jobs.user_hash = ? OR jobs.is_example = 1)
	LIMIT 1
	");
	$stmt->execute([$jid, $user_hash]);
	$job = $stmt->fetch(PDO::FETCH_ASSOC);
	// Making sure job is not empty, outputing 404 if is
	if (!$job) { 
		http_response_code(404);
		require __DIR__ . '/not_found.php';
		die();
	}
} catch (Throwable $e) {
	http_response_code(500);
	die("Could not retrieve job and query.");
}

// Making sure job not pending, otherwise redirecting to loading page
if ($job['status'] === 'pending') {
	header("Location: " . $BASE . "/loading/" . (int)$jid);
	die();
}

echo <<<_HTML
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<link rel="stylesheet" href="/~s2883992/website/styles.css" />
<title>Results</title>
</head>
<body>
_HTML;

include 'cookies.html';
include 'menuf.php';

// Sticky navigation menu on the left
echo <<<_NAV
<div class="page-shell">
<aside class="page-side-nav">
<h2>On this Page</h2>
<ul>
<li><a href="#query_param">Query Parameters</a></li>
<li><a href="#plotcon_res">Plotcon Results</a></li>
<li><a href="#summary">Summary Statistics</a></li>
<li><a href="#files">Downloads</a></li>
<li><a href="#alignment_ajax">Alignment Overview</a></li>
<li><a href="#motif_ajax">Motif Overview</a></li>
<li><a href="#">Back to Top</a></li>
</ul>
</aside>
<main class="page-main">
<header class="page-title">
<h1>Results</h1>
</header>
<hr />
_NAV;

// Rendering results using the rendering script
require_once 'results_content.php';
render_results_content($conn, $job, $jid);

echo <<<_HTML3
</div>
</body>
</html>
_HTML3;
?>

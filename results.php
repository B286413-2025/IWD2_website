<?php // Adapted from class code, debugged with ELM (GPT 5.2), https://elm.edina.ac.uk/elm-new
// Code to display results page based on job ID

session_start();
require_once 'set_cookies.php';
require_once 'login.php';

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
	// Making sure job is not empty
	if (!$job) { 
		http_response_code(404); 
		die("Not found"); 
	}
} catch (Throwable $e) {
	http_response_code(500);
	die("Could not retrieve job and query.");
}

// Making sure job not pending, otherwise redirecting to loading page
if ($job['status'] === 'pending') {
	header("Location: loading_page.php?job_id=" . (int)$jid);
	die();
}

echo <<<_HTML
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<title>Results</title>
</head>
<body>
_HTML;

include 'menuf.php';

// Navigation menu
echo <<<_NAV
<header>
<h2>Results</h2>
<nav aria-label="primary-navigation">
<ul>
<li><a href="#query_param">Query Parameters</a></li>
<li><a href="#plotcon_res">Plotcon Results</a></li>
<li><a href="#summary">Summary Statistics</a></li>
<li><a href="#files">Text Files</a></li>
<li><a href="#alignment_ajax">Alignment Overview</a></li>
<li><a href="#motif_ajax">Motif Overview</a></li>
<li><a href="query.php" target="_blank">New Query</a></li>
</ul>
</nav>
</header>
<hr />
_NAV;

require_once 'results_content.php';
render_results_content($conn, $job, $jid);

// To submit a new query
echo <<<_HTML3
<p id='new_query'><a href='query.php' target="_blank">New query</a></p>
</body>
</html>
_HTML3;
?>

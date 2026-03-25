<?php // Debugged with ELM (GPT 5.2), https://elm.edina.ac.uk/elm-new

// Previous results page - list jobs belonging to current user

session_start();
require_once 'set_cookies.php';
require_once 'login.php';

// Checking user_hash
$user_hash = $_SESSION['user_hash'] ?? '';
if ($user_hash === '') {
	http_response_code(500);
	die("Missing user_hash");
}

// MySQL connection, adapted from class code
try {
	$dsn = "mysql:host=127.0.0.1;dbname=$database;charset=utf8mb4";
	$conn = new PDO($dsn, $username, $password);
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Throwable $e) {
	http_response_code(500);
	die("Database connection failed");
}

// Retrieve previous jobs for the current user
try {
	// Looking for jobs details (id, date, status, error messages, parameters)
	// and query details (protein and taxon)
	// Orderng results by recency
	$stmt = $conn->prepare("
	SELECT
	jobs.job_id,
	jobs.job_date,
	jobs.status,
	jobs.error_message,
	jobs.job_params,
	queries.protein_family,
	queries.taxon
	FROM jobs
	JOIN queries ON queries.query_id = jobs.query_id
	WHERE jobs.user_hash = ?
	AND jobs.is_example = 0
	ORDER BY jobs.job_date DESC, jobs.job_id DESC
	");
	$stmt->execute([$user_hash]);
	$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
	http_response_code(500);
	die("Could not retrieve previous jobs.");
}

echo <<<_HTML
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Previous Results</title>
</head>
<body>
_HTML;

include 'menuf.php';
echo <<<_HEADER
<header>
<h2>Previous Results</h2>
<p>This page lists previous analyses associated with your browser on this website.</p>
</header><hr />
_HEADER;

// Informative message if no previous results, link to query page
if (!$jobs) {
	echo <<<_BODY
<p><b>No previous jobs were found for this user.</b></p>
<p><a href='query.php'>Submit a new query</a></p>
<p>Or check out this <a href='example.php'>example dataset</a> for more information.</p>
</body></html>
_BODY;
    die();
}

// Table header
echo <<<_TH
<br>
<table border='1' cellpadding='6' cellspacing='0'>
<tr>
<th>Job ID</th>
<th>Date</th>
<th>Protein</th>
<th>Taxon</th>
<th>Status</th>
<th>plotcon Win Size</th>
<th>MSA Download Format</th>
<th>Link</th>
</tr>
_TH;

// Proessing query per row and inserting into table
foreach ($jobs as $job) {
	// Verifying query is not empty and an array
	$params = [];
	if (!empty($job['job_params'])) {
		$tmp = json_decode((string)$job['job_params'], true);
		if (is_array($tmp)) {
			$params = $tmp;
		}
	}

	// Optional parameters with empty fallbacks 
	$win_size = $params['win_size'] ?? 'fasta';
	$clust_outfmt = $params['clust_outfmt'] ?? '4';

	// Building link based on job status
	if ($job['status'] === 'pending') {
		$link = "loading_page.php?job_id=" . $job['job_id'];
		$label = "Processing";
	} else {
		$link = "results.php?job_id=" . $job['job_id'];
		$label = "View";
	}

	// Writing table
	echo "<tr>";
	echo "<td>" . htmlspecialchars((string)$job['job_id']) . "</td>";
	echo "<td>" . htmlspecialchars((string)$job['job_date']) . "</td>";
	echo "<td>" . htmlspecialchars((string)$job['protein_family']) . "</td>";
	echo "<td>" . htmlspecialchars((string)$job['taxon']) . "</td>";
	echo "<td>" . htmlspecialchars((string)$job['status']) . "</td>";
	echo "<td>" . htmlspecialchars((string)$win_size) . "</td>";
	echo "<td>" . htmlspecialchars((string)$clust_outfmt) . "</td>";
	echo "<td><a href='" . htmlspecialchars($link) . "'>" . htmlspecialchars($label) . "</a></td>";
	echo "</tr>";
}

echo <<<_EOF
</table>
<br />
<p><a href='query.php'>Submit a new query</a></p>
</body>
</html>
_EOF;
?>


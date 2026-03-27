<?php 
// Adapted from class code, debugged with ELM (GPT 5.2), https://elm.edina.ac.uk/elm/elm
// Loading page for query: loading until results are available
session_start();
require_once 'set_cookies.php';
require_once 'login.php';

// Base dir for pretty URLs
$BASE = '/~s2883992/website';

// Making sure user_hash is set
$user_hash = $_SESSION['user_hash'] ?? '';
if ($user_hash === '') {
	die("Missing user_hash");
}

// MySQL connection, adapted from class code
try {
        $dsn = "mysql:host=127.0.0.1;dbname=$database;charset=utf8mb4";
	$conn = new PDO($dsn, $username, $password, 
		array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
} catch(PDOException $e) {
        die("<br/><br/><b><font color=\"red\">Connection failed</font></b>:<br/>" . $e->getMessage());
}

// If directed from query, using POST
// Getting POST variables, removing whitespace, setting defaults
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$taxon = isset($_POST['taxon']) ? trim($_POST['taxon']) : '';
	$prot_fam = isset($_POST['prot_fam']) ? trim((string)$_POST['prot_fam']) : '';
	$clust_outfmt = isset($_POST['clust_outfmt']) ? strtolower(trim((string)$_POST['clust_outfmt'])) : 'fasta';
	$plot_outfmt = isset($_POST['plot_outfmt']) ? strtolower(trim((string)$_POST['plot_outfmt'])) : 'png';
	$win_size = isset($_POST['win_size']) ? (int)$_POST['win_size'] : 4;

	// Verifying variables
	if ($taxon === '' || strlen($taxon) < 2) {
    		die("Invalid taxon");
	}
	if ($prot_fam === '' || strlen($prot_fam) < 2) {
    		die("Invalid protein family");
	}
	// Checking custom variables with strict comparisons, setting defaults if unreasonable
	if ($win_size < 1 || $win_size > 100) {
		$win_size = 4;
	}	
	$allowed_clust = ['fasta','clustal','msf','phylip','selex','stockholm','vienna'];
	if (!in_array($clust_outfmt, $allowed_clust, true)) {
		$clust_outfmt = 'fasta';
	}
	$allowed_plot = ['png','pdf','svg','gif','data','ps','hpgl','meta'];
	if (!in_array($plot_outfmt, $allowed_plot, true)) {
		$plot_outfmt = 'png';
	}

	// Upserting query row
	$stmt = $conn->prepare("
	INSERT INTO queries (protein_family, taxon)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE query_date = CURRENT_TIMESTAMP
	");
	$stmt->execute([$prot_fam, $taxon]);
	// Retrieving qid
	$qid = (int)$conn->lastInsertId();
	if ($qid <= 0) {
		die("Failed to get query_id");
	}

	// Creating pending job and storing requested parameters as JSON
	$job_params = [
        'taxon' => $taxon,
        'prot_fam' => $prot_fam,
        'clust_outfmt' => $clust_outfmt,
        'plot_outfmt' => $plot_outfmt,
        'win_size' => $win_size
	];

	$stmt = $conn->prepare("
        INSERT INTO jobs (user_hash, query_id, is_example, status, job_params)
        VALUES (?, ?, 0, 'pending', ?)
	");
    	$stmt->execute([$user_hash, $qid, json_encode($job_params, JSON_UNESCAPED_SLASHES)]);
	// Retrieving jid
	$jid = (int)$conn->lastInsertId();
	if ($jid <= 0) {
		die("Failed to create job");
	}

	// Processing query in the background
	// Adapted from: https://stackoverflow.com/questions/4626860/how-can-i-run-a-php-script-in-the-background-after-a-form-is-submitted
	$process_query = __DIR__ . "/process_query.php";
    	$log = sys_get_temp_dir() . "/bioapp_job_" . $jid . ".log";

    	$cmd = "/usr/local/bin/php " . escapeshellarg($process_query) . " " . escapeshellarg($jid)
         . " > " . escapeshellarg($log) . " 2>&1 &";
    	exec($cmd);


	// Redirecting to GET so refresh doesn’t re-submit POST
	// adapted from: https://stackoverflow.com/questions/30885877/how-to-automatically-refresh-the-page-after-submitting-a-form
    	header("Location: " . $BASE . "/loading/" . $jid);
    	die;
}

// Processing query


// If showing past results or after page reload, using GET
// Checking for jid
$jid = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
if ($jid <= 0) {
    http_response_code(400);
    die("Missing job_id");
}

// Retrieving process metadata based on cookies / example
$stmt = $conn->prepare("
    SELECT status, error_message FROM jobs
    WHERE jobs.job_id = ?
    AND (user_hash = ? OR is_example = 1)
    LIMIT 1
");
$stmt->execute([$jid, $user_hash]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);
// Checking for results
if (!$job) {
    http_response_code(404);
    die("Not found");
}

// Redirecting to result page if complete
if ($job['status'] === 'complete') {
    header("Location: " . $BASE . "/results/" . (int)$jid);
    exit;
}

echo<<<_HTML
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Result page</title>
</head>
<body>
_HTML;

// Checking if job is pending, refreshing every 3 seconds
if ($job['status'] === 'pending') {
    echo "<meta http-equiv='refresh' content='3'>";
}

include 'menuf.php';

echo "<h2>Job " . $jid . "</h2>";
echo "<p><b>Status:</b> " . $job['status'] . "</p>";

// Adding informative messages based on status, stopping execution after it
// Pending
if ($job['status'] === 'pending') {
    echo "<p>Processing... please wait. This page will refresh automatically.</p>";
    echo "<p><a href='" . $BASE . "/query'>Back to query</a></p>";
    echo "</body></html>";
    die;
}

// Error
if ($job['status'] === 'error') {
    echo "<p style='color:red'><b>An error has occurred</b></p>";
    echo "<pre>" . $job['error_message'] ?? '' . "</pre>";
    echo "<p><a href='" . $BASE . "/query'>Back to query</a></p>";
    echo "</body></html>";
    die;
}

echo <<<_HTML
</body>
</html>
_HTML;
?>

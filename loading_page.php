<?php 
// Adapted from class code, debugged with ELM (GPT 5.2), https://elm.edina.ac.uk/elm/elm
// Loading page for query: loading until results are available
session_start();
require_once 'set_cookies.php';
require_once 'login.php';

// Base dir for pretty URLs
$BASE = '/~s2883992/website';

// Random facts for fun
// Taken from: https://www.sciencefocus.com/science/fun-facts
// TODO: Eventually expand and use a database and hardcode into job.params
$loading_facts = [
	"An hour of walking burns just 250 calories.",
	"Leftover pasta has extra health benefits.",
	"A thunderstorm called Hector the Convector arrives everyday at 3pm.",
	"Earlobes have no biological purpose.",
	"When you're reading – even silently – the muscles of your mouth, tongue and larynx activate.",
	"The largest piece of fossilised dinosaur poo discovered is over 30cm long and over two litres in volume.",
	"Mars isn’t actually round.",
	"The Universe's average colour is called 'Cosmic latte'.",
	"The raw ingredients of a human body would cost over £116,000.",
	"All the world’s bacteria stacked on top of each other would stretch for 10 billion light-years.",
	"The fear of long words is called Hippopotomonstrosesquippedaliophobia.",
	"The world’s oldest dog lived to 29.5 years old.",
	"The world’s oldest cat lived to 38 years and three days old.",
	"The Moon is gradually drifting away from Earth (around 3cm a year).",
	"Chainsaws were first invented in Scotland to assist with childbirth.",
	"In 1912, a man invented a human flying suit, jumped off the Eiffel Tower, and fell to his death.",
	"Orcas wear other animals as hats. No one knows why.",
	"Pythons can swallow people whole.",
	"Hippos can’t swim.",
	"There are more bacterial cells in your body than human cells.",
	"Your nails grow faster in hot summer.",
	"A rainbow on Venus is called a glory.",
	"Football players spit so much because exercise increases the amount of protein in saliva.",
	"The biggest butterfly in the world has a 31cm wingspan.",
	"A lightning bolt is five times hotter than the surface of the Sun.",
	"The longest anyone has held their breath underwater is over 24.5 minutes.",
	"Flamingoes aren’t born pink, they actually come into the world with grey/white feathers.",
	"People who eat whatever they want and stay slim have a slow metabolism, not fast.",
	"It’s actually fine to drink alcohol on (most) antibiotics.",
	"Giraffes hum to communicate with each other.",
	"Murder rates rise in summer.",
	"'New car smell' is a mix of over 200 chemicals.",
	"You can’t fold a piece of A4 paper more than eight times.",
	"Your brain burns 400-500 calories a day."
];

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
	http_response_code(500);
	die("The site is temporarily unavailable. Please try again later.");
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
	$stmt = $conn->prepare("
	SELECT query_id
	FROM queries
	WHERE protein_family = ? AND taxon = ?
	LIMIT 1
	");
	$stmt->execute([$prot_fam, $taxon]);
	$qid = (int)$stmt->fetchColumn();

	// Stopping if no qid
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

	// Verifying loading facts
	if (!isset($_SESSION['loading_facts']) || !is_array($_SESSION['loading_facts'])) {
	       	$_SESSION['loading_facts'] = [];
	}

	// Storing a random fact per jid in the session
	$_SESSION['loading_facts'][$jid] = $loading_facts[array_rand($loading_facts)];
	
	// Releasing session before redirection
	session_write_close();
	
	// Processing query in the background
	// Adapted from: https://stackoverflow.com/questions/4626860/how-can-i-run-a-php-script-in-the-background-after-a-form-is-submitted
	// Debugged with ELM (GPT 5.2), https://elm.edina.ac.uk/elm-new
	$process_query = __DIR__ . "/process_query.php";
	$log = sys_get_temp_dir() . "/bioapp_job_" . $jid . ".log";
	
	$cmd = "/usr/local/bin/php " . escapeshellarg($process_query) . " " . escapeshellarg($jid)
	. " > " . escapeshellarg($log) . " 2>&1 &";
	exec($cmd);

	// Redirecting to GET so refresh doesn’t re-submit POST
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
	SELECT job_id, status, error_message, job_params,
	queries.protein_family, queries.taxon
	FROM jobs
	JOIN queries ON queries.query_id = jobs.query_id
	WHERE jobs.job_id = ?
	AND (jobs.user_hash = ? OR jobs.is_example = 1)
	LIMIT 1
	");
$stmt->execute([$jid, $user_hash]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);
// Checking for results
if (!$job) {
	http_response_code(404);
	require __DIR__ . '/not_found.php';
	die();
}

// Redirecting to result page if complete
if ($job['status'] === 'complete') {
	// No longer needed for the whole session
	if (isset($_SESSION['loading_facts'][$jid])) {
		unset($_SESSION['loading_facts'][$jid]);
	}
	session_write_close();
	header("Location: " . $BASE . "/results/" . (int)$jid);
	die();
}

// Decode params for display
$params = [];
if (!empty($job['job_params'])) {
	$tmp = json_decode((string)$job['job_params'], true);
	if (is_array($tmp)) {
		$params = $tmp;
	}
}

// Decide page mode and prepare any session-backed content
$page_mode = 'unknown';
$job_fact = '';

// Error
if ($job['status'] === 'error') {
	// No need for the fact anymore
	if (isset($_SESSION['loading_facts'][$jid])) {
		unset($_SESSION['loading_facts'][$jid]);
	}
	session_write_close();

	// Updating page mode
	$page_mode = 'error';

// Pending
} elseif ($job['status'] === 'pending') {
	// Retrieving loading fact
	if (isset($_SESSION['loading_facts'][$jid])) {	
		$job_fact = $_SESSION['loading_facts'][$jid];
	} else {
	$job_fact = $loading_facts[array_rand($loading_facts)];
	$_SESSION['loading_facts'][$jid] = $job_fact;
	}
	session_write_close();

	// Updating mode
	$page_mode = 'pending';
} else {
	session_write_close();
}

echo<<<_HTML
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link rel="stylesheet" href="/~s2883992/website/styles.css" />
<title>Processing Query</title>
_HTML;

// Pending page rendering, refreshing every 3 seconds
// Adapted from: https://stackoverflow.com/questions/30885877/how-to-automatically-refresh-the-page-after-submitting-a-form
if ($page_mode === 'pending') {
	echo "<meta http-equiv='refresh' content='3'>";
}

echo "</head><body>";

include 'cookies.html';
include 'menuf.php';

echo <<<_BODY
<main class="query-shell">
<section class="form-panel">
<h1>Processing Query</h1>
_BODY;

// Adding informative messages based on status, stopping execution after it
// Pending view
if ($page_mode === 'pending') {
	echo "<p><b>Status</b>: In progress</p>";
	echo "<p>Your request is being processed. This page will update automatically.</p>";
	
	// Random fact
	echo "<article class='loading-fact-box'>";
	echo "<h2>Fun fact while you wait...</h2>";
	echo "<p><i>" . htmlspecialchars($job_fact) . "</i></p>";
	echo "<small>Source: <a href='https://www.sciencefocus.com/science/fun-facts' target='_blank'>BBC Science Focus</a></small>";
	echo "</article><hr>";

	// Displaying table with query details
	echo "<h2>Job Details:</h2>";
	echo "<table class='form-table'>";
	echo "<tr><td><b>Job ID:</b></td><td>" . htmlspecialchars((string)$jid) . "</td></tr>";
	echo "<tr><td><b>Protein family:</b></td><td>" . htmlspecialchars((string)$job['protein_family']) . "</td></tr>";
	echo "<tr><td><b>Taxon:</b></td><td>" . htmlspecialchars((string)$job['taxon']) . "</td></tr>";

	// Optional parameters
	if (!empty($params['clust_outfmt'])) {
		echo "<tr><td><b>Clustal Omega format:</b></td><td>" . htmlspecialchars((string)$params['clust_outfmt']) . "</td></tr>";
	}

	if (!empty($params['plot_outfmt'])) {
		echo "<tr><td><b>Plotcon output format:</b></td><td>" . htmlspecialchars((string)$params['plot_outfmt']) . "</td></tr>";
	}

	if (!empty($params['win_size'])) {
		echo "<tr><td><b>Plotcon window size:</b></td><td>" . htmlspecialchars((string)$params['win_size']) . "</td></tr>";
	}
	
	echo "</table>";

	// Another informative message
	echo "<p>You can <a href='" . $BASE . "/query'>submit another query</a> while this one runs.</p>";
	die;

// Error view
} elseif ($page_mode === 'error') {
	// No longer needed
	if (isset($_SESSION['loading_facts'][$jid])) {
		unset($_SESSION['loading_facts'][$jid]);
	}
	session_write_close();
	echo "<p style='color:red'><b>Status:</b> Error</p>";
	echo "<p>Unfortunately, this query could not be completed.</p>";
	echo "<pre>" . htmlspecialchars($job['error_message'] ?? '') . "</pre>";
	echo "<p><a href='" . $BASE . "/query'>Back to query page</a></p>";
	die;
} else {
	echo "<p>Unknown status.</p>";
	echo "<p><a href='" . $BASE . "/query'>Back to query page</a></p>";
}

echo <<<_HTML
</section>
</main>
</body>
</html>
_HTML;
?>

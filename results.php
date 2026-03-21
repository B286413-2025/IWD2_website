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

// Making sure job not pending, otherwise directing to loading page
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
<li><a href="#alignment">Alignment Overview</a></li>
<li><a href="#motifs">Motif Hits Overview</a></li>
<li><a href="query.php" target="_blank">New Query</a></li>
</ul>
</nav>
</header>
<hr />
_NAV;

// Query information
echo "<article id='query_param'>";
echo "<h3>Query parameters</h3>";
echo "<p><b>Protein:</b> " . htmlspecialchars((string)$job['protein_family']) . "<br>";
echo "<b>Taxon:</b> " . htmlspecialchars((string)$job['taxon']) . "<br>";
echo "<b>Job ID:</b> " . htmlspecialchars((string)$jid) . "</p>";
echo "</article><hr />";

// Checking for error
if ($job['status'] === 'error') {
	echo "<p style='color:red'><b>An error has occurred</b> &#128533</p>";
	echo "<pre>" . htmlspecialchars((string)($job['error_message'] ?? '')) . "</pre>";
	echo "<p>You can try again or submit another query: <a href='query.php'>back to query page</a></p>";
	echo "</body></html>";
	die();
}

// Displaying results if job is complete
// Plotcon
// Retrieving ouput ID and mime type
$stmt = $conn->prepare("
	SELECT output_id, mime_type, file_name, parameters
	FROM analysis_outputs
	WHERE job_id = ? AND analysis_type='plotcon'
	ORDER BY created_at DESC, output_id DESC
	");
$stmt->execute([(int)$jid]);
$plot_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Displaying output and offering download with get_output.php
echo "<article id='plotcon_res'>";
echo "<h3>Plotcon Conservation Plot</h3>";
if (!$plot_rows) {
	echo "<p><b>Plotcon output not found for this job.</b></p>";
	echo "</article><hr />";
} else {
	// Determining requested plot format from job_params
	$params = [];
	if (!empty($job['job_params'])) {
		$tmp = json_decode((string)$job['job_params'], true);
		if (is_array($tmp)) {
			$params = $tmp;
		}
	}
	$requested_fmt = $params['plot_outfmt'] ?? '';
	// Going over plot output
	$png_row = null;
	$req_row = null;
	foreach ($plot_rows as $r) {
		$mime = (string)($r['mime_type'] ?? '');
		// If the mime type is png, update png
        	if ($png_row === null && $mime === 'image/png') {
			$png_row = $r;
		}
		// If not and no previous out format
		if ($requested_fmt !== '' && $req_row === null && !empty($r['parameters'])) {
			// Get the analysis parameters
			$p = json_decode((string)$r['parameters'], true);
			// Verify the format
			if (is_array($p) && strtolower((string)($p['graph'] ?? '')) === $requested_fmt) {
				$req_row = $r;
			}
		}
	}

	// Displaying results on web page
	// Locating type, preferably png
	$display_row = $png_row;
	// If no png
	if ($display_row === null) {
		// Get mime type
		foreach ($plot_rows as $r) {
			$mime = (string)($r['mime_type'] ?? '');
			// Use if image/
			if (str_starts_with($mime, 'image/')) { 
				$display_row = $r; 
				break; 
			}
		}
	}
	// Web image
	if ($display_row) {
		$oid = (int)$display_row['output_id'];
		echo "<img style='max-width:900px;width:100%;height:auto;border:1px solid #ccc' "
		. "src='get_output.php?output_id={$oid}' alt='plotcon'>";
	} else {
		echo "<p><i>No browser-displayable plotcon image found.</i></p>";
	}

	// Download links with get_output.php
	echo "<p>";
	// png default
	if ($png_row) {
		$oid = (int)$png_row['output_id'];
		echo "<a href='get_output.php?output_id={$oid}&download=1'>Download PNG</a>";
	}
	// Requested format if different
	if ($req_row && $requested_fmt !== 'png') {
		$oid = (int)$req_row['output_id'];
		$fname = htmlspecialchars((string)($req_row['file_name'] ?? 'requested_plot'));
		echo ($png_row ? " | " : "");
		echo "<a href='get_output.php?output_id={$oid}&download=1'>Download requested format</a> (" . $fname . ")";
	}
	echo "</p>";
	echo "</article><hr />";
}

// Summary statistics
$qid = (int)$job['query_id'];

// Number of sequences
$stmt = $conn->prepare("SELECT COUNT(*) FROM seq_group WHERE query_id=?");
$stmt->execute([$qid]);
$n_seqs = (int)$stmt->fetchColumn();

// Number of unique organisms
$stmt = $conn->prepare("
	SELECT COUNT(DISTINCT sequences.organism)
	FROM seq_group 
	JOIN sequences ON sequences.accession = seq_group.accession
	WHERE seq_group.query_id=?
	");
$stmt->execute([$qid]);
$n_org = (int)$stmt->fetchColumn();

// Alignment length (min/max, verifying match later)
$stmt = $conn->prepare("
	SELECT MIN(LENGTH(aligned_sequence)) AS min_len,
	MAX(LENGTH(aligned_sequence)) AS max_len
	FROM aligned_sequences
	WHERE job_id=?
	");
$stmt->execute([(int)$jid]);
$aln = $stmt->fetch(PDO::FETCH_ASSOC);
// Fallback if not int
$aln_min = (int)($aln['min_len'] ?? 0);
$aln_max = (int)($aln['max_len'] ?? 0);

// Mean raw sequence length
$stmt = $conn->prepare("
	SELECT AVG(LENGTH(sequences.sequence))
	FROM seq_group
	JOIN sequences ON sequences.accession = seq_group.accession
	WHERE seq_group.query_id = ?
	");
$stmt->execute([$qid]);
$mean_len = (float)$stmt->fetchColumn();

// Most common motif and number of occurrences
$stmt = $conn->prepare("
	SELECT motif_name, COUNT(*) AS n
	FROM motif_hits
	WHERE job_id = ?
	GROUP BY motif_name
    	ORDER BY n DESC
    	LIMIT 1
	");
$stmt->execute([(int)$jid]);
$top = $stmt->fetch(PDO::FETCH_ASSOC);
// Fallbacks if none were found
$top_motif = $top ? $top['motif_name'] : 'None';
$top_motif_n = $top ? (int)$top['n'] : 0;

// Number of motif types
$stmt = $conn->prepare("
	SELECT COUNT(DISTINCT motif_name)
	FROM motif_hits
	WHERE job_id = ?
	");
$stmt->execute([(int)$jid]);
$n_motif_types = (int)$stmt->fetchColumn();

// Echoing HTML table
echo "<article id='summary'>";
echo "<h3>Summary statistics</h3>";
echo "<table border='1' cellpadding='6' cellspacing='0'>";
echo "<tr><th>Protein</th><td>" . htmlspecialchars((string)$job['protein_family']) . "</td></tr>";
echo "<tr><th>Taxon</th><td>" . htmlspecialchars((string)$job['taxon']) . "</td></tr>";
echo "<tr><th>Number of sequences</th><td>" . htmlspecialchars((string)$n_seqs) . "</td></tr>";
echo "<tr><th>Number of unique organisms</th><td>" . htmlspecialchars((string)$n_org) . "</td></tr>";

// Checking that min and max alignment lengths are the same
if ($aln_min !== $aln_max) {
	echo "<tr><th>Min alignment length</th><td>" . htmlspecialchars((string)$aln_min) . "</td></tr>";
	echo "<tr><th>Max alignment length</th><td>" . htmlspecialchars((string)$aln_max) . "</td></tr>";
} else {
	echo "<tr><th>Alignment length</th><td>" . htmlspecialchars($aln_min) . "</td></tr>";
}

echo "<tr><th>Mean raw seq length</th><td>" . htmlspecialchars((string)number_format($mean_len, 2)) . "</td></tr>";
echo "<tr><th>Top motif (#)</th><td>" . htmlspecialchars((string)$top_motif) . " (" . htmlspecialchars((string)$top_motif_n) . ")</td></tr>";
echo "<tr><th>Numbe of motif types</th><td>" . htmlspecialchars($n_motif_types) . "</td></tr>";
echo "</table></article><br><hr />";

// Download results
// MSA
$stmt = $conn->prepare("
	SELECT output_id, file_name
	FROM analysis_outputs
    	WHERE job_id = ? AND analysis_type='msa'
    	ORDER BY created_at DESC, output_id DESC
	");
$stmt->execute([(int)$jid]);
$msa_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo <<<_FILES
<article id='files'>
<h3>Text Files</h3>
<h4>MSA Files</h4>
_FILES;
if (!$msa_rows) {
	echo "<p><i>No alignment outputs saved for this job.</i></p>";
} else {
	echo "<ul>";
	foreach ($msa_rows as $r) {
		$oid = (int)$r['output_id'];
		$fname = htmlspecialchars((string)($r['file_name'] ?? ("msa_" . $oid)));
		echo "<li><a href='get_output.php?output_id={$oid}&download=1'>Download MSA</a> - {$fname}</li>";
	}
	echo "</ul>";
}

// Motifs report
echo "<h4>Motif report</h4>";
echo "<ul>";
echo "<li><a href='download_motif_hits.php?job_id=" . (int)$jid . "'>Download motif hits</a> - TSV</li>";
echo "</ul>";
echo "</article><br><hr />";

// Alignment overview
// Number of aligned sequences: between 1-500, default 50
$aln_limit = isset($_GET['aln_limit']) ? (int)$_GET['aln_limit'] : 50;
if ($aln_limit < 1 || $aln_limit > 500) $aln_limit = 50;

$aln_sort = $_GET['aln_sort'] ?? 'gaps_desc';
$aln_order_sql = "gap_fraction DESC";
if ($aln_sort === 'gaps_asc') $aln_order_sql = "gap_fraction ASC";
if ($aln_sort === 'organism') $aln_order_sql = "organism ASC";
if ($aln_sort === 'accession') $aln_order_sql = "accession ASC";

echo "<article id='alignment'>";
echo "<h3>Alignment overview</h3>";

echo "<form method='get' action='results.php'>
  <input type='hidden' name='job_id' value='" . htmlspecialchars($jid) . "'>
  <label>Rows:</label>
  <input type='number' name='aln_limit' min='1' max='500' value='" . htmlspecialchars($aln_limit) . "'>
  <label>Sort:</label>
  <select name='aln_sort'>
    <option value='gaps_desc' " . ($aln_sort==='gaps_desc'?'selected':'') . ">Gaps (desc)</option>
    <option value='gaps_asc' " . ($aln_sort==='gaps_asc'?'selected':'') . ">Gaps (asc)</option>
    <option value='organism' " . ($aln_sort==='organism'?'selected':'') . ">Organism</option>
    <option value='accession' " . ($aln_sort==='accession'?'selected':'') . ">Accession</option>
  </select>
  <button type='submit'>Update</button>
</form>";

$stmt = $conn->prepare("
    SELECT
      s.organism AS organism,
      a.accession AS accession,
      LENGTH(a.aligned_sequence) AS aln_len,
      (LENGTH(a.aligned_sequence) - LENGTH(REPLACE(a.aligned_sequence, '-', ''))) AS gap_count,
      ROUND(
        (LENGTH(a.aligned_sequence) - LENGTH(REPLACE(a.aligned_sequence, '-', '')))
        / NULLIF(LENGTH(a.aligned_sequence),0),
        4
      ) AS gap_fraction
    FROM aligned_sequences a
    JOIN sequences s ON s.accession = a.accession
    WHERE a.job_id = ?
    ORDER BY $aln_order_sql
    LIMIT $aln_limit
");
$stmt->execute([(int)$jid]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='6' cellspacing='0'>";
echo "<tr><th>Organism</th><th>Accession</th><th>Aln length</th><th>Gaps</th><th>Gap fraction</th></tr>";
foreach ($rows as $r) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($r['organism']) . "</td>";
    echo "<td>" . htmlspecialchars($r['accession']) . "</td>";
    echo "<td>" . htmlspecialchars($r['aln_len']) . "</td>";
    echo "<td>" . htmlspecialchars($r['gap_count']) . "</td>";
    echo "<td>" . htmlspecialchars($r['gap_fraction']) . "</td>";
    echo "</tr>";
}
echo "</table>";
echo "</article><br><hr />";

// Motif overview
$mot_limit = isset($_GET['mot_limit']) ? (int)$_GET['mot_limit'] : 50;
if ($mot_limit < 1 || $mot_limit > 500) $mot_limit = 50;

$mot_sort = $_GET['mot_sort'] ?? 'motif';
$mot_order_sql = "mh.motif_name ASC";
if ($mot_sort === 'organism') $mot_order_sql = "s.organism ASC, mh.motif_name ASC";
if ($mot_sort === 'start') $mot_order_sql = "mh.start_pos ASC";

echo "<article id='motifs'>";
echo "<h3>Motif hits overview</h3>";

echo "<form method='get' action='results.php'>
  <input type='hidden' name='job_id' value='" . htmlspecialchars($jid) . "'>
  <label>Rows:</label>
  <input type='number' name='mot_limit' min='1' max='500' value='" . htmlspecialchars($mot_limit) . "'>
  <label>Sort:</label>
  <select name='mot_sort'>
    <option value='motif' " . ($mot_sort==='motif'?'selected':'') . ">Motif</option>
    <option value='organism' " . ($mot_sort==='organism'?'selected':'') . ">Organism</option>
    <option value='start' " . ($mot_sort==='start'?'selected':'') . ">Start position</option>
  </select>
  <button type='submit'>Update</button>
</form>";

$stmt = $conn->prepare("
    SELECT s.organism, mh.accession, mh.motif_name, mh.start_pos, mh.end_pos, mh.score, mh.matched_sequence
    FROM motif_hits mh
    JOIN sequences s ON s.accession = mh.accession
    WHERE mh.job_id = ?
    ORDER BY $mot_order_sql
    LIMIT $mot_limit
");
$stmt->execute([(int)$jid]);
$mrows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='6' cellspacing='0'>";
echo "<tr><th>Organism</th><th>Accession</th><th>Motif</th><th>Start</th><th>End</th><th>Score</th><th>Matched</th></tr>";
foreach ($mrows as $r) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($r['organism']) . "</td>";
    echo "<td>" . htmlspecialchars($r['accession']) . "</td>";
    echo "<td>" . htmlspecialchars($r['motif_name']) . "</td>";
    echo "<td>" . htmlspecialchars($r['start_pos']) . "</td>";
    echo "<td>" . htmlspecialchars($r['end_pos']) . "</td>";
    echo "<td>" . htmlspecialchars($r['score']) . "</td>";
    echo "<td>" . htmlspecialchars($r['matched_sequence']) . "</td>";
    echo "</tr>";
}
echo "</table>";
echo "</article><br><hr />";

// To submit a new query
echo <<<_HTML3
<p id='new_query'><a href='query.php' target="_blank">New query</a></p>
</body>
</html>
_HTML3;
?>

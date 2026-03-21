<?php // Debugged using ELM (GPT 5.2), https://elm.edina.ac.uk/elm-new
// Script to generate motif tsv report for download
session_start();
require_once 'set_cookies.php';
require_once 'login.php';

// Checking required parameters
// User hash
$user_hash = $_SESSION['user_hash'] ?? '';
if ($user_hash === '') {
	http_response_code(500);
	die("Missing user_hash");
}

// jid
$jid = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
if ($jid <= 0) {
	http_response_code(400);
	die("Missing job_id");
}

// MySQL connection, adapted from class code
try {
	$dsn = "mysql:host=127.0.0.1;dbname=$database;charset=utf8mb4";
	$conn = new PDO($dsn, $username, $password, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));

	// Verifying user-job
	$stmt = $conn->prepare("
	SELECT job_id
        FROM jobs
        WHERE job_id = ?
        AND (user_hash = ? OR is_example = 1)
        LIMIT 1
	");
	$stmt->execute([$jid, $user_hash]);
	$check = $stmt->fetchColumn();
	// Exit if not found, 
	if (!$check) {
		http_response_code(404);
		die("Not found");
	}

	// Retrieving motif information for report
    	$stmt = $conn->prepare("
	SELECT
	sequences.organism,
	mh.accession,
	mh.motif_name,
	mh.start_pos,
	mh.end_pos,
	mh.score,
	mh.matched_sequence
	FROM motif_hits AS mh
        JOIN sequences ON sequences.accession = mh.accession
        WHERE mh.job_id = ?
        ORDER BY mh.motif_name, sequences.organism, mh.accession, mh.start_pos
	");
	$stmt->execute([$jid]);

	// Filesname and headers for TSV download
	$fname = "motif_hits_job_" . $jid . ".tsv";
	header("Content-Type: text/tab-separated-values; charset=utf-8");
	header("Content-Disposition: attachment; filename=\"" . addslashes($fname) . "\"");
	header("Cache-Control: private, no-store");

	// Output TSV
	// Header line
	echo "Organism\tAccession\tMotif\tStart\tEnd\tScore\tMatchedSequence\n";
	// Retrieving rows
	while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		// Retrieving cells, replacing tabs and newlines with spaces
		$clean = [];
		foreach ($row as $cell) {
			$cell = (string)$cell;
			$cell = str_replace(["\t", "\n"], " ", $cell);
			$clean[] = $cell;
		}
		// Echoing tsv lines
		echo implode("\t", $clean) . "\n";
	}
} catch (Throwable $e) {
	http_response_code(500);
	die("Server error");
}
?>

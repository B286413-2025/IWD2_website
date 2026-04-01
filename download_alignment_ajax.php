<?php 
// Adapted from ELM (GPT 5.2) code, https://elm.edina.ac.uk/elm-new
// Script to download the currently filtered alignment table as TSV

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
session_write_close();

// jid
$jid = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
if ($jid <= 0) {
	http_response_code(400);
	die("Missing job_id");
}

// Filter parameters with default fallback
// Size
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
if ($limit < 1) {
	$limit = 50;
}
if ($limit > 1000) {
	$limit = 1000;
}

// Sorting and direction
$sort = isset($_GET['sort']) ? (string)$_GET['sort'] : 'gap_fraction';
$dir  = isset($_GET['dir']) ? strtolower((string)$_GET['dir']) : 'desc';
$dir  = ($dir === 'asc') ? 'ASC' : 'DESC';

// Optional filters
// Organism pattern
$organism_like = isset($_GET['organism_like']) ? trim((string)$_GET['organism_like']) : '';
if (strlen($organism_like) > 255) {
	$organism_like = substr($organism_like, 0, 255);
}

// Minimal gap count
$min_gap_count = isset($_GET['min_gap_count']) && $_GET['min_gap_count'] !== '' ? (int)$_GET['min_gap_count'] : null;
if ($min_gap_count !== null && $min_gap_count < 0) {
	$min_gap_count = 0;
}

// Minimal gap fraction
$min_gap_fraction = isset($_GET['min_gap_fraction']) && $_GET['min_gap_fraction'] !== '' ? (float)$_GET['min_gap_fraction'] : null;
if ($min_gap_fraction !== null && $min_gap_fraction < 0) {
	$min_gap_fraction = 0;
}
if ($min_gap_fraction !== null && $min_gap_fraction > 1) {
	$min_gap_fraction = 1;
}

// Include aligned sequences
$include_aligned = isset($_GET['include_aligned']) && $_GET['include_aligned'] === '1';

// Mapping chosen fields to SQL query select parameters
$allowed_fields = [
	'organism' => 's.organism AS organism',
	'accession' => 'a.accession AS accession',
	'raw_len' => 'LENGTH(s.sequence) AS raw_len',
	'gap_count' => '(LENGTH(a.aligned_sequence) - LENGTH(REPLACE(a.aligned_sequence, "-", ""))) AS gap_count',
	'gap_fraction' => 'ROUND((LENGTH(a.aligned_sequence) - LENGTH(REPLACE(a.aligned_sequence, "-", ""))) / NULLIF(LENGTH(a.aligned_sequence),0), 4) AS gap_fraction'
];

// Mapping fields to headings for nice file headers
$field_labels = [
	'organism' => 'Organism',
	'accession' => 'Accession',
	'raw_len' => 'Sequence Length',
	'gap_count' => 'Gap Count',
	'gap_fraction' => 'Gap Fraction',
	'aligned_sequence' => 'Aligned Sequence'
];

// Fields from GET, default if empty
$field_list = isset($_GET['fields']) ? trim((string)$_GET['fields']) : '';
$fields = [];

if ($field_list !== '') {
	foreach (explode(',', $field_list) as $f) {
		$f = trim($f);
		if (isset($allowed_fields[$f])) $fields[] = $f;
	}
}

if (!$fields) {
	$fields = ['organism', 'accession', 'raw_len', 'gap_count', 'gap_fraction'];
}

// SQL sorting map to decide which column to sort on
$sort_map = [
	'accession' => 'a.accession',
	'organism' => 's.organism',
	'raw_len' => 'raw_len',
	'gap_count' => 'gap_count',
	'gap_fraction' => 'gap_fraction'
];
$order_by = $sort_map[$sort] ?? 'gap_fraction';

// MySQL connection, adapted from class code
try {
	$dsn = "mysql:host=127.0.0.1;dbname=$database;charset=utf8mb4";
	$conn = new PDO($dsn, $username, $password, [
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
	]);

	// Ownership + status check
	$stmt = $conn->prepare("
	SELECT status
	FROM jobs
	WHERE job_id = ?
	AND (user_hash = ? OR is_example = 1)
	LIMIT 1
	");
	$stmt->execute([$jid, $user_hash]);
	$status = $stmt->fetchColumn();

	// Return 404 if the job does not exist or is not visible to this user
	if (!$status) {
		http_response_code(404);
		die("Not found");
	}
	
	// Or not complete
	if ($status !== 'complete') {
		http_response_code(400);
		die("Job not complete");
	}

	// Building SQL query from parameter mapping array
	$select_sql = implode(",\n", array_map(function($f) use ($allowed_fields) {
		return $allowed_fields[$f];
	}, $fields));

	if ($include_aligned) {
		$select_sql .= ",\n a.aligned_sequence AS aligned_sequence";
	}

	$sql = "
	SELECT
	$select_sql
	FROM aligned_sequences AS a
	JOIN sequences AS s ON s.accession = a.accession
	WHERE a.job_id = :jid
	";
	
	$params = [':jid' => $jid];

	// Adding optional parameters
	if ($organism_like !== '') {
		$sql .= " AND s.organism LIKE :org ";
		$params[':org'] = '%' . $organism_like . '%';
	}

	$having = [];
	if ($min_gap_count !== null) {
		$having[] = "gap_count >= :min_gap_count";
		$params[':min_gap_count'] = $min_gap_count;
	}

	if ($min_gap_fraction !== null) {
		$having[] = "gap_fraction >= :min_gap_fraction";
		$params[':min_gap_fraction'] = $min_gap_fraction;
	}

	if (!empty($having)) {
		$sql .= " HAVING " . implode(" AND ", $having) . " ";
	}

	// And ordering
	$sql .= " ORDER BY $order_by $dir LIMIT $limit ";

	$stmt = $conn->prepare($sql);
	$stmt->execute($params);

	// Output TSV, filename and HTTP header
	$fname = "alignment_view_job_" . $jid . ".tsv";
	header("Content-Type: text/tab-separated-values; charset=utf-8");
	header("Content-Disposition: attachment; filename=\"" . addslashes($fname) . "\"");
	header("Cache-Control: private, no-store");

	// File header row
	$header_fields = $fields;
	// Optional aligned sequence
	if ($include_aligned) {
		$header_fields[] = 'aligned_sequence';
	}
	$header_names = [];
	foreach ($header_fields as $f) {
		// Setting header names, fallback to param name
		$header_names[] = $field_labels[$f] ?? $f;
	}
	echo implode("\t", $header_names) . "\n";

	// Data rows
	while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$out = [];
		foreach ($header_fields as $f) {
			// Empty fallback
			$v = (string)($row[$f] ?? '');
			$v = str_replace(["\t", "\r", "\n"], " ", $v);
			$out[] = $v;
		}
		echo implode("\t", $out) . "\n";
	}

} catch (Throwable $e) {
	http_response_code(500);
	die("Server error");
}

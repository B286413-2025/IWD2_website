<?php // Adapted from ELM (GPT 5.2) code, https://elm.edina.ac.uk/elm-new
// Script to fetch alignment data is JSON format for interactive display on the results page
session_start();
require_once 'set_cookies.php';
require_once 'login.php';

header("Content-Type: application/json; charset=utf-8");

// Checking user match
$user_hash = $_SESSION['user_hash'] ?? '';
if ($user_hash === '') {
	http_response_code(500);
	echo json_encode(['ok' => false, 'error' => 'Missing user_hash'], JSON_UNESCAPED_SLASHES);
	die();
}
session_write_close();

// Checking jid
$jid = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
if ($jid <= 0) {
	http_response_code(400);
	echo json_encode(['ok' => false, 'error' => 'Missing job_id'], JSON_UNESCAPED_SLASHES);
	die();
}

// Table features
// Limit
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
if ($limit < 1) $limit = 50;
if ($limit > 1000) $limit = 1000;

// Sorting + direction
$sort = isset($_GET['sort']) ? (string)$_GET['sort'] : 'gap_fraction';
$dir  = isset($_GET['dir']) ? strtolower((string)$_GET['dir']) : 'desc';
$dir  = ($dir === 'asc') ? 'ASC' : 'DESC';

// Optional parameters
$organism_like = isset($_GET['organism_like']) ? trim((string)$_GET['organism_like']) : '';
if (strlen($organism_like) > 255) {
	$organism_like = substr($organism_like, 0, 255);
}
$organism_like = str_replace(
	['\\', '%', '_'],
	['\\\\', '\%', '\_'],
	$organism_like
);
$max_gap_fraction = isset($_GET['max_gap_fraction']) && $_GET['max_gap_fraction'] !== '' ? (float)$_GET['max_gap_fraction'] : null;
if ($max_gap_fraction !== null && $max_gap_fraction < 0) {
	$max_gap_fraction = 0;
}
if ($max_gap_fraction !== null && $max_gap_fraction > 1) {
	$max_gap_fraction = 1;
}
$min_gap_fraction = isset($_GET['min_gap_fraction']) && $_GET['min_gap_fraction'] !== '' ? (float)$_GET['min_gap_fraction'] : null;
if ($min_gap_fraction !== null && $min_gap_fraction < 0) {
	$min_gap_fraction = 0;
}
if ($min_gap_fraction !== null && $min_gap_fraction > 1) {
	$min_gap_fraction = 1;
}

// Whether to include aligned_sequence
// TODO: can be large
$include_aligned = isset($_GET['include_aligned']) && $_GET['include_aligned'] === '1';

// Map sort keys to SQL
$sort_map = [
    'accession' => 'a.accession',
    'organism' => 's.organism',
    'raw_len' => 'raw_len',
    'aln_len' => 'aln_len',
    'gap_count' => 'gap_count',
    'gap_fraction' => 'gap_fraction'
];

// Selected sort
$order_by = $sort_map[$sort] ?? 'gap_fraction';

// MySQL connection, adapted from class code
try {
	$dsn = "mysql:host=127.0.0.1;dbname=$database;charset=utf8mb4";
	$conn = new PDO($dsn, $username, $password, [
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
	]);
	
	// Ownership and status check
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
		echo json_encode(['ok' => false, 'error' => 'Not found'], JSON_UNESCAPED_SLASHES);
        	die();
	}
	// Or not complete
	if ($status !== 'complete') {
		echo json_encode(['ok' => true, 'status' => $status, 'rows' => []], JSON_UNESCAPED_SLASHES);
		die();
	}
	
	// Build SQL query, fixed columns - organism, accession, raw seq length, gap count, gap fraction, (aligned sequence)
	// Optional aligned sequences
	$select_aln = $include_aligned ? ", a.aligned_sequence" : "";
	$sql = "
	SELECT
	s.organism AS organism,
        a.accession AS accession,
        LENGTH(s.sequence) AS raw_len,
        LENGTH(a.aligned_sequence) AS aln_len,
        (LENGTH(a.aligned_sequence) - LENGTH(REPLACE(a.aligned_sequence, '-', ''))) AS gap_count,
        ROUND(
        (LENGTH(a.aligned_sequence) - LENGTH(REPLACE(a.aligned_sequence, '-', '')))
        / NULLIF(LENGTH(a.aligned_sequence),0),
        4
        ) AS gap_fraction
        $select_aln
        FROM aligned_sequences AS a
        JOIN sequences AS s ON s.accession = a.accession
        WHERE a.job_id = :jid
	";

	// Binding jid
	$params = [':jid' => $jid];

	// Optional filters: organism substring, minimum gap count, minimum gap fraction 
	if ($organism_like !== '') {
		$sql .= " AND s.organism LIKE :org";
        	$params[':org'] = '%' . $organism_like . '%';
	}

	$having = [];
	if ($max_gap_fraction !== null) {
		$having[] = "gap_fraction <= :max_gap_fraction";
		$params[':max_gap_fraction'] = $max_gap_fraction;
	}
	
	if ($min_gap_fraction !== null) {
		$having[] = "gap_fraction >= :min_gap_fraction";
		$params[':min_gap_fraction'] = $min_gap_fraction;
	}
	if (!empty($having)) {
		$sql .= " HAVING " . implode(" AND ", $having) . " ";
	}

	// Adding order and limit params
	$sql .= " ORDER BY $order_by $dir LIMIT $limit ";
	
	$stmt = $conn->prepare($sql);
	$stmt->execute($params);
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

	// Updating status and infromation on success
	echo json_encode([
		'ok' => true,
        	'status' => 'complete',
        	'job_id' => $jid,
        	'rows' => $rows
	], JSON_UNESCAPED_SLASHES);

// Updating on failure
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Server error'], JSON_UNESCAPED_SLASHES);
}

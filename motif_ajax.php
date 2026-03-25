<?php // Adapted from ELM (GPT 5.2) code, https://elm.edina.ac.uk/elm-new
// Script to retrieve motif data from SQL to results page in JSON for interactive query
session_start();
require_once 'set_cookies.php';
require_once 'login.php';

header("Content-Type: application/json; charset=utf-8");

// Check user
$user_hash = $_SESSION['user_hash'] ?? '';
if ($user_hash === '') {
	http_response_code(500);
	echo json_encode(['ok' => false, 'error' => 'Missing user_hash']);
	die();
}

// Check job id
$jid = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
if ($jid <= 0) {
	http_response_code(400);
	echo json_encode(['ok' => false, 'error' => 'Missing job_id']);
	die();
}

// Table filters
// Size
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
if ($limit < 1) {
	$limit = 50;
}
if ($limit > 1000) {
	$limit = 1000;
}

// Sorting + direction
$sort = isset($_GET['sort']) ? (string)$_GET['sort'] : 'motif_name';
$dir  = isset($_GET['dir']) ? strtolower((string)$_GET['dir']) : 'asc';
$dir  = ($dir === 'desc') ? 'DESC' : 'ASC';

// Options
$organism_like = isset($_GET['organism_like']) ? trim((string)$_GET['organism_like']) : '';
if (strlen($organism_like) > 255) $organism_like = substr($organism_like, 0, 255);

$motif_like = isset($_GET['motif_like']) ? trim((string)$_GET['motif_like']) : '';
if (strlen($motif_like) > 255) $motif_like = substr($motif_like, 0, 255);

$min_score = isset($_GET['min_score']) && $_GET['min_score'] !== '' ? (float)$_GET['min_score'] : null;

// SQL sorting map
$sort_map = [
	'organism' => 's.organism',
	'accession' => 'mh.accession',
	'motif_name' => 'mh.motif_name',
	'start_pos' => 'mh.start_pos',
	'end_pos' => 'mh.end_pos',
	'score' => 'mh.score'
];
$order_by = $sort_map[$sort] ?? 'mh.motif_name';

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

	// Exiting if not found 
	if (!$status) {
		http_response_code(404);
 		echo json_encode(['ok' => false, 'error' => 'Not found']);
		die();
	}

	// Or not complete
	if ($status !== 'complete') {
		echo json_encode([
		'ok' => true,
		'status' => $status,
		'rows' => []
		]);
		die();
	}

	// Retrieving information - organism, accession, motif name, start position, end position, (min) score, matched sequence, (organism pattern), (motif pattern)
	$sql = "
	SELECT
	s.organism AS organism,
	mh.accession AS accession,
	mh.motif_name AS motif_name,
	mh.start_pos AS start_pos,
	mh.end_pos AS end_pos,
	mh.score AS score,
	mh.matched_sequence AS matched_sequence
	FROM motif_hits AS mh
	JOIN sequences AS s ON s.accession = mh.accession
	WHERE mh.job_id = :jid
	";

	$params = [':jid' => $jid];

	// Optional organism pattern
	if ($organism_like !== '') {
		$sql .= " AND s.organism LIKE :org ";
		$params[':org'] = '%' . $organism_like . '%';
	}

	// Optional motif pattern
	if ($motif_like !== '') {
		$sql .= " AND mh.motif_name LIKE :motif ";
		$params[':motif'] = '%' . $motif_like . '%';
	}

	// Optional min score
	if ($min_score !== null) {
		$sql .= " AND mh.score >= :score ";
		$params[':score'] = $min_score;
	}

	$sql .= " ORDER BY $order_by $dir LIMIT $limit ";

	$stmt = $conn->prepare($sql);
	$stmt->execute($params);
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

	// Returning results in JSON
	echo json_encode([
		'ok' => true,
		'status' => 'complete',
		'job_id' => $jid,
		'rows' => $rows
	]);

// Error if failing
} catch (Throwable $e) {
	http_response_code(500);
	echo json_encode(['ok' => false, 'error' => 'Server error']);
}

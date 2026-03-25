<?php // Adapted from ELM (GPT 5.2) code, https://elm.edina.ac.uk/elm-new
session_start();
require_once 'set_cookies.php';
require_once 'login.php';

// Check user
$user_hash = $_SESSION['user_hash'] ?? '';
if ($user_hash === '') {
	http_response_code(500);
	die("Missing user_hash");
}

// Check job id
$jid = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
if ($jid <= 0) {
	http_response_code(400);
	die("Missing job_id");
}

// Table filters
// Limits
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
if ($limit < 1) {
	$limit = 50;
}
if ($limit > 1000) {
	$limit = 1000;
}

// Sorting + direction
$sort = isset($_GET['sort']) ? (string)$_GET['sort'] : 'motif_name';
$dir = isset($_GET['dir']) ? strtolower((string)$_GET['dir']) : 'asc';
$dir = ($dir === 'desc') ? 'DESC' : 'ASC';

// Optional parameters
$organism_like = isset($_GET['organism_like']) ? trim((string)$_GET['organism_like']) : '';
if (strlen($organism_like) > 255) {
	$organism_like = substr($organism_like, 0, 255);
}

$motif_like = isset($_GET['motif_like']) ? trim((string)$_GET['motif_like']) : '';
if (strlen($motif_like) > 255) {
	$motif_like = substr($motif_like, 0, 255);
}

$min_score = isset($_GET['min_score']) && $_GET['min_score'] !== '' ? (float)$_GET['min_score'] : null;

// Mapping names for SQL
$allowed_fields = [
	'organism' => 's.organism AS organism',
	'accession' => 'mh.accession AS accession',
	'motif_name' => 'mh.motif_name AS motif_name',
	'start_pos' => 'mh.start_pos AS start_pos',
	'end_pos' => 'mh.end_pos AS end_pos',
	'score' => 'mh.score AS score',
	'matched_sequence' => 'mh.matched_sequence AS matched_sequence'
];

// Getting fields from GET, default if empty
$field_list = isset($_GET['fields']) ? trim((string)$_GET['fields']) : '';
$fields = [];

if ($field_list !== '') {
	foreach (explode(',', $field_list) as $f) {
		$f = trim($f);
		if (isset($allowed_fields[$f])) {
			$fields[] = $f;
		}
    }
}

if (!$fields) {
    $fields = ['organism', 'accession', 'motif_name', 'start_pos', 'end_pos', 'score'];
}

// Mapping user-chosen sorting fields for MySQL
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

	// Failing if not found
	if (!$status) {
		http_response_code(404);
		die("Not found");
	}
	
	// Or complete
	if ($status !== 'complete') {
		http_response_code(400);
		die("Job not complete");
	}
	
	// Generating the query based on the user-chosen fields and the SQL mapping array
	$select_sql = implode(",\n", array_map(function($f) use ($allowed_fields) {
		return $allowed_fields[$f];
	}, $fields));

	$sql = "
	SELECT
	$select_sql
	FROM motif_hits AS mh
	JOIN sequences AS s ON s.accession = mh.accession
	WHERE mh.job_id = :jid
	";
	
	// Setting params array and expanding SQL query based on optional parameters
	$params = [':jid' => $jid];

	if ($organism_like !== '') {
		$sql .= " AND s.organism LIKE :org ";
		$params[':org'] = '%' . $organism_like . '%';
	}

	if ($motif_like !== '') {
		$sql .= " AND mh.motif_name LIKE :motif ";
		$params[':motif'] = '%' . $motif_like . '%';
	}

	if ($min_score !== null) {
		$sql .= " AND mh.score >= :score ";
		$params[':score'] = $min_score;
	}

	// Adding ordering
	$sql .= " ORDER BY $order_by $dir LIMIT $limit ";

	$stmt = $conn->prepare($sql);
	$stmt->execute($params);

	// File and HTTP header
	$fname = "motif_view_job_" . $jid . ".tsv";
	header("Content-Type: text/tab-separated-values; charset=utf-8");
	header("Content-Disposition: attachment; filename=\"" . addslashes($fname) . "\"");
	header("Cache-Control: private, no-store");

	// File header
	echo implode("\t", $fields) . "\n";

	// Query content
	while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$out = [];
		// Getting string content with empty fallback
		foreach ($fields as $f) {
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

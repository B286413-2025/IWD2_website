<?php 
// Adapted from ELM (GPT 5.2), https://elm.edina.ac.uk/elm-new
// Previous results ajax addition page - allows filtering

session_start();
require_once 'set_cookies.php';
require_once 'login.php';

header("Content-Type: application/json; charset=utf-8");

// Checking user_hash
$user_hash = $_SESSION['user_hash'] ?? '';
if ($user_hash === '') {
	http_response_code(500);
	echo json_encode(['ok' => false, 'error' => 'Missing user_hash']);
	die("Missing user_hash");
}

// Table filters from GET
$status_filter = isset($_GET['status']) ? trim((string)$_GET['status']) : '';
$search = isset($_GET['search']) ? trim((string)$_GET['search']) : '';

$allowed_status = ['', 'pending', 'complete', 'error'];
if (!in_array($status_filter, $allowed_status, true)) {
	$status_filter = '';
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

// Retrieve information about previous jobs for the current user
try {
	// Building filtered query
	$sql = "
	SELECT
	jobs.job_id,
	jobs.job_date,
	jobs.status, " .
//	jobs.error_message,
	"jobs.job_params,
	queries.protein_family,
	queries.taxon
	FROM jobs
	JOIN queries ON queries.query_id = jobs.query_id
	WHERE jobs.user_hash = :user_hash
	AND jobs.is_example = 0
	";

	// Building query parameters array
	$params = [':user_hash' => $user_hash];

	// Checking for status filter
	if ($status_filter !== '') {
		$sql .= " AND jobs.status = :status ";
		$params[':status'] = $status_filter;
	}

	// And search filter
	if ($search !== '') {
		$sql .= " AND (
			queries.protein_family LIKE :search
			OR queries.taxon LIKE :search
		) ";
		$params[':search'] = '%' . $search . '%';
	}
	
	// Ordering by job recency
	$sql .= " ORDER BY jobs.job_date DESC, jobs.job_id DESC ";
	
	$stmt = $conn->prepare($sql);	
	$stmt->execute($params);
	$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
	
	// Flattening JSON for more informative jobs table
	foreach ($jobs as &$job) {
		// Getting parameters
		$params = [];
		if (!empty($job['job_params'])) {
			$tmp = json_decode((string)$job['job_params'], true);
			if (is_array($tmp)) {
				$params = $tmp;
			}
		}
		
		// Setting parameters in the associative array
		$job['win_size'] = $params['win_size'] ?? 4;
		$job['plot_outfmt'] = $params['plot_outfmt'] ?? 'png';
		$job['clust_outfmt'] = $params['clust_outfmt'] ?? 'fasta';
	
		// Short error preview
//		if (!empty($job['error_message'])) {
//			$err = (string)$job['error_message'];
//			$job['error_preview'] = substr($err, 0, 20);
//			if (strlen($err) > 20) {
//				$job['error_preview'] .= '...';
//			}
//		} else {
//			$job['error_preview'] = '';
//		}
		// No need to send raw JSON
		unset($job['job_params']);
	}
	unset($job);

	// Output
	echo json_encode([
		'ok' => true,
		'rows' => $jobs
	]);

} catch (Throwable $e) {
	http_response_code(500);
	die("Could not retrieve previous jobs: " .  htmlspecialchars($e->getMessage()));
}

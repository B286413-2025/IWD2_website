<?php // Adapted from ELM (GPT 5.2) code, https://elm.edina.ac.uk/elm-new

// Script for displaying plotcon output from the database
session_start();
require_once 'set_cookies.php';
require_once 'login.php';

// Calling the script with GET parameters
if (!isset($_GET['output_id'])) {
	http_response_code(400);
	exit("Missing output_id");
}

// Retrieving output ID and download status
$output_id = (int)$_GET['output_id'];
$download  = isset($_GET['download']) && $_GET['download'] == '1';

// Checking user_hash existence
$user_hash = $_SESSION['user_hash'] ?? '';
if ($user_hash === '') {
    http_response_code(500);
    exit("Missing user_hash");
}

// MySQL connection, adapted from class code
try {
	$dsn = "mysql:host=127.0.0.1;dbname=$database;charset=utf8mb4";
	$conn = new PDO($dsn, $username, $password);
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	// Getting file name and data for identified user onlt
	$stmt = $conn->prepare("
		SELECT ao.mime_type, ao.file_name, ao.blob_data
		FROM analysis_outputs AS ao
		JOIN jobs ON jobs.job_id = as.job_id
		WHERE output_id = ?
		AND (jobs.user_hash = ? OR jobs.is_example = 1)
        	LIMIT 1
    	");
    	$stmt->execute([$output_id, $user_hash]);
    	$row = $stmt->fetch(PDO::FETCH_ASSOC);

	// Exiting if row is empty
    	if (!$row || $row['blob_data'] === null) {
        	http_response_code(404);
        	exit("Not found");
    	}

	// Retrieving parameters from query results, fallbacks if empty
    	$mime = $row['mime_type'] ?: "application/octet-stream";
    	$name = $row['file_name'] ?: ("output_" . $output_id);

    	// Caching: faster results re-retrieval 
    	$etag = '"' . sha1($row['blob_data']) . '"';
    	header("ETag: $etag");
    	header("Cache-Control: public, max-age=86400");

    	if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
        	http_response_code(304);
        	exit;
    	}

	// Header according to type (view/download) 
    	header("Content-Type: $mime");
    	if ($download) {
        	header("Content-Disposition: attachment; filename=\"" . addslashes($name) . "\"");
    	} else {
        	header("Content-Disposition: inline; filename=\"" . addslashes($name) . "\"");
    	}

    	echo $row['blob_data'];

} catch (Throwable $e) {
    	http_response_code(500);
    	exit("Server error");
}
?>

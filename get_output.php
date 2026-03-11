<?php // Adapted from ELM (GPT 5.2) code, https://elm.edina.ac.uk/elm-new

// Script for displaying plotcon output from the database

require_once 'login.php';

// Calling the script with GET parameters
if (!isset($_GET['output_id'])) {
	http_response_code(400);
	exit("Missing output_id");
}

// Retrieving output ID and download status
$output_id = (int)$_GET['output_id'];
$download  = isset($_GET['download']) && $_GET['download'] == '1';

// MySQL connection, adapted from class code
try {
	$dsn = "mysql:host=127.0.0.1;dbname=$database;charset=utf8mb4";
	$conn = new PDO($dsn, $username, $password);
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	// Getting file name and data
	$stmt = $conn->prepare("
		SELECT mime_type, file_name, blob_data
        	FROM analysis_outputs
        	WHERE output_id = ?
        	LIMIT 1
    	");
    	$stmt->execute([$output_id]);
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

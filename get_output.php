<?php 
// Adapted from ELM (GPT 5.2) code, https://elm.edina.ac.uk/elm-new

// Script for displaying and downloading outputs from the database in the results page

session_start();
require_once 'set_cookies.php';
require_once 'login.php';

// Calling the script with GET parameters
if (!isset($_GET['output_id'])) {
	http_response_code(400);
	die("Missing output_id");
}

// Retrieving output ID and download status
$output_id = (int)$_GET['output_id'];
$download  = isset($_GET['download']) && $_GET['download'] == '1';

// Checking user_hash existence
$user_hash = $_SESSION['user_hash'] ?? '';
if ($user_hash === '') {
	http_response_code(500);
	die("Missing user_hash");
}
session_write_close();

// MySQL connection, adapted from class code
try {
	$dsn = "mysql:host=127.0.0.1;dbname=$database;charset=utf8mb4";
	$conn = new PDO($dsn, $username, $password);
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	// Getting file name and data for identified user only
	$stmt = $conn->prepare("
		SELECT ao.mime_type, ao.file_name, ao.blob_data, ao.text_data
		FROM analysis_outputs AS ao
		JOIN jobs ON jobs.job_id = ao.job_id
		WHERE ao.output_id = ?
		AND (jobs.user_hash = ? OR jobs.is_example = 1)
		LIMIT 1
		");
	$stmt->execute([$output_id, $user_hash]);
	$row = $stmt->fetch(PDO::FETCH_ASSOC);

	// Exiting if not found
	if (!$row) {
		http_response_code(404);
		die("Not found");
	}

	// Checking output type
	$output = null;
	// blob data if exists
	if ($row['blob_data'] !== null) {
		$output = $row['blob_data'];
	// else text data if exists
	} elseif ($row['text_data'] !== null) {
		$output = $row['text_data'];
	// Otherwise die
	} else {
		http_response_code(404);
		die("Not found");
	}

	// Getting the correct mime type, with a default fallback
	// TODO: kinda brittle
	$mime = $row['mime_type'] ?: "application/octet-stream";
	// If text data, fallback for text
	if ($row['blob_data'] === null && $row['text_data'] !== null && !$row['mime_type']) {
		$mime = "text/plain; charset=utf-8";
	}

	// File name for download
	$name = $row['file_name'] ?: ("output_" . $output_id);

	// Caching
	$etag = '"' . sha1($output) . '"';
	header("ETag: $etag");
	header("Cache-Control: private, no-cache, must-revalidate, max-age=0");

	if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
		http_response_code(304);
		die();
	}

	// Header according to type (view/download) 
	header("Content-Type: $mime");
	if ($download) {
		header("Content-Disposition: attachment; filename=\"" . addslashes($name) . "\"");
	} else {
	header("Content-Disposition: inline; filename=\"" . addslashes($name) . "\"");
	}

	echo $output;
	die();

} catch (Throwable $e) {
	http_response_code(500);
	die();
}

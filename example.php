<?php // Debugged using ELM (GPT 5.2), https://elm.edina.ac.uk/elm-new
// Example set page
// Providing information about the example data set results and interpretation

session_start();
require_once 'set_cookies.php';
require_once 'login.php';

// Database connection, adapted from class code
try {
	$dsn = "mysql:host=127.0.0.1;dbname=$database;charset=utf8mb4";
	$conn = new PDO($dsn, $username, $password);
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Throwable $e) {
	http_response_code(500);
	die("Database connection failed");
}

// Retrieve information for latest example job
// TODO: add an occasional run to update example set
try {
	$stmt = $conn->prepare("
	SELECT jobs.job_id, jobs.status, jobs.error_message, jobs.query_id, jobs.job_params,
	queries.protein_family, queries.taxon
	FROM jobs
	JOIN queries ON queries.query_id = jobs.query_id
	WHERE jobs.is_example = 1
	AND queries.protein_family = ?
	AND queries.taxon = ?
	ORDER BY jobs.job_date DESC, jobs.job_id DESC
	LIMIT 1
	");
	$stmt->execute(['glucose-6-phosphatase', 'aves']);
	$job = $stmt->fetch(PDO::FETCH_ASSOC);

	// Checking if exists
	if (!$job) {
		die("No example dataset found.");
	}

	$jid = (int)$job['job_id'];

} catch (Throwable $e) {
	http_response_code(500);
	die("Could not retrieve example dataset");
}

echo <<<_HTML
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Example Dataset</title>
</head>
<body>
_HTML;

include 'menuf.php';

echo <<<_HTML2
<header>
<h2>Example Dataset</h2>
<p>This website includes a precomputed example analysis using 
<b>glucose-6-phosphatase</b> proteins from <i>Aves</i> (birds).</p>
</header><hr />

<section>
<h3>Process Outline</h3>
<ul>
<li>A multiple sequence alignment of the retrieved proteins using <a href="https://www.ebi.ac.uk/jdispatcher/msa/clustalo" target="_blank">Clustal Omega</a></li>
<li>A <a href="https://www.bioinformatics.nl/cgi-bin/emboss/plotcon" target="_blank">plotcon</a> conservation plot showing how sequence conservation changes along the alignment</li>
<li>A motif scan using <a href="https://prosite.expasy.org/" target="_blank">PROSITE</a> patterns via EMBOSS <a href="https://www.bioinformatics.nl/cgi-bin/emboss/patmatmotifs" target="_blank">patmatmotifs</a></li>
</ul>
</section><hr />

<section>
<h3>Results Outline</h3>
<p>This example is intended as a quick demonstration of the website output before you run your own query.
You can review the results page, inspect the conservation plot, and download the available outputs.</p>
<p>The results contain:</p>
<ul>
<li><b>Plotcon plot</b>, available to view and download. The higher the graph, the more conserved the residue in this position.</li>
<li><b>Summary statistics</b> such as the number of sequences, represented organisms, alignment length, and most common motif</li>
<li><b>Text file results</b> of the MSA and motif hits summary available to download</li>
<li><b>Alignment and motif overview tables</b> that can be filtered and downloaded</li>
</ul>
</section> <hr />

<h3>Navigation Menu</h3>
<nav aria-label="primary-navigation">
<ul>
<li><a href="#query_param">Query Parameters</a></li>
<li><a href="#plotcon_res">Plotcon Results</a></li>
<li><a href="#summary">Summary Statistics</a></li>
<li><a href="#files">Text Files</a></li>
<li><a href="#alignment_ajax">Alignment Overview</a></li>
<li><a href="#motif_ajax">Motif Overview</a></li>
<li><a href="/~s2883992/website/query">New Query</a></li>
</ul>
</nav>
<hr />
_HTML2;

// Making sure example exists
require_once 'results_content.php';
render_results_content($conn, $job, $jid);
echo <<<_HTML3
<hr />
<p><a href='/~s2883992/website/query'>Go to query page</a></p>
</body>
</html>
_HTML3
?>


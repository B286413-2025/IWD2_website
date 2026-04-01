<?php 
// Debugged with ELM (GPT 5.2), https://elm.edina.ac.uk/elm-new
// Previous results page - list jobs belonging to current user

session_start();
require_once 'set_cookies.php';
require_once 'login.php';

// Checking user_hash
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
} catch (Throwable $e) {
	http_response_code(500);
	die("Database connection failed");
}

// Retrieve information about previous jobs for the current user
try {
	// Summary counts by status
	$stmt = $conn->prepare("
	SELECT status, COUNT(*) AS n
	FROM jobs
	WHERE user_hash = ?
	AND is_example = 0
	GROUP BY status
	");
	$stmt->execute([$user_hash]);
	$counts_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

	// Initializing an associative array
	$counts = [
	'total' => 0,
	'pending' => 0,
	'complete' => 0,
	'error' => 0
	];

	// Going through query, counting results
	foreach ($counts_raw as $row) {
		$st = (string)$row['status'];
		$n = (int)$row['n'];
		if (isset($counts[$st])) {
			$counts[$st] = $n;
			$counts['total'] += $n;
		}
	}

} catch (Throwable $e) {
	http_response_code(500);
	die("Could not retrieve previous jobs.");
}

// HTML information
echo <<<_HTML
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="/~s2883992/website/styles.css">
<title>Previous Results</title>
</head>
<body>
_HTML;

include 'cookies.html';
include 'menuf.php';

echo <<<_HEADER
<main class='history-shell'>
<header class='page-title'>
<h1>Previous Results</h1>
<p>Here you can view all analyses previously run from this browser.</p>
</header><hr>
_HEADER;

// Informative message if no previous results, link to query page
if ($counts['total'] === 0) {
	echo <<<_BODY
<p><b>No previous jobs were found for this user.</b></p>
<p><a href='/~s2883992/website/query'>Submit a new query</a></p>
<p>Or check out this <a href='/~s2883992/website/example'>example dataset</a> for more information.</p>
</main></body></html>
_BODY;
    die();
}

// Summary counts
echo "<section><h2>Summary</h2>";
echo "<ul>";
echo "<li><b>Total jobs:</b> " . htmlspecialchars((string)($counts['total'])) . "</li>";
echo "<li><b>Completed:</b> " . htmlspecialchars(($counts['complete'])) . "</li>";
echo "<li><b>Pending:</b> " . htmlspecialchars(($counts['pending'])) . "</li>";
echo "<li><b>Errors:</b> " . htmlspecialchars(($counts['error'])) . "</li>";
echo "</ul></section><hr>";

// Filters - displaying options
echo <<<_FILTERS
<section class="history-panel" id="job_filters">
<h2>Filter Jobs</h2>

<div class="ajax-controls-grid">
<div>
<label title="Filter jobs by their current processing status.">Status:</label>
<!-- Search by job type -->
<select id='prev_status' title="Show all jobs, or only pending, complete, or error jobs.">
	<option value=''>All</option>
	<option value='pending'>Pending</option>
	<option value='complete'>Complete</option>
	<option value='error'>Error</option>
</select>
</div>
<!-- Or by protein/taxon pattern -->
<div>
<label title="Filter by protein family or taxonomic group.">Search protein/taxon:</label>
<input type='text' id='prev_search' placeholder='(optional)' title="Type part of a protein family name or taxon to filter the table.">
</div>
</div>
<div class="ajax-actions-row">
<button type='button' id='prev_update' class='update-button'>Update</button>
<button type='button' id='prev_reset' class='reset-button'>Reset Filters</button>
<span id='prev_status_msg' class="ajax-status"></span>
</div>

<h3>Matching Jobs</h3>
<div class="previous-table-wrap">
<div id="prev_results_wrap"></div>
</div>
</section>
_FILTERS;

// JS functionality for dynamic filtering
// Adapted from ELM (GPT 5.2), https://elm.edina.ac.uk/elm-new
echo <<<_JS
<script>
(function(){
	// Function to build URL
	function buildUrl() {
		// Filtering parameters
		const status = document.getElementById('prev_status').value;
		const search = document.getElementById('prev_search').value.trim();
	
		// URL based on filter parameters
		const url = new URL('/~s2883992/website/previous_results_ajax.php', window.location.origin);
		if (status !== '') {
			url.searchParams.set('status', status);
		}
		if (search !== '') {
			url.searchParams.set('search', search);
		}
		return url;
	}

	// Function to render the final table
	function renderTable(rows) {
		const wrap = document.getElementById('prev_results_wrap');

		// No results response
		if (!rows || rows.length === 0) {
			wrap.innerHTML = '<p><i>No jobs match the current filters.</i></p>';
			return;
		}

		// Table in HTML syntax
		// TODO: perhaps only present error preview if not all are empty
		let html = '<table class="previous-table">';
		// Headers
		html += '<tr>';
		html += '<th title="Internal job ID assigned by the website.">Job ID</th>';
		html += '<th title="Date and time when the job was created.">Date</th>';
		html += '<th title="Protein family used in the query.">Protein</th>';
		html += '<th title="Taxonomic group used in the query.">Taxon</th>';
		html += '<th title="Current job status.">Status</th>';
		html += '<th title="Plotcon window size used for this job.">Window Size</th>';
		html += '<th title="Requested plotcon output format.">Plot Format</th>';
		html += '<th title="Requested Clustal Omega output format.">MSA Format</th>';
		html += '<th title="Open the job page. Pending jobs open the loading page, completed or failed jobs open the results page.">Link</th>';
		html += '<th title="Summary of dataset size before and after filtering.">Dataset</th>';
		html += '</tr>';

		// Table rows
		for (const row of rows) {
			// Initializing parameters
			let link = '';
			let label = '';

			// Deciding link and label based on status
			if (row.status === 'pending') {
				link = '/~s2883992/website/loading/' + row.job_id;
				label = 'Processing';
			} else {
				link = '/~s2883992/website/results/' + row.job_id;
				label = 'View';
			}
			
			// Adding table data
			html += '<tr>';
			html += '<td>' + row.job_id + '</td>';
			html += '<td>' + row.job_date + '</td>';
			html += '<td>' + row.protein_family + '</td>';
			html += '<td>' + row.taxon + '</td>';
			html += '<td>' + row.status + '</td>';
			html += '<td>' + (row.win_size ?? 4) + '</td>';
			html += '<td>' + (row.plot_outfmt ?? 'png') + '</td>';
			html += '<td>' + (row.clust_outfmt ?? 'fasta') + '</td>';
			html += '<td><a href="' + link + '">' + label + '</a></td>';
			html += '<td>' + (row.dataset_summary ?? '') + '</td>';
//			html += '<td>' + (row.error_preview ?? '') + '</td>';
			html += '</tr>';
		}

		html += '</table>';
		wrap.innerHTML = html;
	}

	// Reset function
	function resetPreviousResultsFilters() {
		document.getElementById('prev_status').value = '';
		document.getElementById('prev_search').value = '';
	}

	// Async function to update table
	async function updatePreviousResults() {
		const msg = document.getElementById('prev_status_msg');
		msg.textContent = 'Loading...';

		// Trying to fetch
		try {
			const res = await fetch(buildUrl().toString(), { cache: 'no-store' });
			const data = await res.json();

			// Checking status
			if (!data.ok) {
				msg.textContent = 'Error: ' + (data.error || 'unknown');
				return;
			}

			msg.textContent = 'Rows: ' + (data.rows ? data.rows.length : 0);
			renderTable(data.rows);

		} catch (e) {
			msg.textContent = 'Error fetching previous results.';
		}
	}

	// Updating or resetting on click
	document.getElementById('prev_update').addEventListener('click', updatePreviousResults);
	document.getElementById('prev_reset').addEventListener('click', function () {
		resetPreviousResultsFilters();
		updatePreviousResults();
	});
	updatePreviousResults();
})();
</script>
_JS;

echo "</main></body></html>";
?>

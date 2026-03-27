<?php // Debugged with ELM (GPT 5.2), https://elm.edina.ac.uk/elm-new

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

// HTML infromation
echo <<<_HTML
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Previous Results</title>
</head>
<body>
_HTML;

include 'menuf.php';

echo <<<_HEADER
<header>
<h2>Previous Results</h2>
<p>This page lists previous analyses associated with your browser on this website.</p>
</header><hr />
_HEADER;

// Informative message if no previous results, link to query page
if ($counts['total'] == 0) {
	echo <<<_BODY
<p><b>No previous jobs were found for this user.</b></p>
<p><a href='/~s2883992/website/query'>Submit a new query</a></p>
<p>Or check out this <a href='/~s2883992/website/example'>example dataset</a> for more information.</p>
</body></html>
_BODY;
    die();
}

// Summary counts
echo "<section><h3>Summary</h3>";
echo "<ul>";
echo "<li>Total jobs: " . htmlspecialchars((string)($counts['total'])) . "</li>";
echo "<li>Complete: " . htmlspecialchars(($counts['complete'])) . "</li>";
echo "<li>Pending: " . htmlspecialchars(($counts['pending'])) . "</li>";
echo "<li>Error: " . htmlspecialchars(($counts['error'])) . "</li>";
echo "</ul></section><hr />";

// Filters - echoing options
echo <<<_FILTERS
<div>
<label>Status:</label>
<select id='prev_status'>
	<option value=''>All</option>
	<option value='pending'>Pending</option>
	<option value='complete'>Complete</option>
	<option value='error'>Error</option>
</select>
<label>Search protein/taxon:</label>
<input type='text' id='prev_search'>
<button type='button' id='prev_update'>Update</button>
</div>

<div id='prev_status_msg' style='margin-top:10px;'></div>
<div id='prev_results_wrap' style='margin-top:10px;'></div>
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
	
		// URL
		const url = new URL('previous_results_ajax.php', window.location.href);
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

		// No results
 		if (!rows || rows.length === 0) {
			wrap.innerHTML = '<p><i>No jobs match the current filter.</i></p>';
			return;
		}

		// Table in HTML syntax
		// TODO: perhaps only present error preview if not all are empty
		let html = '<table border="1" cellpadding="6" cellspacing="0">';
		// Headers
		html += '<tr>';
		html += '<th>Job ID</th>';
		html += '<th>Date</th>';
		html += '<th>Protein</th>';
		html += '<th>Taxon</th>';
		html += '<th>Status</th>';
		html += '<th>Window Size</th>';
		html += '<th>Plot Format</th>';
		html += '<th>MSA Format</th>';
		html += '<th>Error Preview</th>';
		html += '<th>Link</th>';
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

			html += '<tr>';
			html += '<td>' + row.job_id + '</td>';
			html += '<td>' + row.job_date + '</td>';
			html += '<td>' + row.protein_family + '</td>';
			html += '<td>' + row.taxon + '</td>';
			html += '<td>' + row.status + '</td>';
			html += '<td>' + (row.win_size ?? 4) + '</td>';
			html += '<td>' + (row.plot_outfmt ?? 'png') + '</td>';
			html += '<td>' + (row.clust_outfmt ?? 'fasta') + '</td>';
			html += '<td>' + (row.error_preview ?? '') + '</td>';
			html += '<td><a href="' + link + '">' + label + '</a></td>';
			html += '</tr>';
		}

		html += '</table>';
		wrap.innerHTML = html;
	}

	// Async function to update table
	async function updatePreviousResults() {
		const msg = document.getElementById('prev_status_msg');
		msg.textContent = 'Loading...';

		// Trying to fetch based on promises
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

	// Updating on click
	document.getElementById('prev_update').addEventListener('click', updatePreviousResults);
	updatePreviousResults();
})();
</script>
_JS;

echo "<p><a href='/~s2883992/website/query'>Submit a new query</a></p>";
echo "</body></html>";
?>

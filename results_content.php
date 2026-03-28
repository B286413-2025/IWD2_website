<?php 
// Adapted from class code, debugged with ELM (GPT 5.2), https://elm.edina.ac.uk/elm-new
// Separating results rendering from wrapper


// Function to render the analysis results content based on:
// PDO connection, $conn
// array of job record features from the jobs table, $job
// int job id, $jid
function render_results_content(PDO $conn, array $job, int $jid): void
{
	// Base dir for pretty URLs
	$BASE = '/~s2883992/website';

	// Query information
	echo "<article id='query_param'>";
	echo "<h2>Query parameters</h2>";
	echo "<p><b>Protein:</b> " . htmlspecialchars((string)$job['protein_family']) . "<br>";
	echo "<b>Taxon:</b> " . htmlspecialchars((string)$job['taxon']) . "<br>";
	echo "<b>Job ID:</b> " . htmlspecialchars((string)$jid) . "</p>";
	echo "</article><hr />";

	// Checking for job status
  // Error case - informative message and suggestion
	if ($job['status'] === 'error') {
		echo "<p style='color:red'><b>An error has occurred</b> &#128533</p>";
		echo "<pre>" . htmlspecialchars((string)($job['error_message'] ?? '')) . "</pre>";
		echo "<p>You can try again or submit another query: <a href='" . $BASE . "/query'>back to query page</a></p>";
		return;
	}
	// Pending case
	// TODO: perhaps remove?
	if ($job['status'] === 'pending') {
		echo "<p><i>This job is still processing.</i></p>";
		return;
	}

	// Decoding job parameters
	$params = [];
	if (!empty($job['job_params'])) {
		$tmp = json_decode((string)$job['job_params'], true);
		if (is_array($tmp)) {
			$params = $tmp;
		}
	}

	// Complete case - displaying results
	
  // Plotcon
	// Retrieving analysis information (ouput ID, mime type, file name and relevant parameters)
	$stmt = $conn->prepare("
		SELECT output_id, mime_type, file_name, parameters
		FROM analysis_outputs
		WHERE job_id = ? AND analysis_type='plotcon'
		ORDER BY created_at DESC, output_id DESC
		");
	$stmt->execute([(int)$jid]);
	$plot_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

	// Displaying plotcon output and offering download with get_output.php
	echo "<article id='plotcon_res'>";
	echo "<h2>Plotcon Conservation Plot</h2>";
	if (!$plot_rows) {
		echo "<p><b>Plotcon output not found for this job.</b></p>";
	} else {
		$requested_fmt = $params['plot_outfmt'] ?? '';
		// Going over plot output
    // Initializing 
		$png_row = null;
		$req_row = null;
		foreach ($plot_rows as $r) {
			$mime = (string)($r['mime_type'] ?? '');
			// If the mime type is png, update png
      if ($png_row === null && $mime === 'image/png') {
				$png_row = $r;
			}
			// If not and no previous out format
			if ($requested_fmt !== '' && $req_row === null && !empty($r['parameters'])) {
				// Get the analysis parameters
				$p = json_decode((string)$r['parameters'], true);
				// Verify the format
				if (is_array($p) && strtolower((string)($p['graph'] ?? '')) === $requested_fmt) {
					$req_row = $r;
				}
			}
		}

		// Displaying results on web page
		// Locating type, preferably png
		$display_row = $png_row;
		// If no png
		if ($display_row === null) {
			// Get mime type
			foreach ($plot_rows as $r) {
				$mime = (string)($r['mime_type'] ?? '');
				// Use if "image/"
				if (str_starts_with($mime, 'image/')) { 
					$display_row = $r; 
					break; 
				}
			}
		}
		// Web image
		if ($display_row) {
			$oid = (int)$display_row['output_id'];
      // Clickable
			echo "<a href='" . $BASE . "/get_output.php?output_id=" . $oid . "' target='_blank'>";
			// In-page
      echo "<img src='" . $BASE . "/get_output.php?output_id=" . $oid . "' alt='plotcon'>";
			echo "</a>";
			echo "<p><i>Click for the full-size version.</i></p>";
		} else {
			echo "<p><i>No browser-displayable plotcon image found.</i></p>";
		}

		// Download links with get_output.php
		echo "<div class='button-group'>";
		// png default
		if ($png_row) {
			$oid = (int)$png_row['output_id'];
			echo "<a class='button-link' href='" . $BASE . "/get_output.php?output_id=" . $oid . "&download=1'>Download PNG</a>";
		}
		// Requested format if different
		if ($req_row && $requested_fmt !== 'png') {
			$oid = (int)$req_row['output_id'];
			$fname = htmlspecialchars((string)($req_row['file_name'] ?? 'requested_plot'));
			echo ($png_row ? " | " : "");
			echo "<a class='button-link secondary' href='" . $BASE . "/get_output.php?output_id=" . $oid . "&download=1'>Download requested format</a> (" . $fname . ")";
		}
		echo "</div>";
	}

	echo "</article><hr />";

	// Summary statistics
	// Query internal ID
  $qid = (int)$job['query_id'];

	// Number of sequences
	$stmt = $conn->prepare("SELECT COUNT(*) FROM seq_group WHERE query_id=?");
	$stmt->execute([$qid]);
	$n_seqs = (int)$stmt->fetchColumn();

	// Number of unique organisms
	$stmt = $conn->prepare("
		SELECT COUNT(DISTINCT sequences.organism)
		FROM seq_group
		JOIN sequences ON sequences.accession = seq_group.accession
		WHERE seq_group.query_id=?
		");
	$stmt->execute([$qid]);
	$n_org = (int)$stmt->fetchColumn();

	// Alignment length (min/max, verifying match later)
	$stmt = $conn->prepare("
		SELECT MIN(LENGTH(aligned_sequence)) AS min_len,
		MAX(LENGTH(aligned_sequence)) AS max_len
		FROM aligned_sequences
		WHERE job_id=?
		");
	$stmt->execute([(int)$jid]);
	$aln = $stmt->fetch(PDO::FETCH_ASSOC);
	
	// Fallback if not int
	$aln_min = (int)($aln['min_len'] ?? 0);
	$aln_max = (int)($aln['max_len'] ?? 0);

	// Mean raw sequence length
	$stmt = $conn->prepare("
		SELECT AVG(LENGTH(sequences.sequence))
		FROM seq_group
		JOIN sequences ON sequences.accession = seq_group.accession
		WHERE seq_group.query_id = ?
		");
	$stmt->execute([$qid]);
	$mean_len = (float)$stmt->fetchColumn();

	// Most common motif and number of occurrences
	$stmt = $conn->prepare("
		SELECT motif_name, COUNT(*) AS n
		FROM motif_hits
		WHERE job_id = ?
		GROUP BY motif_name
    ORDER BY n DESC
	  LIMIT 1
		");
	$stmt->execute([(int)$jid]);
	$top = $stmt->fetch(PDO::FETCH_ASSOC);
	// Fallbacks if none were found
	$top_motif = $top ? $top['motif_name'] : 'None';
	$top_motif_n = $top ? (int)$top['n'] : 0;

	// Number of motif types
	$stmt = $conn->prepare("
		SELECT COUNT(DISTINCT motif_name)
		FROM motif_hits
		WHERE job_id = ?
		");
	$stmt->execute([(int)$jid]);
	$n_motif_types = (int)$stmt->fetchColumn();

	// Echoing HTML table
	// TODO: perhaps download summary statistics?
	echo "<article id='summary'>";
	echo "<h2>Summary statistics</h2>";
	echo "<table class='summary-table'>";
	echo "<tr><th>Protein</th><td>" . htmlspecialchars((string)$job['protein_family']) . "</td></tr>";
	echo "<tr><th>Taxon</th><td>" . htmlspecialchars((string)$job['taxon']) . "</td></tr>";
	echo "<tr><th>Number of sequences</th><td>" . htmlspecialchars((string)$n_seqs) . "</td></tr>";
	echo "<tr><th>Number of unique organisms</th><td>" . htmlspecialchars((string)$n_org) . "</td></tr>";

	// Checking that min and max alignment lengths are the same
	if ($aln_min !== $aln_max) {
		echo "<tr><th>Min alignment length</th><td>" . htmlspecialchars((string)$aln_min) . "</td></tr>";
		echo "<tr><th>Max alignment length</th><td>" . htmlspecialchars((string)$aln_max) . "</td></tr>";
	} else {
		echo "<tr><th>Alignment length</th><td>" . htmlspecialchars($aln_min) . "</td></tr>";
	}

  // Continue table
	echo "<tr><th>Mean raw seq length</th><td>" . htmlspecialchars((string)number_format($mean_len, 2)) . "</td></tr>";
	echo "<tr><th>Top motif (number of occurrences)</th><td>" . htmlspecialchars((string)$top_motif) . " (" . htmlspecialchars((string)$top_motif_n) . ")</td></tr>";
	echo "<tr><th>Number of motif types</th><td>" . htmlspecialchars($n_motif_types) . "</td></tr>";
	echo "</table></article><hr />";

	// Download results
	// MSA
	// Getting report
  $stmt = $conn->prepare("
		SELECT output_id, file_name
		FROM analysis_outputs
    WHERE job_id = ? AND analysis_type='msa'
    ORDER BY created_at DESC, output_id DESC
		");
	$stmt->execute([(int)$jid]);
	$msa_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  
  // Some HTML for article start
	echo <<<_FILES
	<article id='files'>
	<h2>Text Files</h2>
	<h3>MSA Files</h3>
	_FILES;
  
  // Getting report from query ouput, offering download with get_output.php
	if (!$msa_rows) {
		echo "<p><i>No alignment outputs saved for this job.</i></p>";
	} else {
		echo "<div class='button-group'>";
		foreach ($msa_rows as $r) {
			$oid = (int)$r['output_id'];
			$fname = htmlspecialchars((string)($r['file_name'] ?? ("msa_" . $oid)));
			echo "<a class='button-link' href='" . $BASE . "/get_output.php?output_id=" . $oid . "&download=1'>Download MSA: " . $fname . "</a>";
		}
		echo "</div>";
	}

	// Motifs report, offering download with download_motif_hits.php
	echo "<h3>Motif report</h3>";
	echo "<div class='button-group'>";
	echo "<a class='button-link' href='" . $BASE . "/download_motif_hits.php?job_id=" . (int)$jid . "'>Download motif hits: TSV</a>";
	echo "</div></article><hr />";

	// Alignment Overview
	// AJAX table adapted from ELM (GPT 5.2) code, https://elm.edina.ac.uk/elm-new
	echo <<<_ALIGNMENT
	<article id='alignment_ajax'>
	<h2>Alignment Overview</h2>
	<div class="ajax-panel">
	<div class="ajax-controls-grid">
	<!--
	Table filtering options: # rows, sorting values, sort direction, organism name
	-->
	<div>
		<label>Rows (max 1000)</label>
		<input type='number' id='aln_limit' value='50' min='1' max='1000'>
	</div>
	<!--
	Sorting values: gap fraction, gap count, sequence length, organism, accession
	-->
	<div>
		<label>Sort Field</label>
		<select id='aln_sort'>
		<option value='gap_fraction' selected>Gap Fraction</option>
		<option value='gap_count'>Gap Count</option>
		<option value='raw_len'>Sequence Length</option>
		<option value='organism'>Organism</option>
		<option value='accession'>Accession</option>
		</select>
	</div>
	<div>
		<label>Direction</label>
		<select id='aln_dir'>
		<option value='desc' selected>Descending</option>
		<option value='asc'>Ascending</option>
		</select>
	</div>
	<!--
	Organism partial match search
	-->
	<div>
		<label>Organism Contains</label>
		<input type='text' id='aln_organism' placeholder='(optional)'>
	</div>
	<div>
		<label>Minimum Gap Count</label>
		<input type='number' id='aln_min_gap_count' min='0' step='1' placeholder='(optional)'>
	</div>
	<div>
		<label>Minimum Gap Fraction</label>
		<input type='number' id='aln_min_gap_fraction' min='0' max='1' step='0.0001' placeholder='(optional)'>
	</div>
	</div>
	<!--
	Possible fields to include in table: organism, accession, seq length, gap count, gap fraction
	-->
	<div class="ajax-fields-row">
		<b>Show fields:</b>
		<label><input type='checkbox' class='aln_field' value='organism' checked> Organism</label>
		<label><input type='checkbox' class='aln_field' value='accession' checked> Accession</label>
		<label><input type='checkbox' class='aln_field' value='raw_len' checked> Sequence length</label>
		<label><input type='checkbox' class='aln_field' value='gap_count' checked> Gap count</label>
		<label><input type='checkbox' class='aln_field' value='gap_fraction' checked> Gap fraction</label>
		<label><input type='checkbox' id='show_aligned'> Show Aligned Sequence</label>
	</div>
	<!--
	Action buttons: update table and download current table
	-->
	<div class="ajax-actions-row">
		<button type='button' id='aln_update' class='update-button'>Update Table</button>
		<a id='aln_download' class='button-link download-button' href='#'>Download Current Table (TSV)</a>
		<span id='aln_status' class='ajax-status'></span>
	</div>
	<div id='aln_table_wrap'></div>
	</div>
	</article>
	<hr />
	_ALIGNMENT;

	// JS for ajax functionality
	$jid_js = (int)$jid;
	echo <<<_JS
	<script>
	// JavaScript function to manipulate table
	// Adapted from ELM (GPT 5.2) generated code, https://elm.edina.ac.uk/elm-new
	(function(){
		const jobId = $jid_js;

		// Mapping field labels to a table header
		const fieldLabels = {
		organism: 'Organism',
		accession: 'Accession',
		raw_len: 'Sequence Length',
		gap_count: 'Gap Count',
		gap_fraction: 'Gap Fraction'
		};

		// Returning all checked columns
		function selectedFields() {
			return Array.from(document.querySelectorAll('.aln_field:checked')).map(x => x.value);
		}

		// Function to print sequence nicely, slicing to fixed-size subsequences
		// Taking sequence and desired width (default 36), returning a the sliced string joined by newline chars
		function wrapSeq(seq, width) {
			if (!seq) {
				return '';
			}
			const w = width || 36;
			let parts = [];
			for (let i = 0; i < seq.length; i += w) {
				parts.push(seq.slice(i, i + w));
			}
			return parts.join('\\n');
		}
	
		// Function to build URL for table update and download
		function buildAlignmentUrl(baseFile) {
			// Getting field values
			const limit = document.getElementById('aln_limit').value;
			const sort = document.getElementById('aln_sort').value;
			const dir = document.getElementById('aln_dir').value;
			const org = document.getElementById('aln_organism').value.trim();
			const minGapCount = document.getElementById('aln_min_gap_count').value.trim();
			const minGapFraction = document.getElementById('aln_min_gap_fraction').value.trim();
			const showAligned = document.getElementById('show_aligned').checked;
			const fields = selectedFields();
	
			// Building URL
			const url = new URL(baseFile, window.location.href);
			url.searchParams.set('job_id', jobId);
			url.searchParams.set('limit', limit);
			url.searchParams.set('sort', sort);
			url.searchParams.set('dir', dir);
			
			// Optional params
			if (org !== '') {
				url.searchParams.set('organism_like', org);
			}
			if (minGapCount !== '') {
				url.searchParams.set('min_gap_count', minGapCount);
			}
			if (minGapFraction !== '') {
				url.searchParams.set('min_gap_fraction', minGapFraction);
			}

			url.searchParams.set('include_aligned', showAligned ? '1' : '0');
			if (fields.length > 0) {
				url.searchParams.set('fields', fields.join(','));
			}
			return url;
		}
	
		// A function to render the table based on the checked fields and selected rows
		function renderTable(rows, fields, showAligned) {
			const wrap = document.getElementById('aln_table_wrap');
			// No results
			if (!rows || rows.length === 0) {
				wrap.innerHTML = '<p><i>No rows to display.</i></p>';
				return;
			}

			// Building table with HTML syntax
			let html = '<table class="ajax-table">';
			
			// If aligned sequence is shown, reserve a fixed-width final column
			if (showAligned) {
				html += '<colgroup>';
				for (let i = 0; i < fields.length; i++) {
					html += '<col>';
				}
				html += '<col class="sequence-col">';
				html += '</colgroup>';
			}

			// Header row, mapping fields to header cells
			html += '<tr>';
			html += fields.map(f => '<th>' + (fieldLabels[f] || f) + '</th>').join('');
			if (showAligned) html += '<th class="sequence-head">Aligned Sequence</th>';
			html += '</tr>';

			// Table rows
			for (const r of rows) {
				html += '<tr>';

				// And cell values, with fallback
				for (const f of fields) {
					const val = (r && r[f] !== undefined && r[f] !== null) ? String(r[f]) : '';
					html += '<td>' + val + '</td>';
				}

				// Optional fasta sequence
				if (showAligned) {
					// Checking for aligned sequence and printing nicely
					if (r && r['aligned_sequence']) {
						const seqBlock = wrapSeq(String(r['aligned_sequence']), 36);
					html += '<td class="sequence-cell"><pre>' + seqBlock + '</pre></td>';
					} else {
					html += '<td class="sequence-cell"><i>(no aligned sequence)</i></td>';
					}
				}
				html += '</tr>';
			}
			html += '</table>';
			wrap.innerHTML = html;
		}
		// async funtcion to update table upon promises
		async function update() {
			// Selected fields
			const fields = selectedFields();
			const showAligned = document.getElementById('show_aligned').checked;
	
			// Output status
			const status = document.getElementById('aln_status');
			status.textContent = 'Loading...';

			// Updating download link according to chosen parameters
			document.getElementById('aln_download').href = buildAlignmentUrl('/~s2883992/website/download_alignment_ajax.php').toString();

			// Trying to fetch the data
			try {
				const url = buildAlignmentUrl('/~s2883992/website/alignment_ajax.php');
				const res = await fetch(url.toString(), { cache: 'no-store' });
				const data = await res.json();
	
				// Checking data status from alignment_ajax.php
				if (!data.ok) {
					status.textContent = 'Error: ' + (data.error || 'unknown');
					return;
				}
				if (data.status && data.status !== 'complete') {
					status.textContent = 'Job status: ' + data.status;
					return;
				}

				status.textContent = 'Rows: ' + (data.rows ? data.rows.length : 0);
				renderTable(data.rows, fields, showAligned);
			} catch (e) {
				status.textContent = 'Error fetching alignment data.';
			}
		}

		// Updataing upon click
		document.getElementById('aln_update').addEventListener('click', update);
		update();
	})();
	</script>
	_JS;

	// Motif overview
	// Setting table filtering options
	echo <<<_MOTIF
	<article id="motif_ajax">
	<h2>Motif Overview</h2>
	<div class="ajax-panel">
	<!--
	Sorting values - filter columns, direction, optional filters, columns to show
	-->
	<div class="ajax-controls-grid">
	<div>
		<label>Rows (max 1000)</label>
		<input type="number" id="mot_limit" value="50" min="1" max="1000">
	</div>
	<!--
	Srting fields - motif name, organism, accession, start and end position, score
	-->
	<div>
		<label>Sort Field</label>
		<select id="mot_sort">
		<option value="motif_name" selected>Motif</option>
		<option value="organism">Organism</option>
		<option value="accession">Accession</option>
		<option value="start_pos">Start Position</option>
		<option value="end_pos">End Position</option>
		<option value="score">Score</option>
		</select>
	</div>
	<div>
		<label>Direction</label>
		<select id="mot_dir">
		<option value="asc" selected>Ascending</option>
		<option value="desc">Descending</option>
		</select>
	</div>
	<!--
	Partial organism pattern filter
	-->
	<div>
		<label>Organism contains</label>
		<input type="text" id="mot_organism" placeholder="(optional)">
	</div>
	<!-- 
	Partial motif name pattern filter
	-->
	<div>
		<label>Motif contains</label>
		<input type="text" id="mot_name" placeholder="(optional)">
	</div>
	<div>
		<label>Minimum score</label>
		<input type="number" id="mot_score" step="0.01" placeholder="(optional)">
	</div>
	</div>
	<!--
	Columns to present in the table
	-->
	<div class="ajax-fields-row">
		<b>Show fields:</b>
		<label><input type="checkbox" class="mot_field" value="organism" checked> Organism</label>
		<label><input type="checkbox" class="mot_field" value="accession" checked> Accession</label>
		<label><input type="checkbox" class="mot_field" value="motif_name" checked> Motif</label>
		<label><input type="checkbox" class="mot_field" value="start_pos" checked> Start</label>
		<label><input type="checkbox" class="mot_field" value="end_pos" checked> End</label>
		<label><input type="checkbox" class="mot_field" value="score" checked> Score</label>
		<label><input type="checkbox" class="mot_field" value="matched_sequence"> Matched Sequence</label>
	</div>
	<!--
	Action buttons
	-->
	<div class="ajax-actions-row">
	<button type="button" id="mot_update" class="update-button">Update Table</button>
	<a id="mot_download" class="button-link download-button" href="#">Download Current Table (TSV)</a>
	<span id="mot_status" class="ajax-status"></span>
	</div>
	<div id="mot_table_wrap"></div>
	</div>
	</article>
	<hr />
	_MOTIF;

	// JS for ajax functionality
	echo <<<_MOTIFJS
	<script>
	// JS function to manipulate motif overview table
	// Adaptef from ELM (GPT 5.2) code, https://elm.edina.ac.uk/elm-new
	(function(){
		const jobId = $jid_js;
		// Mapping table column names with IDs
		const fieldLabels = {
			organism: 'Organism',
			accession: 'Accession',
			motif_name: 'Motif',
			start_pos: 'Start',
			end_pos: 'End',
			score: 'Score',
			matched_sequence: 'Matched Sequence'
		};

		// Function to return all checked column options
		function selectedMotifFields() {
			return Array.from(document.querySelectorAll('.mot_field:checked')).map(x => x.value);
		}

		// Function to build and return URL to for rendering and download
		function buildMotifUrl(baseFile) {
			// Getting values for selected parameters
			const limit = document.getElementById('mot_limit').value;
			const sort = document.getElementById('mot_sort').value;
			const dir = document.getElementById('mot_dir').value;
			const organism = document.getElementById('mot_organism').value.trim();
			const motif = document.getElementById('mot_name').value.trim();
			const score = document.getElementById('mot_score').value.trim();
			const fields = selectedMotifFields();
	
			// Building URL
			const url = new URL(baseFile, window.location.href);
			url.searchParams.set('job_id', jobId);
			url.searchParams.set('limit', limit);
			url.searchParams.set('sort', sort);
			url.searchParams.set('dir', dir);
			// Optional parameters
			if (organism !== '') {
				url.searchParams.set('organism_like', organism);
			}
			if (motif !== '') {
				url.searchParams.set('motif_like', motif);
			}
			if (score !== '') {
				url.searchParams.set('min_score', score);
			}
			if (fields.length > 0) {
				url.searchParams.set('fields', fields.join(','));
			}
			return url;
		}

	// Function to render the table
	function renderMotifTable(rows, fields) {
		const wrap = document.getElementById('mot_table_wrap');

		// If no results
		if (!rows || rows.length === 0) {
			wrap.innerHTML = '<p><i>No rows to display.</i></p>';
			return;
		}

		// Building the table in HTML syntax based on returned results
		let html = '<table class="ajax-table">';
		// Header row
		html += '<tr>';
		html += fields.map(f => '<th>' + (fieldLabels[f] || f) + '</th>').join('');
		html += '</tr>';
		
		// Content rows
		for (const r of rows) {
		html += '<tr>';
			// And cells
			for (const f of fields) {
				const val = (r && r[f] !== undefined && r[f] !== null) ? String(r[f]) : '';
				html += '<td>' + val + '</td>';
			}
		html += '</tr>';
		}

		html += '</table>';
		wrap.innerHTML = html;
	}
	
	// Asynch function to update the table upon motif promise
	async function updateMotifs() {
		// Filters and status
		const fields = selectedMotifFields();
		const status = document.getElementById('mot_status');
		status.textContent = 'Loading...';

		// Updating download link
		const dl = document.getElementById('mot_download');
		dl.href = buildMotifUrl('/~s2883992/website/download_motif_ajax.php').toString();
	
		// Trying to fetch
		try {
			const url = buildMotifUrl('/~s2883992/website/motif_ajax.php');
			const res = await fetch(url.toString(), { cache: 'no-store' });
			const data = await res.json();

			// Checking data is ok
			if (!data.ok) {
				status.textContent = 'Error: ' + (data.error || 'unknown');
				return;
			}
			// And status 
			if (data.status && data.status !== 'complete') {
				status.textContent = 'Job status: ' + data.status;
				return;
			}

			// Rendering table based on content
			status.textContent = 'Rows: ' + (data.rows ? data.rows.length : 0);
			renderMotifTable(data.rows, fields);

		} catch (e) {
			status.textContent = 'Error fetching motif data.';
		}
	}
	
	// Updating on click
	document.getElementById('mot_update').addEventListener('click', updateMotifs);
	updateMotifs();
	})();
	</script>
	_MOTIFJS;
}

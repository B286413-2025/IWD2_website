<?php 
// Adapted from class code and from ELM (GPT 5.2), https://elm.edina.ac.uk/elm/elm
// Processing query from the command line with php after form submission
// Takes jid as an argument, running the python scripts and inserts data to MySQL

require_once 'login.php';

// Making sure the script is only run from the command line, adapted from ELM
if (php_sapi_name() !== 'cli') {
	http_response_code(403);
	die("CLI only");
}

// Checking for jid
$jid = isset($argv[1]) ? (int)$argv[1] : 0;
if ($jid <= 0) {
	die(1);
}

// Functions
// Function for system call, returning exit code
// Taking as arguments command and an array for output
function run_cmd($cmd, &$out_lines) {
	exec($cmd . " 2>&1", $out_lines, $rc);
	return $rc;
}

// Cleanup function for the end, deleting files and temp directory	
// Debugged with ELM (GPT 5.2), https://elm.edina.ac.uk/elm-new
function cleanup_workdir($dir) {
	//  Checking dir exists
	if (!$dir || !is_dir($dir)) return;
	// Deleting files
	$files = glob($dir . "/*");
	if ($files !== false) {
		foreach ($files as $f) {
			if (is_file($f)) {
				@unlink($f);
			}
		}
	}
	// Removing directory
	@rmdir($dir);
}

// Function to update errors, stopping script and cleaning workdir
$workdir = null;
function job_error($conn, $jid, $msg) {
	// Getting workdir
	$dir = $GLOBALS['workdir'] ?? null;
	// Updating error
	try {
		$stmt = $conn->prepare("UPDATE jobs SET status='error', error_message=? WHERE job_id=?");
		$stmt->execute([$msg, $jid]);
	} catch (Throwable $e) {}
	// Cleaning up
	cleanup_workdir($dir);
	die(1);
}

// Function to find newest plotcon file because Plotcon returns unexpected file names...
// Returning most recent file based on the known file prefix
// Generated with ELM (GPT 5.2) help
function newest_plot($prefix) {
	$best = null;
	$files = glob($prefix . "*");
	if ($files !== false && count($files) > 0) {
		foreach ($files as $f) {
			if ($best === null || filemtime($f) > filemtime($best)) {
				$best = $f;
			}
		}
	}
	return $best;
}

// MySQL connection, adapted from class code
try {
        $dsn = "mysql:host=127.0.0.1;dbname=$database;charset=utf8mb4";
	$conn = new PDO($dsn, $username, $password, 
		array(PDO::MYSQL_ATTR_LOCAL_INFILE => true, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
} catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
}

// Trying to get parameters from jobs and queries table
try {
	$stmt = $conn->prepare("
	SELECT jobs.job_id, jobs.query_id, jobs.status, jobs.is_example, jobs.job_params,
	queries.protein_family, queries.taxon
	FROM jobs
	JOIN queries ON queries.query_id = jobs.query_id
	WHERE jobs.job_id = ?
	LIMIT 1
	");
	$stmt->execute([$jid]);
	$job = $stmt->fetch(PDO::FETCH_ASSOC);
	// Exiting if failed to retrieve
	if (!$job) {
		die(1);
	}

	// Avoiding double-processing if already processed
	if ($job['status'] !== 'pending') {
		die(0);
	}
	
	// Extracting parameters
	$qid = $job['query_id'];
	$params = [];
	// Making sure result is not empty
	if (!empty($job['job_params'])) {
		$tmp = json_decode((string)$job['job_params'], true);
		// And the right type
		if (is_array($tmp)) {
			$params = $tmp;
		}
	}

	// Using existing parameters, otherwise fall-back
	$taxon = $params['taxon'] ?? $job['taxon'];
	$prot_fam = $params['prot_fam'] ?? $job['protein_family'];
	$win_size = isset($params['win_size']) ? (int)$params['win_size'] : 4;
	$plot_outfmt = strtolower((string)($params['plot_outfmt'] ?? 'png'));
	$clust_outfmt = strtolower((string)($params['clust_outfmt'] ?? 'fasta'));

	// Sequence filtering defaults
	$retmax = isset($params['retmax']) ? (int)$params['retmax'] : 1000;
	$minlen = isset($params['minlen']) ? (int)$params['minlen'] : 50;
	$maxlen = isset($params['maxlen']) ? (int)$params['maxlen'] : 2000;
	$max_x_frac = isset($params['max_x_frac']) ? (float)$params['max_x_frac'] : 0.05;
	$max_total_aa = isset($params['max_total_aa']) ? (int)$params['max_total_aa'] : 200000;
	$max_kept = isset($params['max_kept']) ? (int)$params['max_kept'] : 500;

	// Verifying parameters
	if ($win_size < 1 || $win_size > 100) $win_size = 4;
	
	$allowed_plot = ['png','pdf','svg','gif','data','ps','hpgl','meta'];
	if (!in_array($plot_outfmt, $allowed_plot, true)) {
		$plot_outfmt = 'png';
	}
	
	$allowed_clust = ['fasta','clustal','msf','phylip','selex','stockholm','vienna'];
	if (!in_array($clust_outfmt, $allowed_clust, true)) {
		$clust_outfmt = 'fasta';
	}

	if ($retmax < 1) {
		$retmax = 1000;
	}

	if ($minlen < 0) {
		$minlen = 0;
	}
	if ($maxlen < $minlen) {
		$maxlen = 2000;
	}

	if ($max_x_frac < 0) {
		$max_x_frac = 0.;
	}

	if ($max_x_frac > 1) {
		$max_x_frac = 1.0;
	}

	if ($max_total_aa < 1) {
		$max_total_aa = 200000;
	}

	if ($max_kept < 2) {
		$max_kept = 500;
	}

} catch (Throwable $e) {
	job_error($conn, $jid, "Failed to fetch job / parameters: " . $e->getMessage());
}

// Script directories
$base_dir = __DIR__;
$download_py = $base_dir . '/py_scripts/download_sequences.py';
$msa_py = $base_dir . '/py_scripts/msa_to_sql.py';
$motif_py = $base_dir . "/py_scripts/patmat_to_sql.py";

// Creating working directory
// Based on ELM (GPT 5.2) code, https://elm.edina.ac.uk/elm/elm
$workdir = sys_get_temp_dir() . "/bioapp_" . $jid . "_" . bin2hex(random_bytes(4)); // Creating a unique temporary working directory per session
if (!mkdir($workdir, 0700, true)) {
	job_error($conn, $jid, "Failed to create working directory.");
}

// Sequence output directories
// TODO: rename files with a more informative name...
$seq_tsv = $workdir . "/example_record.tsv";
$seq_fa = $workdir . "/example_record.fasta";

// Running python script for sequence download
$download_cmd = 'python3 ' . escapeshellarg($download_py) 
	. ' --protein ' . escapeshellarg($prot_fam) 
	. ' --taxon ' . escapeshellarg($taxon) 
	. ' --outdir '  . escapeshellarg($workdir)
	. ' --retmax ' . escapeshellarg((string)$retmax)
	. ' --minlen ' . escapeshellarg((string)$minlen)
	. ' --maxlen ' . escapeshellarg((string)$maxlen)
	. ' --max_x_frac ' . escapeshellarg((string)$max_x_frac)
	. ' --max_total_aa ' . escapeshellarg((string)$max_total_aa)
	. ' --max_kept ' . escapeshellarg((string)$max_kept);

$download_out=[];
$download_rc = run_cmd($download_cmd, $download_out);

// Stopping if exit code is not zero 
if ($download_rc !== 0) {
	job_error($conn, $jid, "Download failed:\n" . implode("\n", $download_out));
}

// Parsing result print from py script
$match_num = 0;
$raw_match_num = null;
$total_aa_kept = null;

foreach ($download_out as $line) {
	$line = trim($line);
	// Skip if line is empty
	if ($line === '') {
		continue;
	}

	// The first numeric line is the usable sequence count
	if ($match_num === 0 && ctype_digit($line)) {
		$match_num = (int)$line;
		continue;
	}

	// Checking for other output based on str pattern
	if (str_starts_with($line, 'NCBI matches:')) {
		// Getting the integer only
		$raw_match_num = (int)trim(substr($line, strlen('NCBI matches:')));
		continue;
	}

	if (str_starts_with($line, 'Total amino acids kept:')) {
		// Getting the integer only
		$total_aa_kept = (int)trim(substr($line, strlen('Total amino acids kept:')));
		continue;
	}
}

// Stopping execuation for under two usable sequences
if ($match_num < 2) {
job_error($conn, $jid, "Less than 2 sequences found for this query.");
}

// Making sure the files exist
if (!file_exists($seq_tsv) || !file_exists($seq_fa)) {
	job_error($conn, $jid, "Download finished but output files were not created.");
}
// Storing filter metadata and observed counts in job_params
try {
// Filters
$params['retmax'] = $retmax;
$params['minlen'] = $minlen;
$params['maxlen'] = $maxlen;
$params['max_x_frac'] = $max_x_frac;
$params['max_total_aa'] = $max_total_aa;
$params['max_kept'] = $max_kept;

// Returned results
$params['kept_num'] = $match_num;

// Calculating filtered out sequences
if ($raw_match_num !== null) {
	$params['raw_match_num'] = $raw_match_num;
	$params['filtered_out_num'] = max(0, $raw_match_num - $match_num);
}

if ($total_aa_kept !== null) {
	$params['total_aa_kept'] = $total_aa_kept;
}

// Updating jobs
$stmt = $conn->prepare("UPDATE jobs SET job_params=? WHERE job_id=?");
$stmt->execute([json_encode($params, JSON_UNESCAPED_SLASHES), $jid]);

// Non-fatal error, analysis can continue
} catch (Throwable $e) {}

// Inserting into MySQL database, debugged using ELM (GPT 5.2), https://elm.edina.ac.uk/elm-new
try {
	// Only inserting entire jobs
	$conn->beginTransaction();

	// Temporary table to allow upserting,
	// Informed by: https://stackoverflow.com/questions/15271202/mysql-load-data-infile-with-on-duplicate-key-update
	$conn->exec("DROP TEMPORARY TABLE IF EXISTS seq_temp");
	$conn->exec("
	CREATE TEMPORARY TABLE seq_temp (
	accession VARCHAR(255) NOT NULL,
	organism VARCHAR(255) NOT NULL,
	sequence LONGTEXT NOT NULL
	)
	");

	// Loading sequences into temp
	$conn->exec("
        LOAD DATA LOCAL INFILE " . $conn->quote($seq_tsv) . "
	INTO TABLE seq_temp
	FIELDS TERMINATED BY '\t'
	LINES TERMINATED BY '\n'
	(accession, organism, sequence)
	");

	// Upserting into sequences
	// TODO: think about it for per-job results integrity
	$conn->exec("
	INSERT INTO sequences (accession, organism, sequence)
	SELECT accession, organism, sequence FROM seq_temp
	ON DUPLICATE KEY UPDATE
	organism = VALUES(organism),
	sequence = VALUES(sequence)
	");

	// Mapping to seq_group, ignoring duplicates
	$conn->exec("
	INSERT IGNORE INTO seq_group (query_id, accession)
	SELECT " . (int)$qid . ", accession FROM seq_temp
	");

	// Dropping temporary table
	$conn->exec("DROP TEMPORARY TABLE seq_temp;");

	// Comitting all changes together
	$conn->commit();

} catch (Throwable $e) {
	// Closing connection
	if ($conn->inTransaction()) {
		$conn->rollBack();
	};

	// Terminating if cannot load data
	job_error($conn, $jid, "Database load stage failed: " . $e->getMessage());
}

// Performing MSA
// Setting directiories and command
$msa_base  = $workdir . "/aligned_job_" . $jid;
$msa_fasta = $msa_base . ".fasta";
$msa_tsv = $msa_base . ".tsv";
$msa_cmd = "python3 " . escapeshellarg($msa_py) . " "
         . escapeshellarg($seq_fa) . " fasta "
         . escapeshellarg($msa_base) . " "
         . escapeshellarg($msa_tsv);

$msa_out = [];
$msa_rc = run_cmd($msa_cmd, $msa_out);

// Checking exit code and files existence, recording error in database
if ($msa_rc !== 0 || !file_exists($msa_tsv) || !file_exists($msa_fasta)) {
	job_error($conn, $jid, "MSA failed:\n" . implode("\n", $msa_out));
}

// Loading alignments and storing MSA file in analysis_outputs
try {
	// Load aligned sequences from tsv
	$conn->exec("
	LOAD DATA LOCAL INFILE " . $conn->quote($msa_tsv) . "
	INTO TABLE aligned_sequences
	FIELDS TERMINATED BY '\t'
	LINES TERMINATED BY '\n'
	(accession, aligned_sequence)
	SET job_id = " . (int)$jid . "
	");
} catch (Throwable $e) {
	job_error($conn, $jid, "Failed loading aligned_sequences: " . $e->getMessage());
}

// Store aligned FASTA text as an output
// TODO: perhaps irrelevant because can be recreated with SQL queries
try {
	$msa_text = file_get_contents($msa_fasta);
	$stmt = $conn->prepare("
	INSERT INTO analysis_outputs (job_id, analysis_type, mime_type, file_name, parameters, text_data)
	VALUES (?, 'msa', 'text/plain', ?, ?, ?)
	");
	$stmt->execute([
		$jid,
		"aligned_job_" . $jid . ".fasta",
		json_encode(['tool' => 'clustalo', 'outfmt' => 'fasta']),
		$msa_text
	]);
} catch (Throwable $e) {
	// Updating failure and stopping	
	// TODO: perhaps not stop because not fatal
	job_error($conn, $jid, "Failed storing MSA output: " . $e->getMessage());
}

// Storing user requested format for download
// TODO: perhaps increase number of threads, it's a big nice server
if ($clust_outfmt !== 'fasta') {
	$alt_file = $msa_base . "." . $clust_outfmt;
	$alt_cmd = "clustalo -i " . escapeshellarg($seq_fa) .
		" --outfmt " . escapeshellarg($clust_outfmt) .
		" -o " . escapeshellarg($alt_file) .
		" --threads 12 --force";
	$alt_out = [];
	$alt_rc = run_cmd($alt_cmd, $alt_out);

	// Updating analysis outputs in case of success
	if ($alt_rc === 0 && file_exists($alt_file)) {
		try {
			$alt_text = file_get_contents($alt_file);
			$stmt = $conn->prepare("
			INSERT INTO analysis_outputs (job_id, analysis_type, mime_type, file_name, parameters, text_data)
			VALUES (?, 'msa', 'text/plain', ?, ?, ?)
			");
			$stmt->execute([
			$jid,
			"aligned_job_" . $jid . "." . $clust_outfmt,
			json_encode(['tool'=>'clustalo','outfmt'=>$clust_outfmt]),
			$alt_text
			]);
		} catch (Throwable $e) {}
	}
}

// Running plotcon
// Requested output
$plot_prefix_req = $workdir . "/plotcon_job_" . $jid . "_" . $plot_outfmt;

// Building command, parameters for future flexible script
// TODO: Potential editions (though they don't work well):
// -gtitle $title
// -gxtitle $xlab
// -gytitle $ylab
$plot_cmd = "plotcon " .
	"-sequences " . escapeshellarg($msa_fasta) .
	" -winsize " . (int)$win_size .
	" -graph " . $plot_outfmt .
	" -goutfile " . escapeshellarg($plot_prefix_req) .
	" -auto";

$plot_out = [];
$plot_rc  = run_cmd($plot_cmd, $plot_out);

// Stopping if exit code is not zero
if ($plot_rc !== 0) {
	job_error($conn, $jid, "plotcon failed:\n" . implode("\n", $plot_out));
}

// Finding newest output by plotcon 
$plot_path = newest_plot($plot_prefix_req);
if ($plot_path === null || !file_exists($plot_path)) {
	job_error($conn, $jid, "plotcon produced no output file (requested format).");
}

// Read outputs
$plot_bytes = file_get_contents($plot_path);

// Store in analysis_outputs as blob 

// mime type mapping
$mime_map = [
'png' => 'image/png',
'gif' => 'image/gif',
'pdf' => 'application/pdf',
'svg' => 'image/svg+xml',
'ps'  => 'application/postscript',
'data'=> 'text/plain',
'hpgl'=> 'application/octet-stream',
'meta'=> 'application/octet-stream',
];
$plot_mime = $mime_map[$plot_outfmt] ?? 'application/octet-stream';

// Trying to insert to DB
try {
	$stmt = $conn->prepare("
	INSERT INTO analysis_outputs
	(job_id, analysis_type, mime_type, file_name, parameters, blob_data)
	VALUES
	(?, 'plotcon', ?, ?, ?, ?)
	");
	$stmt->execute([
	$jid,
	$plot_mime,
	basename($plot_path),
	json_encode(['winsize'=>$win_size, 'graph'=>$plot_outfmt]),
	$plot_bytes
	]);

} catch (Throwable $e) {
	job_error($conn, $jid, "Failed storing plotcon output: " . $e->getMessage());
}

// png for display if needed
if ($plot_outfmt !== 'png') {
	$plot_prefix_png = $workdir . "/plotcon_job_" . $jid . "_png";
	$plot_cmd_png = "plotcon " .
		"-sequences " . escapeshellarg($msa_fasta) .
		" -winsize " . (int)$win_size .
		" -graph png" .
		" -goutfile " . escapeshellarg($plot_prefix_png) .
		" -auto";
	$plot_out_png = [];
	$plot_rc_png  = run_cmd($plot_cmd_png, $plot_out_png);

	// Verifying success
	if ($plot_rc_png !== 0) {
		job_error($conn, $jid, "plotcon PNG failed:\n" . implode("\n", $plot_out_png));
	}
	$png_path = newest_plot($plot_prefix_png);
	if ($png_path === null || !file_exists($png_path)) {
		job_error($conn, $jid, "plotcon PNG produced no output file.");
	}
	$png_bytes = file_get_contents($png_path);
	
	// Trying to insert output
	try {
		$stmt = $conn->prepare("
		INSERT INTO analysis_outputs
		(job_id, analysis_type, mime_type, file_name, parameters, blob_data)
		VALUES
		(?, 'plotcon', 'image/png', ?, ?, ?)
		");
		$stmt->execute([
		(int)$jid,
		basename($png_path),
		json_encode(['winsize'=>$win_size, 'graph'=>'png', 'purpose'=>'display']),
		$png_bytes
		]);
	} catch (Throwable $e) {
		job_error($conn, $jid, "Failed storing plotcon PNG: " . $e->getMessage());
	}
}

// patmatmotifs run and SQL loading
// Directories
$motif_hits_tsv = $workdir . "/motif_hits_job_" . $jid . ".tsv";

// Running python
$mot_cmd = "python3 " . escapeshellarg($motif_py) . " " .
	escapeshellarg($seq_tsv) . " " .
	escapeshellarg($motif_hits_tsv);

$mot_out = [];
$mot_rc  = run_cmd($mot_cmd, $mot_out);

// Updating jobs in case of an error
if ($mot_rc !== 0 || !file_exists($motif_hits_tsv)) {
	job_error($conn, $jid, "patmatmotifs failed:\n" . implode("\n", $mot_out));
}

// Trying loading motifs into MySQl
try {
	$load_motifs = "
		LOAD DATA LOCAL INFILE " . $conn->quote($motif_hits_tsv) . "
		INTO TABLE motif_hits
		FIELDS TERMINATED BY '\t'
		LINES TERMINATED BY '\n'
		(@acc, @motif, @start, @end, @score, @mseq)
		SET
		job_id = " . (int)$jid . ",
		accession = @acc,
		motif_name = @motif,
		start_pos = @start,
		end_pos = @end,
		score = NULLIF(@score, ''),
		matched_sequence = NULLIF(@mseq, '')
		";
	$conn->exec($load_motifs);

// Otherwise updating job failure
} catch (Throwable $e) {
	job_error($conn, $jid, "Failed loading motif_hits: " . $e->getMessage());
}

// Updating job status
try {
	$stmt = $conn->prepare("UPDATE jobs SET status='complete' WHERE job_id=?");
	$stmt->execute([$jid]);
} catch (Throwable $e) {
	job_error($conn, $jid, "Failed final status update: " . $e->getMessage());
}

cleanup_workdir($workdir);

exit(0);

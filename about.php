<?php
session_start();
require_once 'set_cookies.php';
$BASE = '/~s2883992/website';

echo <<<_HTML
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>About</title>
</head>
<body>
_HTML;

include 'menuf.php';

echo <<<_BODY
<header>
<h1>About This Website</h1>
<p>
This page gives a developer-oriented overview of how the website is structured,
which tools are used, and how the database supports the analysis workflow.
</p>

<h2>Navigation Menu</h2>
<nav aria-label="primary-navigation">
<ul>
<li><a href="#purpose">Overall Purpose</a></li>
<li><a href="#workflow">Main Workflow</a></li>
<li><a href="#pages">Main Pages</a></li>
<li><a href="#background_scripts">Background and Processing Scripts</a></li>
<li><a href="#py_scripts">Python and Analysis Scripts</a></li>
<li><a href="#database">Database Overview</a></li>
<li><a href="#tools">Tools Used</a></li>
<li><a href="#security">Data Security</a></li>
<li><a href="#url">URL Structure</a></li>
<li><a href="#limitations">Current Limitations</a></li>
</ul>
</nav>

</header>
<hr />

<section id="purpose">
<h2>1. Overall Purpose</h2>
<p>
This website retrieves protein sequence datasets for a selected protein family and taxonomic group,
runs a small bioinformatics analysis pipeline, stores the results in MySQL, and presents them back to the user.
</p>
</section>
<a href='#'>Back to Top</a>
<hr />

<section id="workflow">
<h2>2. Main Workflow</h2>
<ol>
<li>The user lands on the front page and receives a browser cookie used to associate jobs with that browser.</li>
<li>The user submits a query from the query page.</li>
<li>A job is created in the database with status <code>pending</code>.</li>
<li>A background worker processes the job:
	<ul>
	<li>Downloads sequences from NCBI</li>
	<li>Loads the dataset into MySQL</li>
	<li>Runs Clustal Omega</li>
	<li>Runs EMBOSS plotcon</li>
	<li>Runs EMBOSS patmatmotifs</li>
	</ul>
</li>
<li>The loading page polls job status until the job becomes <code>complete</code> or <code>error</code>.</li>
<li>The results page retrieves the stored outputs and summary statistics.</li>
</ol>
</section>
<a href='#'>Back to Top</a>
<hr />

<section id="pages">
<h2>3. Main Pages</h2>
<ul>
<li><b>front</b> - landing page with site overview and navigation</li>
<li><b>query.php</b> - query form for taxon, protein family, and analysis options</li>
<li><b>loading</b> - creates a pending job and waits for processing to finish</li>
<li><b>results</b> - wrapper page for completed results</li>
<li><b>results_content.php</b> - reusable results rendering block used by results and example pages</li>
<li><b>example</b> - explanatory page for the precomputed example dataset</li>
<li><b>previous_results</b> - lists previous jobs associated with the current browser</li>
<li><b>help</b> - user-facing biological help page</li>
<li><b>credit</b> - statement of credits and sources</li>
<li><b>not_found.php</b> - custom 404 page</li>
</ul>
</section>
<a href='#'>Back to Top</a>
<hr />

<section id="background_scripts">
<h2>4. Background and Processing Scripts</h2>
<ul>
<li><b>set_cookies.php</b> - creates and hashes the site cookie used for browser-level job ownership</li>
<li><b>process_query.php</b> - CLI worker that processes a job by job ID</li>
<li><b>get_output.php</b> - returns stored output files from the database</li>
<li><b>alignment_ajax.php</b> - returns alignment overview data as JSON</li>
<li><b>motif_ajax.php</b> - returns motif overview data as JSON</li>
<li><b>download_alignment_ajax.php</b> - exports filtered alignment tables as TSV</li>
<li><b>download_motif_ajax.php</b> - exports filtered motif tables as TSV</li>
<li><b>download_motif_hits.php</b> - exports total motif hits report as TSV</li>
</ul>
</section>
<a href='#'>Back to Top</a>
<hr />

<section id="py_scripts">
<h2>5. Python and Analysis Scripts</h2>
<ul>
<li><b>download_sequences.py</b> - retrieves sequence data from NCBI and writes TSV/FASTA outputs</li>
<li><b>msa_to_sql.py</b> - runs Clustal Omega and writes alignment output suitable for SQL loading</li>
<li><b>patmat_to_sql.py</b> - runs patmatmotifs and writes motif hits to a TSV suitable for SQL loading</li>
</ul>
</section>
<a href='#'>Back to Top</a>
<hr />

<section id="database">
<h2>6. Database Overview</h2>
<p>The database stores both reusable query/sequence information and job-specific outputs.</p>

<h3>Main Tables</h3>
<ul>
<li><b>queries</b> - unique combinations of protein family and taxon</li>
<li><b>sequences</b> - accession, organism, and raw sequence data</li>
<li><b>seq_group</b> - links queries to the sequences associated with them</li>
<li><b>jobs</b> - one row per analysis job, including status and JSON job parameters</li>
<li><b>aligned_sequences</b> - aligned sequences for a given job</li>
<li><b>analysis_outputs</b> - stored text or binary outputs such as MSA files and plotcon outputs</li>
<li><b>motif_hits</b> - structured motif hits from patmatmotifs</li>
</ul>

<h3>Storage model</h3>
<p>
Binary outputs such as images are stored in <code>blob_data</code>, and text outputs such as MSA reports are stored in <code>text_data</code>.
This avoids depending on writable web directories for persistent output storage.
</p>

<h3>Schema Diagram</h3>
<p>
The following diagram summarises the current database structure used by the website
(generated with <a href="https://app.chartdb.io/" target="_blank">ChartDB</a>).
</p>

<figure>
<a href="/~s2883992/website/images/website_diagram.png" target="_blank">
<img src="/~s2883992/website/images/website_diagram.png" alt="Database schema diagram for the website"
style="max-width:100%; height:auto; border:1px solid #ccc;">
</a>
<figcaption>
Figure: Database schema used by the website. Click the image to open the full-size version.
</figcaption>
</figure>

<p>
The full SQL script used to generate the database, which includes indexing and unique constraints as well, can be seen in 
<a href="https://github.com/algra2001/IWD2_website/blob/master/sql_scripts/maketables.sql" target="_blank">my personal GitHub repository</a>.
</p>
</section>
<a href='#'>Back to Top</a>
<hr />

<section id="tools">
<h2>7. Tools Used</h2>
<ul>
<li>PHP</li>
<li>MySQL</li>
<li>JavaScript (including AJAX for the results tables)</li>
<li>Python 3</li>
<li>Biopython</li>
<li>Clustal Omega</li>
<li>EMBOSS plotcon</li>
<li>EMBOSS patmatmotifs</li>
</ul>
</section>
<a href='#'>Back to Top</a>
<hr />

<section id="security">
<h2>8. Data Security</h2>
<p>
Jobs are associated with a browser through a cookie-derived hash stored in the database.
<br />This is used to reduce guessability of results and prevent direct access to another user's outputs by URL alone.
<br />Example jobs are separately marked and may be accessed without matching the user hash.
</p>
</section>
<a href='#'>Back to Top</a>
<hr />

<section id="url">
<h2>9. URL Structure</h2>
<p>
The website uses rewritten URLs through <code>.htaccess</code> so that pages can be accessed with cleaner paths
instead of explicit <code>.php</code> filenames in the visible URL.
</p>
</section>
<a href='#'>Back to Top</a>
<hr />

<section id="limitations">
<h2>10. Current Limitations</h2>
<ul>
<li>Large queries may take substantial time to process.</li>
<li>Sequence retrieval currently depends on external database availability and naming patterns.</li>
<li>Some results are query-level rather than strict job-level snapshots.</li>
<li>The user interface and CSS styling are still being refined.</li>
</ul>
</section>
<a href='#'>Back to Top</a>
<hr />

<p>
<a href="front">Front Page</a> |
<a href="query">Query Page</a> |
<a href="help">Help</a>
</p>

</body>
</html>
_BODY;
?>


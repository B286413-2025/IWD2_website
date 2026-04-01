<?php
// About page, with more technical details about the site flow
session_start();
require_once 'set_cookies.php';
$BASE = '/~s2883992/website';
session_write_close();

echo <<<_HTML
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link rel="stylesheet" href="/~s2883992/website/styles.css" />
<title>About</title>
</head>
<body>
_HTML;

include 'cookies.html';
include 'menuf.php';

// Sticky left navigation menu, informed by ELM (GPT 5.2), https://elm.edina.ac.uk/elm-new
echo <<<_NAV
<div class="page-shell">
<aside class="page-side-nav">
<h2>On this Page</h2>
<ul>
<li><a href="#purpose">Overall Purpose</a></li>
<li><a href="#workflow">Main Workflow</a></li>
<li><a href="#pages">Main Pages</a></li>
<li><a href="#scripts">Background Scripts</a></li>
<li><a href="#py_scripts">Python and Analysis Scripts</a></li>
<li><a href="#database">Database Overview</a></li>
<li><a href="#tools">Tools Used</a></li>
<li><a href="#security">Security and Access Model</a></li>
<li><a href="#url">URL Structure</a></li>
<li><a href="#limitations">Current Limitations</a></li>
<li><a href="#">Back to Top</a></li>
</ul>
</aside>
<main class="page-main">
<header class="page-title" id="intro">
<h1>About This Website</h1>
<p>
A more developer-oriented overview of how the website is structured,
which tools are used, and how the database supports the analysis workflow.
</p>
</header>
<hr />
_NAV;

echo <<<_BODY
<section id="purpose">
<h2>1. Overall Purpose</h2>
<p>
This website retrieves protein sequence datasets for a selected protein family and taxonomic group,
runs a small bioinformatics analysis pipeline, stores the results in MySQL, and presents them back to the user.
</p>
</section>
<hr />

<section id="workflow">
<h2>2. Main Workflow</h2>
<ol>
<li>The user lands on the home page and receives a browser cookie used to associate jobs with that browser.</li>
<li>The user submits a query from the query page.</li>
<li>A job is created in the database with status <code>pending</code>.</li>
<li>A background worker processes the job:
	<ul>
	<li>Downloads sequences from NCBI</li>
	<li>Runs Clustal Omega</li>
	<li>Runs EMBOSS plotcon</li>
	<li>Runs EMBOSS patmatmotifs</li>
	<li>Loads the generated outputs to MySQL while preserving job information for each step</li>
	</ul>
</li>
<li>The loading page polls job status until the job becomes <code>complete</code> or <code>error</code>, and refreshes every 3 seconds</li>
<li>The results page retrieves the stored outputs, summary statistics and interactive tables for further exploration.</li>
</ol>
<h3>Note:</h3>
<p>
The website runs on a server used for multiple purposes. To prevent extremely large interactive jobs from overwhelming the web workflow 
(for example, aligning <a href="https://en.wikipedia.org/wiki/Titin" target="_blank">titin</a> proteins &#128552;),
sequence retrieval is filtered by minimum length, maximum length, ambiguous residue content, and total retained dataset size (aa and sequence number).
The applied thresholds and observed retained counts are stored along with other job parameters.
</p>
</section>
<hr />

<section id="pages">
<h2>3. Main Pages</h2>
<p>
The main pages featured on this site, connected by session ID.
<br />These are mainly wrapper pages rendering background scripts details in the next section.
<br />Full scripts can be seen in my personal 
<a href="https://github.com/algra2001/IWD2_website", target="_blank">GitHub repository</a>.
</p>
<ul>
<li><b>front</b> - landing home page with site overview and navigation</li>
<li><b>query</b> - query form for taxon, protein family, and analysis options</li>
<li><b>loading</b> - creates a pending job and waits for processing to finish</li>
<li><b>results</b> - wrapper page for presenting completed results</li>
<li><b>example</b> - explanatory page for a precomputed example dataset</li>
<li><b>previous_results</b> - lists previous jobs associated with the current browser</li>
<li><b>help_page</b> - user-facing biological help page</li>
<li><b>credit</b> - statement of credits and sources used in creating the site</li>
<li><b>not_found.php</b> - custom 404 page</li>
</ul>
</section>
<hr />

<section id="scripts">
<h2>4. Background Scripts</h2>
<p>
Background PHP scripts that allow pages transition, manipulation and overall site functionality.
<br />Full scripts can be seen in my personal 
<a href="https://github.com/algra2001/IWD2_website" target="_blank">GitHub repository</a>.
</p>
<ul>
<li><b>set_cookies.php</b> - creates and hashes the site cookie used for browser-level job ownership</li>
<li><b>process_query.php</b> - CLI worker that processes a job by job ID</li>
<li><b>results_content.php</b> - reusable results rendering block used by results and example pages</li>
<li><b>get_output.php</b> - returns stored output files (MSA and plotcon) from the database, either for presenting or download</li>
<li><b>alignment_ajax.php</b> - returns alignment overview data for table update</li>
<li><b>motif_ajax.php</b> - returns motif overview data for table update</li>
<li><b>download_alignment_ajax.php</b> - exports filtered alignment tables as TSV</li>
<li><b>download_motif_ajax.php</b> - exports filtered motif tables as TSV</li>
<li><b>download_motif_hits.php</b> - exports total motif hits report as TSV</li>
</ul>
</section>
<hr />

<section id="py_scripts">
<h2>5. Python and Analysis Scripts</h2>
<p>
Analysis python scripts for data retrieval and analysis. All generate a TSV file suitable for SQL loading along with the outputs.
<br />Full scripts can be seen in my personal
<a href="https://github.com/algra2001/IWD2_website/tree/master/py_scripts" target="_blank">GitHub repository</a>.
</p>
<ul>
<li><b>download_sequences.py</b> - retrieves sequence data from NCBI</li>
<li><b>msa_to_sql.py</b> - runs Clustal Omega</li>
<li><b>patmat_to_sql.py</b> - runs patmatmotifs on each sequence</li>
</ul>
</section>
<hr />

<section id="database">
<h2>6. Database Overview</h2>
<p>The database stores both reusable query/sequence information and job-specific outputs.</p>

<h3>Main Tables</h3>
<p>The tables used to store analysis outputs and manage user-job data.</p>
<ul>
<li><b>queries</b> - contains unique combinations of protein family and taxon</li>
<li><b>sequences</b> - contains accession, organism, and raw sequence data</li>
<li><b>seq_group</b> - links queries to the sequences associated with them</li>
<li><b>jobs</b> - contains per job metadata, including status and JSON job parameters</li>
<li><b>aligned_sequences</b> - aligned sequences (MSA) for a given job</li>
<li><b>analysis_outputs</b> - per analysis stored results (text or binary), like MSA file and plotcon output</li>
<li><b>motif_hits</b> - motif hit results from patmatmotifs</li>
</ul>

<h3>Storage Model</h3>
<p>
All outputs are stored in the database. Binary outputs such as images are stored in <code>blob_data</code>, and text outputs such as MSA reports are stored in <code>text_data</code>.
<br />This avoids depending on writable web directories for persistent output storage, and allows easy and comprehensive output querying.
</p>

<h3>Schema Diagram</h3>
<p>
The following diagram summarises the current database structure used by the website
(generated with <a href="https://app.chartdb.io/" target="_blank">ChartDB</a>).
</p>

<figure>
<a href="/~s2883992/website/images/website_diagram.png" target="_blank">
<img src="/~s2883992/website/images/website_diagram.png" alt="Database schema diagram for the website">
</a>
<figcaption>
<i>Database schema used by the website.</i>
</figcaption>
</figure>

<p>
The full SQL script used to generate the database, which includes indexing and unique constraints as well, can be seen in my personal 
<a href="https://github.com/algra2001/IWD2_website/blob/master/sql_scripts/maketables.sql" target="_blank">GitHub repository</a>.
</p>
</section>
<hr />

<section id="tools">
<h2>7. Tools Used</h2>
<ul>
<li><a href="https://www.php.net/releases/8.2/en.php" target="_blank">PHP 8.2.8</a></li>
<li><a href="https://dev.mysql.com/downloads/mysql/8.0.html" target="_blank">MySQL 8.0.45</a></li>
<li>JavaScript (browser-native)</li>
<li><a href="https://www.python.org/downloads/release/python-3135/" target="_blank">Python 3.13.5</a></li>
<li><a href="https://biopython.org/wiki/Download" target="_blank">Biopython 1.86</a></li>
<li><a href="https://www.ebi.ac.uk/jdispatcher/msa/clustalo" target="_blank">Clustal Omega 1.2.4</a></li>
<li><a href="https://www.bioinformatics.nl/cgi-bin/emboss/plotcon" target="_blank">EMBOSS 6.6.0.0 plotcon</a></li>
<li><a href="https://www.bioinformatics.nl/cgi-bin/emboss/patmatmotifs" target="_blank">EMBOSS 6.6.0.0 patmatmotifs</a></li>
</ul>
</section>
<hr />

<section id="security">
<h2>8. Data Security</h2>
<p>
Jobs are associated with a browser through a cookie-derived hash stored in the database.
<br />This is used to reduce guessability of results and prevent direct access to another user's outputs by URL alone.
<br />Example job is separately marked and may be accessed without matching the user hash.
</p>
</section>
<hr />

<section id="url">
<h2>9. URL Structure</h2>
<p>
The website uses rewritten URLs through <code>.htaccess</code> so that pages can be accessed with cleaner paths
instead of explicit <code>.php</code> filenames in the visible URL.
</p>
</section>
<hr />

<section id="limitations">
<h2>10. Current Limitations</h2>
<p>The website is currently limited in a number of ways, as development is active.</p>
<ul>
<li>Large queries may take substantial time to process.</li>
<li>Sequence retrieval currently depends on external database availability and naming patterns.</li>
<li>Some results are query-level rather than strict job-level snapshots.</li>
<li>The user interface and CSS styling are still being refined.</li>
<li>Additional analyses and external resources are still not fully integrated into the website.</li>
<li>Current worker is automatically launched, ideally a queuing system would be implemented.</li>
</ul>
</section>
<hr />
</main>
</div>
</body>
</html>
_BODY;
?>


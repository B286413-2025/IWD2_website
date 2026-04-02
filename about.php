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
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="/~s2883992/website/styles.css">
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
<li><a href="#future">Future Directions</a></li>
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
<hr>
_NAV;

echo <<<_BODY
<section id="purpose">
<h2>1. Overall Purpose</h2>
<p>
This website retrieves protein sequence datasets for a selected protein family and taxonomic group,
runs a small bioinformatics analysis pipeline, stores the results in MySQL, and presents them back to the user.
</p>
</section>
<hr>

<section id="workflow">
<h2>2. Main Workflow</h2>
<ol>
<li>The user lands on the home page and receives a browser-specific cookie to associate jobs with that browser.</li>
<li>The user submits a query from the query page.</li>
<li>A job is created in the database with status <code>pending</code>.</li>
<li>A background worker processes the job:
	<ul>
	<li>Downloads sequences from NCBI</li>
	<li>Runs Clustal Omega</li>
	<li>Runs EMBOSS plotcon</li>
	<li>Runs EMBOSS patmatmotifs</li>
	<li>Loads the generated outputs to MySQL while documenting job information for each step</li>
	</ul>
</li>
<li>The loading page polls job status every 3 seconds until the job becomes <code>complete</code> or <code>error</code>.</li>
<li>The results page retrieves the stored outputs, summary statistics and interactive tables for exploration.</li>
</ol>
<h3>Note:</h3>
<p>
The website runs on a shared server. To prevent extremely large interactive jobs from overwhelming the workflow 
(for example, aligning <a href="https://en.wikipedia.org/wiki/Titin" target="_blank">titin</a> proteins &#128552;),
sequence retrieval is filtered by minimum length, maximum length, ambiguous residue content, and total retained dataset size.
All thresholds and retained counts are stored alongside the job parameters.
</p>
</section>
<hr>

<section id="pages">
<h2>3. Main Pages</h2>
<p>
The main pages of the site, connected via session-based ID, are detailed below.
<br>These pages primarily wrap the background scripts described in the next section.
<br>Full source code is available in my
<a href="https://github.com/B286413-2025/IWD2_website" target="_blank">GitHub repository</a>.
</p>
<ul>
<li><b>front</b> - landing page with site overview and navigation</li>
<li><b>query</b> - form for entering taxon, protein family, and analysis options (form validation with JavaScript)</li>
<li><b>loading</b> - creates a pending job and waits for processing to finish (automatic refresh, polling job status)</li>
<li><b>results</b> - wrapper page for presenting completed results</li>
<li><b>example</b> - explanatory page for a precomputed example dataset</li>
<li><b>previous_results</b> - lists previous jobs associated with the current browser</li>
<li><b>help_page</b> - user-facing help and interpretation guide</li>
<li><b>about</b> - web-developer oriented help page</li>
<li><b>credit</b> - statement of credits and sources used in creating the site</li>
<li><b>not_found.php</b> - custom 404 page</li>
</ul>
</section>
<hr>

<section id="scripts">
<h2>4. Background Scripts</h2>
<p>
Background PHP scripts that support page transitions, data retrieval, and overall site functionality.
<br>Full scripts are available in my 
<a href="https://github.com/B286413-2025/IWD2_website" target="_blank">GitHub repository</a>.
</p>
<ul>
<li><b>set_cookies.php</b> - creates and hashes the browser‑level cookie used for job ownership</li>
<li><b>process_query.php</b> - CLI worker that processes a job by job ID (runs python scripts, loads results to MySQL)</li>
<li><b>results_content.php</b> - results rendering script used by results and example pages 
(queries database, displays results on page with HTML and JavaScript)</li>
<li><b>get_output.php</b> - returns stored output files (MSA and plotcon) for display or download (forced header download)</li>
<li><b>alignment_ajax.php</b> - returns alignment overview data as JSON for interactive tables in results_content.php</li>
<li><b>motif_ajax.php</b> - returns motif overview data as JSON for interactive tables in results_content.php</li>
<li><b>download_alignment_ajax.php</b> - exports filtered alignment tables as TSV (alignment_ajax.php with forced header download)</li>
<li><b>download_motif_ajax.php</b> - exports filtered motif tables as TSV (motif_ajax.php with forced header download)</li>
<li><b>download_motif_hits.php</b> - exports total motif-hit summary as TSV (forced header download)</li>
</ul>
</section>
<hr>

<section id="py_scripts">
<h2>5. Python and Analysis Scripts</h2>
<p>
Python scripts used for data retrieval and analysis. Each script produces a TSV file suitable for SQL loading,
along with any associated outputs.
<br>Full scripts are available in my
<a href="https://github.com/B286413-2025/IWD2_website/tree/master/py_scripts" target="_blank">GitHub repository</a>.
</p>
<ul>
<li><b>download_sequences.py</b> - retrieves sequence data from NCBI using BioPython</li>
<li><b>msa_to_sql.py</b> - runs Clustal Omega and prepares alignment output</li>
<li><b>patmat_to_sql.py</b> - runs patmatmotifs on each sequence</li>
</ul>
</section>
<hr>

<section id="database">
<h2>6. Database Overview</h2>
<p>The database stores both reusable query/sequence information and job-specific outputs. A simplified schema can be seen 
<a href="#schema">below</a>.
<br>The full SQL script used to generate the database, including indexing and constraints, is available in my
<a href="https://github.com/B286413-2025/IWD2_website/blob/master/sql_scripts/maketables.sql" target="_blank">GitHub repository</a>.
</p>

<h3>Main Tables</h3>
<p>The tables used to store analysis outputs and manage user-job data.</p>
<ul>
<li><b>queries</b> - unique combinations of protein family and taxon</li>
<li><b>sequences</b> - accession, organism, and raw sequence data</li>
<li><b>seq_group</b> - links queries to the sequences associated with them</li>
<li><b>jobs</b> - job metadata, including status and parameters (JSON)</li>
<li><b>aligned_sequences</b> - aligned sequences (MSA) for each job</li>
<li><b>analysis_outputs</b> - stored analysis outputs (text or binary)</li>
<li><b>motif_hits</b> - motif hits from patmatmotifs</li>
</ul>

<h3>Indexing</h3>
<p>
The tables are indexed to support the most common queries.
Indexing logic was informed by this <a href="https://www.jamesmichaelhickey.com/database-indexes/" target="_blank">James Hickey article</a>.
</p>

<h3>Storage Model</h3>
<p>
All outputs are stored in the database. Binary outputs such as images are stored in <code>blob_data</code>, and text outputs such as MSA reports are stored in <code>text_data</code>.
<br>This provides several advantages:
</p>
<ul>
<li><b>Integrity:</b> all the data of the website is contained in a single relational database.</li>
<li><b>Security:</b> avoids relying on writable web directories, which can introduce permission issues or security risks on shared servers.</li>
<li><b>Consistency:</b> each output is tied to a specific job ID and stored with its metadata, ensuring that results remain stable and reproducible even if external tools or databases change.</li>
<li><b>Queryability:</b> storing all outputs in MySQL allows flexible retrieval, filtering, and joining with other job‑level information without needing to manage separate files.</li>
<li><b>Ownership:</b> because jobs are associated with a browser hash, storing everything in MySQL makes it easier to apply access rules and remove old jobs cleanly.</li>
</ul>

<h3 id="schema">Schema Diagram</h3>
<p>
The following diagram summarizes the current database structure used by the website
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

</section>
<hr>

<section id="tools">
<h2>7. Tools Used</h2>
<ul>
<li><a href="https://www.php.net/releases/8.2/en.php" target="_blank">PHP 8.2.8</a></li>
<li><a href="https://dev.mysql.com/downloads/mysql/8.0.html" target="_blank">MySQL 8.0.45</a></li>
<li>JavaScript (browser-native)</li>
<li><a href="https://www.python.org/downloads/release/python-3135/" target="_blank">Python 3.13.5</a></li>
<li><a href="https://biopython.org/docs/1.86/" target="_blank">Biopython 1.86</a></li>
<li><a href="https://www.ebi.ac.uk/jdispatcher/msa/clustalo" target="_blank">Clustal Omega 1.2.4</a></li>
<li><a href="https://www.bioinformatics.nl/cgi-bin/emboss/plotcon" target="_blank">EMBOSS 6.6.0.0 plotcon</a></li>
<li><a href="https://www.bioinformatics.nl/cgi-bin/emboss/patmatmotifs" target="_blank">EMBOSS 6.6.0.0 patmatmotifs</a></li>
</ul>
</section>
<hr>

<section id="security">
<h2>8. Data Security</h2>
<p>
Jobs are associated with a browser through a cookie hash stored in the database.
<br>This prevents access to another user’s results by guessing job IDs.
<br>The example job is marked separately and may be accessed without a matching user hash.
</p>
</section>
<hr>

<section id="url">
<h2>9. URL Structure</h2>
<p>
The website uses <code>.htaccess</code> rewrite rules to provide clean URLs
instead of exposing underlying <code>.php</code> filenames.
</p>
</section>
<hr>

<section id="limitations">
<h2>10. Current Limitations</h2>
<p>The website is currently limited in a number of ways.</p>
<ul>
<li>Large queries may take longer to process.</li>
<li>Sequence retrieval currently depends on external database availability and naming conventions.</li>
<li>Some results are query-level rather than strict job-level.</li>
<li>The user interface and CSS styling are still being refined.</li>
<li>Additional analyses and external resources are not yet fully integrated.</li>
<li>Current worker is automatically launched, ideally a proper queuing system would be implemented.</li>
</ul>
</section>
<hr>

<section id="future">
<h2>11. Future Directions</h2>
<p>
Development is ongoing, and several improvements are planned to expand functionality, improve performance,
and enhance the user experience. Planned future work includes:
</p>
<ul>
<li><b>Support for taxon IDs:</b> allowing queries using NCBI Taxonomy IDs in addition to scientific names.</li>
<li><b>Improved job scheduling:</b> replacing the current auto‑launched worker with a proper queuing system to handle multiple simultaneous jobs and possible server errors more efficiently.</li>
<li><b>Additional analyses:</b> further integration of external tools, for example more EMBOSS tools or motif databases.</li>
<li><b>Enhanced visualisations:</b> adding new plots such a protein structure or gap distribution.</li>
<li><b>User accounts:</b> optional login system to persist results across devices instead of relying solely on browser‑based identifiers.</li>
<li><b>Expanded filtering options:</b> finer control over sequence inclusion criteria and dataset preprocessing.</li>
<li><b>Query reanalysis:</b> resubmission of previously-performed queries with adjusted parameters.</li>
<li><b>UI and accessibility improvements:</b> continued refinement of layout, styling, and responsiveness.</li>
</ul>
</section>
<hr>

</main>
</div>
</body>
</html>
_BODY;
?>


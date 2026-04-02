<?php
// Help page explaining what the website can do
session_start();
require_once 'set_cookies.php';
session_write_close();

echo <<<_HTML
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="/~s2883992/website/styles.css">
<title>Help</title>
</head>
<body>
_HTML;

include 'cookies.html';
include 'menuf.php';

// Sticky left navigation menu
// Debugged with ELM (GPT 5.2), https://elm.edina.ac.uk/elm-new
echo <<<_NAV
<div class="page-shell">
<aside class="page-side-nav">
<h2>On this Page</h2>
<ul>
<li><a href="#function">What Can This Website Do?</a></li>
<li><a href="#query">How to Run a Query</a></li>
<li><a href="#outputs">Main Outputs</a></li>
<li><a href="#interpretation">How to Interpret the Outputs</a></li>
<li><a href="#valid_query">What Counts as a Valid Query</a></li>
<li><a href="#filtering">Filtering and Dataset Limits</a></li>
<li><a href="#general">Things to Keep in Mind</a></li>
<li><a href="#trouble">Troubleshooting</a></li>
<li><a href="#example">Example Dataset</a></li>
<li><a href="#">Back to Top</a></li>
</ul>
</aside>

<main class="page-main">
<header class="page-title" id="intro">
<h1>Help</h1>
<p>
Guidance on how to use the website, run queries, and interpret the main outputs.
</p>
</header>
<hr>
_NAV;

echo <<<_BODY
<section id="function">
<h2>What Can This Website Do?</h2>
<p>
This website retrieves protein sequences for a chosen protein family within a selected taxonomic group, aligns them, estimates sequence conservation, and searches for known motifs.
</p>
</section>
<hr>

<section id="query">
<h2>How to Run a Query</h2>
<ol>
<li>Go to the <a href="/~s2883992/website/query" target="_blank">query page</a>.</li>
<li>Enter a <b>taxonomic group</b> (e.g., <i>Aves</i>, <i>Mammalia</i>).</li>
<li>Enter a <b>protein name</b> (e.g., glucose-6-phosphatase, ABC transporter).</li>
<li>Optionally adjust the alignment and plot settings.</li>
<li>Submit the query and wait for the results page to appear.</li>
</ol>
</section>
<hr>

<section id="outputs">
<h2>Main Outputs</h2>
<h3>1. Plotcon Conservation Plot</h3>
<p>
This plot shows how strongly conserved the aligned sequences are across the dataset.
<br>It displays the conservation score (y-axis) across residue positions (x-axis). 
<br>Higher scores suggest greater similarity and stronger conservation, which often reflect functionally or structurally important regions.
</p>

<h3>2. Summary Statistics</h3>
<p>
The results page includes a summary table with values such as:
</p>
<ul>
<li>Number of sequences in the dataset</li>
<li>Number of unique organisms represented</li>
<li>Alignment length</li>
<li>Mean raw sequence length</li>
<li>Most common detected motif</li>
<li>Total number of motif types found</li>
</ul>

<h3>3. Text Files</h3>
<p>
Downloadable text outputs are provided, including the multiple sequence alignment and the summary of detected motif hits.
</p>

<h3>4. Alignment Overview</h3>
<p>
The section allows you to inspect aligned sequences and compare gap content across entries.
<br>High gap fractions may suggest more variable or divergent regions.
</p>

<h3>5. Motif Overview</h3>
<p>
This section lists PROSITE motifs detected in the dataset.
<br>You can examine motif names, coordinates, and matches across organisms.
</p>
</section>
<hr>

<section id="interpretation">
<h2>How to Interpret the Outputs</h2>
<p>A few general guidelines for interpreting the results:</p>
<ul>
<li><b>Highly conserved regions</b> may indicate important functional or structural roles.</li>
<li><b>Low conservation</b> may reflect variable or lineage-specific regions.</li>
<li><b>Motifs found across many species</b> may suggest conserved biological functions.</li>
</ul>
</section>
<hr>

<section id="valid_query">
<h2>What Counts as a Valid Query</h2>
<p>A few guidelines for valid queries.</p>

<h3>Protein</h3>
<ul>
<li>Use a protein family name (e.g., "glucose‑6‑phosphatase").</li>
<li>Do not use accession numbers.</li>
<li>Use a singular name (e.g., "kinase" rather than "kinases").</li>
<li>Choose a name broad enough to return at least two sequences.</li>
</ul>

<h3>Taxon</h3>
<ul>
<li>Use a taxon name (e.g., <i>Aves</i>, <i>Mammalia</i>).</li>
<li>Do not use taxon IDs (feature in development).</li>
<li>Choose a group broad enough to return at least two sequences.</li>
<li>Use a scientific or group name (e.g., "birds", not "bird").</li>
</ul>
</section>
<hr>

<section id="filtering">
<h2>Filtering and Dataset Limits</h2>
<p>
To keep the website responsive, automatic filtering is applied before alignment.
<br>Very short, very long, or highly ambiguous sequences may be excluded.
<br>Large datasets may also be limited by the number of sequences and the total number of residues processed.
</p>
<p>
When filtering occurs, the results page reports how many records were originally found and how many were retained for analysis.
</p>
</section>
<hr>

<section id="general">
<h2>Things to Keep in Mind</h2>
<ul>
<li>Some queries may take longer to run, especially those with many or long sequences.</li>
<li>Very broad queries may be limited in the number of sequences processed.</li>
<li>Results depend on the exact records retrieved from external databases at the time of the query.</li>
<li>Jobs may fail if too few sequences are available for a meaningful alignment.</li>
</ul>
</section>
<hr>

<section id="trouble">
<h2>Troubleshooting</h2>
<p>
If you're having trouble getting results, try adjusting the query:
</p>
<ul>
<li>If your job fails and too few sequences are found, try broadening the query (e.g., more general taxon or protein family)</li>
<li>If the dataset is too large, try narrowing the taxon or choosing a more specific protein family.</li>
</ul>
</section>
<hr>

<section id="example">
<h2>Try the Example Dataset</h2>
<p>
If you want to explore the website's features before submitting your own query,
you view the <a href="/~s2883992/website/example">example dataset</a>.
</p>
</section>
<hr>
</main>
</div>
</body>
</html>
_BODY;
?>


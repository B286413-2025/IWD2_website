<?php
session_start();
require_once 'set_cookies.php';

echo <<<_HTML
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Help</title>
</head>
<body>
_HTML;

include 'menuf.php';

echo <<<_BODY
<header>
<h1>Help</h1>
<p>
This page explains how to use the website and how to interpret the main outputs.
</p>

<h2>Navigation Menu</h2>
<nav aria-label="primary-navigation">
<ul>
<li><a href="#function">What Can This Website Do?</a></li>
<li><a href="#query">How to Run a Query</a></li>
<li><a href="#outputs">Main Outputs</a></li>
<li><a href="#interpretation">How to Interpret the Outputs</a></li>
<li><a href="#general">Things to Keep In Mind</a></li>
<li><a href="#example">Try the Example Dataset</a></li>
</ul>
</nav>

</header>
<hr />

<section id="function">
<h2>What Can This Website Do?</h2>
<p>
This website retrieves protein sequences for a protein family within a selected taxonomic group, aligns them, estimates sequence conservation, and searches for known motifs.
</p>
</section>
<hr />

<section id="query">
<h2>How to Run a Query</h2>
<ol>
<li>Go to the <a href="/~s2883992/website/query" target="_blank">query page</a>.</li>
<li>Enter a <b>taxonomic group</b> (for example: <i>Aves</i>, <i>Mammalia</i>).</li>
<li>Enter a <b>protein name</b> (for example: glucose-6-phosphatase, ABC transporter).</li>
<li>Optionally adjust the alignment and plot settings.</li>
<li>Submit the query and wait for the results page.</li>
</ol>
</section>
<a href='#'>Back to Top</a>
<hr />

<section id="outputs">
<h2>Main Outputs</h2>
<h3>1. Plotcon Conservation Plot</h3>
<p>
This plot shows how strongly conserved the aligned sequences are across the dataset.
Regions with higher conservation are more likely to be functionally or structurally important.
</p>

<h3>2. Summary Statistics</h3>
<p>
The results page includes a summary table with values such as:
</p>
<ul>
<li>Number of sequences included in the dataset</li>
<li>Number of represented organisms</li>
<li>Alignment length</li>
<li>Mean raw sequence length</li>
<li>Most common detected motif</li>
<li>Number of different motif types found</li>
</ul>

<h3>3. Text Files</h3>
<p>
The original text files outputs (MSA report and summarized motifs hits) are available for download.
</p>

<h3>4. Alignment Overview</h3>
<p>
The alignment overview section lets you inspect aligned sequences and compare gap content between entries.
Large gap fractions may indicate lower similarity or more variable regions.
</p>

<h3>5. Motif Overview</h3>
<p>
The motif overview section lists PROSITE motifs detected in the selected protein set.
You can inspect motif names, coordinates, and matches across different organisms.
</p>
</section>
<a href='#'>Back to Top</a>
<hr />

<section id="interpretation">
<h2>How to interpret the outputs</h2>
<p>A few general guidlines for output interpretation:</p>
<ul>
<li><b>High conservation</b> may point to important functional regions.</li>
<li><b>Low conservation</b> may indicate variable or lineage-specific regions.</li>
<li><b>Repeated motifs across many species</b> may suggest conserved biological roles.</li>
<li><b>Differences in motif content</b> may reflect evolutionary divergence or annotation differences.</li>
</ul>
</section>
<a href='#'>Back to Top</a>
<hr />

<section id="general">
<h2>Things to keep in mind</h2>
<ul>
<li>Queries are limited to a 1000 sequences for a reasonable runtime.</li>
<li>Some larger queries (many / long sequences) may take longer to run.</li>
<li>Very broad queries may be limited in the number of sequences processed.</li>
<li>Results depend on the exact records retrieved from external databases.</li>
<li>Some jobs may fail if too few sequences are found for a meaningful alignment.</li>
</ul>
</section>
<a href='#'>Back to Top</a>
<hr />

<section id="example">
<h2>Try the example dataset</h2>
<p>
If you want to see how the website works before submitting your own query,
you can open the <a href="/~s2883992/website/example">example dataset</a>.
</p>
</section>
<hr />

<p>
<a href="/~s2883992/website/front">Front page</a> |
<a href="/~s2883992/website/query">Query page</a> |
<a href="/~s2883992/website/previous_results">Previous results </a> |
<a href='#'>Back to Top</a>
</p>

</body>
</html>
_BODY;
?>


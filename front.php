<?php 
// Adapted from class code
// Home page with general information

session_start();
require_once 'set_cookies.php';
echo<<<_HTML
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="/~s2883992/website/styles.css">
<title>Protein conservation analysis</title>
</head>
<body>
_HTML;

// Including cookies banner and menu
include 'cookies.html';
include 'menuf.php';

echo<<<_BODY
<main class="page-main">
<header class="page-title">
<h1>Protein Conservation Analysis</h1>
<h2>Welcome!</h2>
<p>This website allows you to explore the conservation of protein families across different taxonomic groups.
<br>It was developed as part of the 
<a href="https://www.drps.ed.ac.uk/current/dpt/cxbilg11016.htm" target="_blank">Introduction to Website and Database Design</a> course at the 
<a href="https://www.ed.ac.uk/" target="_blank">University of Edinburgh</a>.
</p>
</header>
<hr>

<section>
<h2>Analysis Overview</h2>
<p>The conservation analysis consists of four main steps:</p>
<ol>
<li><b>Sequence retrieval</b> from the
<a href="https://www.ncbi.nlm.nih.gov/protein" target="_blank"> 
<abbr title="National Center for Biotechnology Information">NCBI</abbr>
protein database</a>.</li>
<li><b>Multiple sequence alignment</b> using
<a href="https://www.ebi.ac.uk/jdispatcher/msa/clustalo" target="_blank">Clustal Omega</a>.</li>
<li><b>Conservation plot</b> with 
<a href="https://www.bioinformatics.nl/cgi-bin/emboss/plotcon" target="_blank">plotcon</a>.</li>
<li><b>Motif detection</b> of 
<a href="https://prosite.expasy.org/" target="_blank">PROSITE</a> patterns via
<a href="https://www.bioinformatics.nl/cgi-bin/emboss/help/patmatmotifs" target="_blank">patmatmotifs</a>.</li>
</ol>
</section>
<hr>

<section>
<h2>Your Options</h2>
<p>
You can explore the precomputed example set, submit your own query, or revisit previous results.
<h3>Main Actions</h3>
<div class="button-group">
<form action="/~s2883992/website/example" method="get">
<button type="submit">Example Dataset</button>
</form>

<form action="/~s2883992/website/query" method="get">
<button type="submit">Submit Query</button>
</form>

<form action="/~s2883992/website/previous_results" method="get">
<button type="submit">Previous Results</button>
</form>
</div>

<h3>Information</h3>
<div class="button-group">
<form action="/~s2883992/website/help_page" method="get">
<button type="submit">Help</button>
</form>

<form action="/~s2883992/website/about" method="get">
<button type="submit">About</button>
</form>

<form action="/~s2883992/website/credit" method="get">
<button type="submit">Statement of Credits</button>
</form>
</div>
</section>
<hr>
</main>
</body>
</html>
_BODY;
?>

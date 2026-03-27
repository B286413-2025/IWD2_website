<?php // Adapted from class code
session_start();
require_once 'set_cookies.php';
echo<<<_HEAD1
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Protein conservation analysis</title>
_HEAD1;

// Including cookies banner
include 'cookies.html';

echo<<<_BODY
</head>
<body>
<header>
<h1>Protein Conservation Analysis</h1>
<h2>Welcome to my website for protein conservation!</h2>
<p>In this site you can look at the conservation levels of a protein family from a certain taxonomic group.
<br/>It was created as a web assessment component of the <a href="https://www.drps.ed.ac.uk/current/dpt/cxbilg11016.htm" target="_blank">Introduction to Website and Database Design</a> course.</p>
</header>
<hr>

<section>
<h2>Analysis Overview</h2>
<p>Conservation analysis will be done using 
<abbr title="EMBL's European Bioinformatics Institute">
<a href="https://www.ebi.ac.uk/" target="_blank">EMBL-EBI</a></abbr>
<a href="https://www.ebi.ac.uk/jdispatcher/msa/clustalo" target="_blank">Clustal Omega</a>, a multiple sequences alignment tool.
<br/>Further downstream analysis includes searching for known motifs agaist the
<a href="https://prosite.expasy.org/" target="_blank">PROSITE</a> protein family and domains database.
<br/>That will be done with the EMBOSS tool
<a href="https://www.bioinformatics.nl/cgi-bin/emboss/help/patmatmotifs" target="_blank">patmatmotifs</a>.
</p>
</section>
<hr>

<section>
<h2>Your Options</h2>
<p>
You can view an example analysis for conservation of glucose-6-phosphatase proteins
<br/>in birds (<i>Aves</i>) href="/~s2883992/website/example">here</a>.
<br/>Or you can continue to the main site and submit your own query and view past results.
</p>
<p>View the precomputed example dataset:</p>
<form action="/~s2883992/website/example" method="get">
<button type="submit">Example Dataset</button>
</form>

<p>Submit your own protein/taxon query:</p>
<form action="/~s2883992/website/query" method="get">
<button type="submit">Submit Query</button>
</form>

<p>View analyses previously run in this browser:</p>
<form action="/~s2883992/website/previous_results" method="get">
<button type="submit">Previous Results</button>
</form>

<p>Help and further information:</p>
<form action="/~s2883992/website/help_page" method="get">
<button type="submit">Help</button>
</form>

<form action="/~s2883992/website/about" method="get">
<button type="submit">About</button>
</form>

<form action="/~s2883992/website/credit" method="get">
<button type="submit">Statement of Credits</button>
</form>
</section>
<hr>
</body>
</html>
_BODY;
?>

<?php
// Statement of credits
// Detailing external resources used and AI assistance
session_start();
require_once 'set_cookies.php';
session_write_close();

echo <<<_HTML
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Statement of Credits</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="/~s2883992/website/styles.css">
</head>
<body>
_HTML;

include 'cookies.html';
include 'menuf.php';

// Sticky left menu, informed by ELM (GPT 5.2), https://elm.edina.ac.uk/elm-new
echo <<<_LAYOUT
<div class="page-shell">
<aside class="page-side-nav">
<h2>On this Page</h2>
<ul>
<li><a href="#class">Class Materials</a></li>
<li><a href="#ai">AI Assistance</a></li>
<li><a href="#external">External Documentation and References</a></li>
<li><a href="#specific">Specific Code References</a></li>
<li><a href="#software">Software Used</a></li>
<li><a href="#">Back to Top</a></li>
</ul>
</aside>
<main class="page-main">
<header class="page-title" id="intro">
<h1>Statement of Credits</h1>
<p>
Details of the main sources of code, documentation, software, and AI assistance
used during development of the website.
</p>
</header>
<hr>
_LAYOUT;

echo <<<_BODY
<section id="class">
<h2>1. Class Materials</h2>
<p>The following parts of the website were adapted from class code and teaching examples:</p>
<ul>
<li>PDO database connection patterns</li>
<li>HTML/PHP page structure from exercises and directed learning</li>
<li>Basic form handling patterns</li>
<li>Use of sessions and page redirection</li>
</ul>
<p>
Course Unofficial Webpage:
<a href="https://bioinfmsc8.bio.ed.ac.uk/IWD2.html" target="_blank">Introduction to Website and Database Design</a>
</p>
</section>
<hr>

<section id="ai">
<h2>2. AI Assistance</h2>
<p>
The University of Edinburgh ELM system (GPT 5.2) was used to assist with
debugging, code explanation, and some code generation during development.
</p>

<p><b>Tool used:</b><br>
<a href="https://elm.edina.ac.uk/elm/elm" target="_blank">ELM</a> (GPT 5.2)
</p>

<p><b>How AI was used:</b></p>
<ul>
<li>Debugging PHP, Python, JavaScript, and SQL syntax errors</li>
<li>Explaining error messages and suggesting fixes</li>
<li>Generating or refining code for:
<ul>
<li>Integrating analysis scripts with PHP wrappers</li>
<li>Image output storage and handling in MySQL</li>
<li>AJAX table rendering</li>
<li>Cookie-based user identification</li>
<li>CSS initial structure and refinement</li>
</ul>
</li>
</ul>

<p><b>Important note:</b><br>
All AI-generated suggestions were reviewed, tested, and modified where necessary before inclusion.
<br>Some generated code was rejected or corrected during development.
<br>Non-modified code is credited directly in scripts, available in my <a href="https://github.com/algra2001/IWD2_website" target="_blank">GitHub repository</a>.
</p>
</section>
<hr>

<section id="external">
<h2>3. External Documentation and References</h2>
<ul>
<li>
<a href="https://www.php.net/manual/en/" target="_blank">PHP Manual</a> - PDO, sessions, cookies, general PHP syntax
</li>
<li>
<a href="https://dev.mysql.com/doc/refman/8.4/en/" target="_blank">MySQL Manual</a> - SQL syntax
</li>
<li>
<a href="https://developer.mozilla.org/en-US/docs/Web/HTML" target="_blank">Mozilla HTML Guide</a> - HTML syntax and standards
</li>
<li>
<a href="https://biopython.org/" target="_blank">Biopython Documentation</a> - Entrez and SeqIO usage
</li>
<li>
<a href="https://www.ncbi.nlm.nih.gov/protein" target="_blank">NCBI protein database</a> - sequence retrieval
</li>
<li>
<a href="https://www.ncbi.nlm.nih.gov/books/NBK179288/" target="_blank">NCBI Entrez Direct / E-utilities documentation</a> - sequence retrieval concepts
</li>
<li>
<a href="https://www.ncbi.nlm.nih.gov/genbank/internatprot_nomenguide/#2-formats-for-protein-names" target="_blank">NCBI protein naming conventions</a> 
- protein names verification
</li>
<li>
<a href="https://www.ebi.ac.uk/jdispatcher/msa/clustalo" target="_blank">Clustal Omega </a> documentation
</li>
<li>
<a href="https://www.bioinformatics.nl/cgi-bin/emboss/help/plotcon" target="_blank">EMBOSS plotcon help</a>
</li>
<li>
<a href="https://www.bioinformatics.nl/cgi-bin/emboss/help/patmatmotifs" target="_blank">EMBOSS patmatmotifs help</a>
</li>
<li>
<a href="https://prosite.expasy.org/" target="_blank">PROSITE</a> - motif and domain resource
</li>
<li>
<a href="https://stackoverflow.com/questions" target="_blank">Stack Overflow discussions</a> - specific coding questions (credited in-script)
</li>
<li>
<a href="https://www.w3schools.com/" target="_blank">W3 Schools</a> - PHP, CSS, SQL and HTML examples and ideas
</li>
<li>
<a href="https://www.youtube.com/watch?v=kUMe1FH4CHE" target="_blank">freeCodeCamp HTML tutorial</a>
</li>
<li>
<a href="https://www.youtube.com/watch?v=kPtS4vO42II" target="_blank">Dani Krossing tutorial</a> - 404 page setup
</li>
<li>
<a href="https://www.youtube.com/watch?v=zJxCq6D14eM" target="_blank">Dani Krossing tutorial</a> - clean URLs
</li>
<li>
<a href="https://app.chartdb.io/" target="_blank">ChartDB</a> - schema diagram generation
</li>
<li>
<a href="https://www.sciencefocus.com/science/fun-facts" target="_blank">BBC Science Focus</a> - fun facts for the loading page
</li>
</ul>
</section>
<hr>

<section id="specific">
<h2>4. Specific Code References</h2>
<ul>
<li>
JavaScript query validation adapted from:
<a href="https://www.geeksforgeeks.org/javascript/username-validation-in-js-regex/" target="_blank">GeeksforGeeks username validation example</a>
</li>
<li>
FASTA writing from GenBank format from: 
<a href="https://warwick.ac.uk/fac/sci/moac/people/students/peter_cock/python/genbank2fasta" target="_blank">
University of Warwick resource</a>
</li>
<li>
Temporary table upsert strategy informed by: 
<a href="https://stackoverflow.com/questions/15271202/mysql-load-data-infile-with-on-duplicate-key-update" target="_blank">
Stack Overflow discussion</a>
</li>
<li>
Background processing approach informed by: 
<a href="https://stackoverflow.com/questions/4626860/how-can-i-run-a-php-script-in-the-background-after-a-form-is-submitted" target="_blank">
Stack Overflow discussion</a>
</li>
<li>
Loading page automatic refresh strategy informed by: 
<a href="https://stackoverflow.com/questions/30885877/how-to-automatically-refresh-the-page-after-submitting-a-form" target="_blank">
Stack Overflow discussion</a>
</li>
<li>
Fixed main menu informed by: 
<a href="https://www.w3schools.com/howto/howto_css_fixed_menu.asp" target="_blank">
W3Schools entry</a>
</li>
</ul>
</section>
<hr>

<section id="software">
<h2>5. Software Used</h2>
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
<hr>
</main>
</div>
</body>
</html>
_BODY;
?>


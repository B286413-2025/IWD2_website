<?php
session_start();
require_once 'set_cookies.php';

echo <<<_HTML
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Statement of Credits</title>
</head>
<body>
_HTML;

include 'menuf.php';

echo <<<_BODY
<header>
<h1>Statement of Credits</h1>
<p>
This page documents the sources of code, documentation, software, and AI assistance
used in the development of this website.
</p>
<h2>Navigation Menu</h2>
<nav aria-label="primary-navigation">
<ul>
<li><a href="#class">Class Material</a></li>
<li><a href="#ai">AI Assistance</a></li>
<li><a href="#external">External Documentation and References</a></li>
<li><a href="#specific">Specific Code References</a></li>
<li><a href="#software">Software Used</a></li>
</ul>
</nav>
</header>
<hr />

<section>
<h2 id="class">1. Class Materials</h2>
<p>The following parts of the website were adapted from class code and teaching examples:</p>
<ul>
<li>PDO database connection patterns</li>
<li>HTML/PHP page structure used in early exercises and directed learning</li>
<li>Basic form handling patterns</li>
<li>Use of sessions and page redirection</li>
</ul>
<p>
Course Unofficial Webpage:
<a href="https://bioinfmsc8.bio.ed.ac.uk/IWD2.html" target="_blank">Introduction to Website and Database Design</a>
</p>
</section>
<hr />

<section>
<h2 id="ai">2. AI Assistance</h2>
<p>
The University of Edinburgh ELM system (based on GPT 5.2 architecture) was used to assist with
debugging, code explanation, and some code generation during development.
</p>

<p><b>Tool used:</b><br />
<a href="https://elm.edina.ac.uk/elm/elm" target="_blank">ELM</a> (GPT 5.2)
</p>

<p><b>How AI was used:</b></p>
<ul>
<li>Debugging PHP, Python, JavaScript, and SQL syntax errors</li>
<li>Explaining error messages and suggesting fixes</li>
<li>Generating or refining code for:
<ul>
<li>Integrating analysis scripts with PHP wrappers (mainly working with temporary files)</li>
<li>Image output storage and handling in MySQL</li>
<li>AJAX table rendering</li>
<li>Cookie-based user identification</li>
</ul>
</ul>

<p><b>Important note:</b><br />
All AI-generated suggestions were reviewed, tested, and modified if necessary before inclusion.
<br />Some generated code was rejected or corrected during development. 
<br/ >Non-modified code is credited in scripts, which can be seen in my personal <a href="https://github.com/algra2001/IWD2_website" target="_blank">GitHub repository</a>.
</p>
</section>
<hr />

<section>
<h2 id="external">3. External Documentation and References</h2>
<ul>
<li>
<a href="https://www.php.net/manual/en/" target="_blank">PHP Manual</a> - used for PDO, sessions, cookies, and general PHP syntax
</li>
<li>
<a href="https://dev.mysql.com/doc/refman/8.4/en/" target="_blank">MySQL Manual</a> - used for SQL syntax
</li>
<li>
<a href="https://biopython.org/" target="_blank">Biopython Documentation</a> - used for sequence retrieval and parsing with Entrez / SeqIO
</li>
<li>
<a href="https://www.ncbi.nlm.nih.gov/books/NBK179288/" target="_blank">NCBI Entrez Direct / E-utilities</a> documentation - for sequence retrieval concepts
</li>
<li>
<a href="https://www.ncbi.nlm.nih.gov/genbank/internatprot_nomenguide/#2-formats-for-protein-names" target="_blank">NCBI protein naming conventions</a> 
- for protein names general verification
</li>
<li>
<a href="https://www.ebi.ac.uk/jdispatcher/msa/clustalo" target="_blank">Clustal Omega </a> documentation - for alignment formats and usage
</li>
<li>
<a href="https://www.bioinformatics.nl/cgi-bin/emboss/help/plotcon" target="_blank">EMBOSS plotcon help</a> - for conservation plot generation
</li>
<li>
<a href="https://www.bioinformatics.nl/cgi-bin/emboss/help/patmatmotifs" target="_blank">EMBOSS patmatmotifs help</a> - for motif scanning
</li>
<li>
<a href="https://prosite.expasy.org/" target="_blank">PROSITE</a> - biological motif/domain resource used in motif analysis
</li>
<li>
<a href="https://stackoverflow.com/questions" target="_blank">Stack Overflow discussions</a> - for specific coding questions (indicated in-script)
</li>
<li>
<a href="https://www.youtube.com/watch?v=kUMe1FH4CHE" target="_blank">freeCodeCamp HTML tutorial</a> - for basic HTML ideas
</li>
<li>
<a href="https://www.youtube.com/watch?v=kPtS4vO42II" target="_blank">Dani Krossing tutorial</a> - for 404 page setup
</li>
</ul>
</section>
<hr />

<section>
<h2 id="specific">4. Specific Code References</h2>
<ul>
<li>
JavaScript username/query validation pattern adapted from:
<a href="https://www.geeksforgeeks.org/javascript/username-validation-in-js-regex/" target="_blank">GeeksforGeeks username validation example</a>
</li>
<li>
Temporary table upsert strategy informed by: 
<a href="https://stackoverflow.com/questions/15271202/mysql-load-data-infile-with-on-duplicate-key-update" target="_blank">
Stack Overflow discussion</a>
</li>
<li>
Loading page automatic refresh strategy informed by: 
<a href="https://stackoverflow.com/questions/30885877/how-to-automatically-refresh-the-page-after-submitting-a-form" target="_blank">
Stack OverFlow discussion</a>
</li>
</ul>
</section>
<hr />

<section>
<h2 id="software">5. Software Used</h2>
<ul>
<li>PHP</li>
<li>MySQL</li>
<li>Python 3</li>
<li>Clustal Omega</li>
<li>EMBOSS plotcon</li>
<li>EMBOSS patmatmotifs</li>
</ul>
</section>
<hr />
<p><a href="front.php">Back to front page</a></p>
</body>
</html>
_BODY;
?>


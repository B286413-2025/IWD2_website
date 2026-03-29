<?php 
// Adapted from class code
// Checking user name is set
session_start();
require_once 'set_cookies.php';
require_once 'login.php';
echo<<<_HTML

<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link rel="stylesheet" href="/~s2883992/website/styles.css" />
<title>Submit a Query</title>
</head>
<body>	
_HTML;

include 'cookies.html';
include'menuf.php';

echo <<<_INFO
<main class="query-shell">
<header class="page-title">
<h1>Query Submission</h1>
<p>Main query page</p>
</header>
<hr />
<section id="overview">
<h2>Overview</h2>
<p>You can enter a taxonomic group and a protein name to perform the conservation analysis.
<br />The settings of the analysis outputs can be modified, otherwise defaults will be used.</p>

<p>
<b>Default analysis settings:</b><br />
Clustal Omega output format: FASTA<br />
Plotcon window size: 4<br>
Plotcon output format: PNG<br>
Motif scan: default patmatmotifs settings
</p>
</section>
<hr />
_INFO;

echo <<<_FORM
<section class="form-panel">
<script>
// Adapted from: https://www.geeksforgeeks.org/javascript/username-validation-in-js-regex/
// and from class code
// A function to validate query parameters entered in form
function validate(form) {
	// Form values, initializing fail variable for alert
	const taxon = form.taxon.value.trim();
	const protFam = form.prot_fam.value.trim();
	let fail = "";

	// Checking for appropriate length, updating fail
	if (taxon.length === 0) {
		fail += 'Taxon cannot be empty. '; 
	} else if (taxon.length < 2) {
		fail += 'Taxon must contain at least two letters. ';
	}
	if (protFam.length === 0) {
		fail += 'Protein family cannot be empty. '; 
	} else if (protFam.length < 2) {
		fail += 'Protein family  must contain at least two letters. ';
	}

	// Checking for permitted characters, defining patterns, updating fail
	// TODO: maybe allow taxon id as well as of taxon name
	const taxPat = /^[a-zA-Z -]{2,}$/;
	// General guidelines for protein names taken from: https://www.ncbi.nlm.nih.gov/genbank/internatprot_nomenguide/#2-formats-for-protein-names
	const protPat = /^[\w '\,\+\/\(\)-]{2,}$/;

	// Alerting error
	if(taxon.length >= 2 && !taxPat.test(taxon)) {
		fail += "Taxon contains illegal characters. ";
	}
	if(protFam.length >= 2 && !protPat.test(protFam)) {
		fail += "Protein contains illegal characters.";
	}
	if(fail === "") {
		return true;
	} else {
		alert(fail); 
		return false;
	}
}
</script>

<!-- form to retrieve query parameters -->
<form action="/~s2883992/website/loading_page.php" method="post" onsubmit="return validate(this)">
<h2>Query</h2>
<!-- Mandatory query parameters -->
<fieldset>
<legend>Query Parameters</legend>  
<table class="form-table">
	<tr>	
	<td>Taxonomic group:</td><td><input type="text" name="taxon" placeholder="Aves" maxlength="100" required/></td>
	</tr>
	<tr>
	<td>Protein family:</td><td><input type="text" name="prot_fam" placeholder="glucose-6-phosphatase" required/></td>
	</tr>
</table>
</fieldset>

<!-- Optional additional query parameters -->
<details>
<summary><b>Advanced settings</b></summary>
	<fieldset>
	<legend>Clustal Omega Parameters</legend>
	<table class="form-table">
		<tr>
		<td>Output Format:</td>
		<td>
			<select name="clust_outfmt" id="clust_outfmt" size="5">
			<option value="fasta" selected>FASTA</option>
			<option value="clustal">Clustal</option>
			<option value="msf">MSF</option>
			<option value="phylip">PHYLIP</option>
			<option value="selex">SELEX</option>
			<option value="stockholm">STOCKHOLM</option>
			<option value="vienna">VIENNA</option>
			</select>
		</td>
		</tr>
	</table>
	</fieldset>

	<fieldset>
	<legend>Plotcon Parameters</legend>
	<table class="form-table">
		<tr>
		<td>Window Size:</td>
		<td>
			<input type="number" name="win_size" id="win_size" min="1" max="100" step="1" value="4">
		</td> 
		<td>Output Format:</td>
		<td>
			<select name="plot_outfmt" id="plot_outfmt" size="5">
			<option value="png" selected>png</option>
			<option value="pdf">pdf</option>
			<option value="svg">svg</option>
			<option value="gif">gif</option>
			<option value="data">data</option>
			<option value="ps">ps</option>
			<option value="hpgl">hpgl</option>
			<option value="meta">meta</option>
		</select>
		</td>
		</tr>
	</table>
	</fieldset>

	<fieldset>
	<legend>Motif Scan</legend>
	<p>The motif search currently runs with default <code>patmatmotifs</code> settings.</p>
	</fieldset>
</details>

<div class="query-submit-row">
<input type="submit" value="Submit">
</div>
</form>
</section>
</main>
_FORM;

echo <<<_TAIL
</body>
</html>
_TAIL;
?>

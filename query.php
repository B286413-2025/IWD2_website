<?php // Adapted from class code
// Checking user name is set
session_start();
require_once 'set_cookies.php';
require_once 'login.php';
echo<<<_HEAD1
<!doctype html>
<html lang="en">
<html>
<head>
<meta charset="UTF-8" />
<title>Submit a query</title>
</head>

<body>	
<header>
_HEAD1;
include'menuf.php';

echo <<<_INFO
<h1>Query submission</h1>
<p>This is the main query page. You can enter a taxonomic group and a protein name for a conservation analysis.
</br>The settings of the downstream analysis can be updated, otherwise defaults will be used.</p>
<hr>
</br>
</header>
_INFO;
// Checking connection to database using details from login.php
try {
	$dsn = "mysql:host=127.0.0.1;dbname=$database;charset=utf8mb4";
	$conn = new PDO($dsn, $username, $password);
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
       	echo "\nConnected successfully to the database!<br/>";
} catch(PDOException $e) {
	echo "<br/><br/><b><font color=\"red\">Connection failed</font></b>:<br/>" . $e->getMessage();
}
echo <<<_FORM
<script>
// Adapted from: https://www.geeksforgeeks.org/javascript/username-validation-in-js-regex/
// and from class code
// A function to validate query parameters entered in form
    function validate(form) {
	const taxon = form.taxon.value.trim();
	const protFam = form.prot_fam.value.trim();
	let fail = "";
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
	// Checking for permitted characters, defining patterns
	// TODO: maybe allow taxon id as well as of taxon name
	const taxPat = /^[a-zA-Z -]{2,}$/;
	// General guidelines for protein names taken from: https://www.ncbi.nlm.nih.gov/genbank/internatprot_nomenguide/#2-formats-for-protein-names
	const protPat = /^[\w '\,\+\/\(\)-]{2,}$/;
	// Alerting error
	if(taxon.length >= 2 && !taxPat.test(taxon)) fail += "Taxon contains illegal characters. ";
	if(protFam.length >= 2 && !protPat.test(protFam)) fail += "Protein contains illegal characters.";
	if(fail === "") {
		return true;
	} else { 
		alert(fail); 
		return false;
	}
}
</script>
<br/>
<!-- form to retrieve query parameters -->
<form action="result_page.php" method="post" onsubmit="return validate(this)">
<p>Enter query parameters: taxonomic group and protein family are mandatory</p>
<fieldset>
<legend>Query parameters</legend>  
<table>
    <tr>	
      <td>Taxonomic group:</td><td><input type="text" name="taxon" placeholder="Aves" maxlength="100"/></td>
    </tr>
    <tr>
      <td>Protein family:</td><td><input type="text" name="prot_fam" placeholder="glucose-6-phosphatase"/></td>
    </tr>
  </table>
</fieldset>
<br/>
<fieldset>
<legend>ClustalO parameters</legend>
<table>
  <tr>
    <td>Output format:</td>
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
<br/>
<fieldset>
<legend>Plotcon parameters</legend>
<table>
  <tr>
    <td>Window size:</td>
    <td>
      <input type="number" name="win_size" id="win_size" min="1" max="100" step="1" value="4">
    <td/>
    <td></td> 
    <td>Output format:</td>
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
<br/>
<fieldset>
<legend>patpatmotifs parameters</legend>
</fieldset>
<br/><input type="submit" value="submit" />
</form>
_FORM;
	echo <<<_TAIL1
</body>
</html>
_TAIL1;
?>

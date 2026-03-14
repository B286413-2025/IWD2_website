<?php // Adapted from class code
session_start();
require_once 'set_cookies.php';
require_once 'login.php';
echo<<<_HEAD1
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Protein conservation analysis</title>
_HEAD1;

// Including cookies banner
include 'cookies.html';

echo<<<_HEAD2
</head>
<body onload="displayForm()">
_HEAD2;

//TODO: add a nice welcome message
echo<<<_WELCOME
<header>
<h1>Protein conservation analysis</h1>
<h2>Welcome to my website for protein conservation!</h2>
<p>In this site you can look at the conservation levels of a protein family from a certain taxonomic group.
<br/>Conservation analysis will be done using 
<abbr title="EMBL's European Bioinformatics Institute">
<a href="https://www.ebi.ac.uk/">EMBL-EBI</a></abbr>
<a href="https://www.ebi.ac.uk/jdispatcher/msa/clustalo">Clustal Omega</a>, a multiple sequences alignment tool.
<br/>Further downstream analysis includes searching for known motifs agaist the
<a href="https://prosite.expasy.org/">PROSITE</a> protein family and domains database.
<br/>That will be done with the EMBOSS tool
<a href="https://www.bioinformatics.nl/cgi-bin/emboss/help/patmatmotifs">patmatmotifs</a>.
</p>
</header>
<hr>
<p>
You can proceed to the query page by submitting the form below.
<br/>You can choose whether to save your results on the site by entering a user name,
<br/>or simply continue without saving them.
<br/>To view an example analysis for conservation of glucose-6-phosphatase proteins
<br/>in birds (<i>Aves</i>), click
<a href="https://bioinfmsc8.bio.ed.ac.uk/~s2883992/website/example.php">here</a>.
</p>
<hr>
_WELCOME;

// Checking connection to database using details from login.php
try {
	$dsn = "mysql:host=127.0.0.1;dbname=$database;charset=utf8mb4";
	$conn = new PDO($dsn, $username, $password);
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "\nConnected successfully to the database!";
} catch(PDOException $e) {
	echo "<br/><br/><b><font color=\"red\">Connection failed, the site does not work right now :(</font></b>:<br/>" . $e->getMessage();
}
   echo <<<_EOP
<script>
// Adapted from: https://www.geeksforgeeks.org/javascript/username-validation-in-js-regex/
// and from class code
// A function to validate user name entered at form
    function validate(form) {
	let fail = "";
	const userName = form.user_name.value.trim();
	if(userName === "") {
// Checking for value
	fail = "Username cannot be empty.";
// Checking for length
	} else if(userName.length < 6) {
	fail = "Username is too short.";
	} else if(userName.length > 16) {
	fail = "Username is too long.";
// Checking for permitted characters
	} else {
	const pattern = /^[\w]{6,16}$/;
// Alerting error
	if(!pattern.test(userName)) fail = "Username contains illegal characters";
	}
	if(fail === "") {
		return true;
	} else { 
		alert(fail); 
		return false;
	}
}
</script>
<br/>
<!--TODO: perhaps add an optional choice of cookies instead of username-->
<!--Deciding whether to save resulst-->

<script>
// form to retrieve user name if saving
function displayForm()
{
document.getElementById("save_choice").innerHTML=`<form action="query.php" method="post" onsubmit="return validate(this)">
<p>Enter a user name to save results. Can contain letters, numbers and underscores. 
<br/>Must be between 6 and 16 characters long.<p/>  
<table>
    <tr>	
      <td>User name:</td><td><input type="text" name="user_name"/></td>
    </tr>
  </table>
<br/><input type="submit" value="submit" />
</form>`;
}

// deleting form if not saving
function delForm()
{
document.getElementById("save_choice").innerHTML=`<form action="query.php" method="post">
<input type="hidden" name="user_name" value="no-save" />
<p><input type="submit" value="continue" /></p>
</form>`;
}
</script>

<!-- displaying options for saving results -->
<p>
<input type="radio" name="save_choice" id="save_yes" value="save" onclick="displayForm()" checked>
<label for="save_yes">Save results</label>
</p>
<p>
<input type="radio" name="save_choice" id="save_no" value="no-save" onclick="delForm()">
<label for="save_no">Continue without saving results</label>
</p>
<p id="save_choice"></p>
_EOP;
echo <<<_TAIL1
</body>
</html>
_TAIL1;

?>

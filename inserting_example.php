<?php
session_start();
require_once 'login.php';
//include 'redir.php';
echo<<<_HEAD1
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8" />
</head>
<body>
_HEAD1;

include 'menuf.php';

// MySQL connection
try {
        $dsn = "mysql:host=127.0.0.1;dbname=$database;charset=utf8mb4";
        $conn = new PDO($dsn, $username, $password, array(PDO::MYSQL_ATTR_LOCAL_INFILE => true));
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
        echo "<br/><br/><b><font color=\"red\">Connection failed</font></b>:<br/>" . $e->getMessage();
}

echo "Loading example dataset...";

// Inserting example query to queries
$protein_family = "glucose-6-phosphatase";
$taxon = "aves";

try {
	// Preparing statement for insertion
	$queries_insert = "INSERT INTO queries (protein_family, taxon, is_example) VALUES (:pf, :tx, 1) ON DUPLICATE KEY UPDATE protein_family = :pf, taxon = :tx";
	$queries_prepare = $conn->prepare($queries_insert);
	$queries_prepare->execute([':pf' => $protein_family, ':tx' => $taxon]);

	// Retrieving query ID for seq_group
	$query = $conn->prepare("SELECT query_id FROM queries WHERE protein_family = ? AND taxon = ?");
	$query->execute([$protein_family, $taxon]);
	$qid = $query->fetchColumn();

	// Sanity check
	echo "<p>Query ID: $qid</p>";

} catch(PDOException $e) {
	echo "<br/><br/><b><font color=\"red\">Failed to insert data to queries</font></b>:<br/>" . $e->getMessage();
};

// Inserting sequences
try {
	$csv_file = "/home/s2883992/public_html/website/downloaded_sequences/example_record.csv";
	$seq_query = "LOAD DATA LOCAL INFILE '" . $csv_file .  "' INTO TABLE sequences FIELDS TERMINATED BY ',' LINES TERMINATED BY '\n' (accession, organism, sequence);";
	$stmt = $conn->prepare($seq_query);
	$stmt->execute();
} catch(PDOException $e) {
        echo "<br/><br/><b><font color=\"red\">Failed to insert sequences/font></b>:<br/>" . $e->getMessage();
};
echo "<p>Sequences loaded</p>";
var_dump($csv_file);

// Updating seq_group
try {
	$seq_group = "LOAD DATA LOCAL INFILE '" . $csv_file . "' INTO TABLE seq_group FIELDS TERMINATED BY ',' LINES TERMINATED BY '\n' (@acc, @org, @seq) SET query_id = " . $qid . ", accession = @acc";
	$stmt = $conn->prepare($seq_group);
	$stmt->execute();
	echo "<p>seq_group loaded</p>";
} catch(PDOException $e) {
        echo "<br/><br/><b><font color=\"red\">Failed to update seq_group</font></b>:<br/>" . $e->getMessage();
};
var_dump($csv_file);

// Updating jobs
try {
	$job_query = "INSERT INTO jobs (user_id, query_id, status) VALUES ('example', ?, 'pending')";
	$stmt = $conn->prepare($job_query);
	$stmt->execute([$qid]);
	echo "<p>job updated</p>";
	
	// Retrieving query ID for seq_group
	$query = $conn->prepare("SELECT job_id FROM jobs WHERE query_id = ?");
	$query->execute([$qid]);
	$jid = $query->fetchColumn();

	// Sanity check
	echo "<p>Job ID: $jid</p>";
} catch(PDOException $e) {
        echo "<br/><br/><b><font color=\"red\">Failed to update seq_group</font></b>:<br/>" . $e->getMessage();
};


echo <<<_TAIL1
</body>
</html>
_TAIL1;
?>

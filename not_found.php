<?php 
// Custom 404 error page

http_response_code(404);

// Starting session if not called from another page
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}
require_once 'set_cookies.php';

echo <<<_HTML
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link rel="stylesheet" href="/~s2883992/website/styles.css" />
<title>404 - Page Not Found</title>
</head>
<body>
_HTML;

include 'menuf.php';

echo <<<_BODY
<main class="center-page">
<section>
<h1>404 - Page Not Found</h1>
<p>Sorry, the page you requested could not be found on this website &#128560</p>
<p>But no worries, you still have many options!</p>
<div class="button-group">
<a class="button-link" href="/~s2883992/website/front">Home Page</a>
<a class="button-link" href="/~s2883992/website/query">Submit Query</a>
<a class="button-link" href="/~s2883992/website/previous_results">Previous Results</a>
<a class="button-link" href="/~s2883992/website/example">Example Dataset</a>
</div>
</section>
</main>
</form>
</body>
</html>
_BODY;
?>


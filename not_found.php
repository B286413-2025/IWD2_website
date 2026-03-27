<?php
session_start();
require_once 'set_cookies.php';

echo <<<_HTML
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Page Not Found</title>
</head>
<body>
_HTML;

include 'menuf.php';

echo <<<_BODY
<h1>404 - Page Not Found</h1>
<p>Sorry, the page you requested could not be found on this website.</p>
<p>You can use the menu options or return to the front page.</p>
<form action="/~s2883992/website/front" method="get">
  <button type="submit">Front Page</button>
</form>
</body>
</html>
_BODY;
?>


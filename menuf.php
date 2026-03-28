<?php 
// Setting global menu options for the website, adapted from class code

// Base url
$BASE = '/~s2883992/website';

// Getting current page and marking the active one in the menu
$uri = $_SERVER['REQUEST_URI'] ?? '';

// Function to check active page
// Taking string URI and path, returning 'active' if active, empty string if not
function menu_active($uri, $path) {
	// Checking if path is substring of URI
	$active = (strpos($uri, $path) !== false) ? 'active' : '';
	return $active;
}
$frontClass = menu_active($uri, '/front');
$queryClass = menu_active($uri, '/query') ;
$exampleClass = menu_active($uri, '/example');
$previousClass = menu_active($uri, '/previous_results'); 
$helpClass = menu_active($uri, '/help'); 
$aboutClass = menu_active($uri, '/about'); 
$creditClass = menu_active($uri, '/credit'); 

// Echoing menu, adjusting CSS in accordance
echo <<<HTML
<nav class="site-nav" aria-label="Main site navigation">
<div class="site-nav-inner">
<div class="site-brand">
<a href="$BASE/front">Protein Conservation Analysis</a>
</div>
<ul class="site-nav-list">
<li><a class="$frontClass" href="$BASE/front">Home</a></li>
<li><a class="$queryClass" href="$BASE/query">Query</a></li>
<li><a class="$exampleClass" href="$BASE/example">Example</a></li>
<li><a class="$previousClass" href="$BASE/previous_results">Previous Results</a></li>
<li><a class="$helpClass" href="$BASE/help_page">Help</a></li>
<li><a class="$aboutClass" href="$BASE/about">About</a></li>
<li><a class="$creditClass" href="$BASE/credit">Credits</a></li>
</ul>
</div>
</nav>
HTML;
?>

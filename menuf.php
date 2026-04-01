<?php 
// Setting global menu options for the website, adapted from class code
// Debugged with ELM (GPT 5.2), https://elm.edina.ac.uk/elm-new

// Base url
if (!isset($BASE)) {
	$BASE = '/~s2883992/website';
}

// Getting current page and marking the active one in the menu
$uri = $_SERVER['REQUEST_URI'] ?? '';

// Function to check active page
// Taking string URI and path, returning 'active' if active, empty string if not
function menu_active($uri, $paths) {
	foreach ((array)$paths as $path) {
	// For each, checking if path is substring of URI
		if (strpos($uri, $path) !== false) {
			return 'active';
		}
	}
	return '';
}
$frontClass = menu_active($uri, '/front');
$queryClass = (
	menu_active($uri, ['/query', '/loading/'])
);
$exampleClass = menu_active($uri, '/example');
$previousClass = (
	menu_active($uri, ['/previous_results', '/results/'])
);
$helpClass = menu_active($uri, '/help_page'); 
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

<?php
// Essential cookie - browser token used to associate jobs with this browser
// Adapted from ELM (GPT 5.2) code, https://elm.edina.ac.uk/elm-new
$uid_cookie = 'site_uid';
$cookie_path = '/~s2883992/website/';
// TODO: maybe reduce the expiry period later
$cookie_expiry = time() + (365 * 24 * 60 * 60);

// Setting the cookie
if (empty($_COOKIE[$uid_cookie])) {
        // 64 hex chars token
        $token = bin2hex(random_bytes(32));
        // Use secure cookies only when HTTPS is on (should be on for your site)
        $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        setcookie($uid_cookie, $token, [
                'expires'  => $cookie_expiry,
                'path'     => $cookie_path,
                // HTTPS and no JS
                'secure'   => $is_https,
		'httponly' => true,
		'samesite' => 'Lax'
	]);

	// Updating $_COOKIE within request for hashing
	$_COOKIE[$uid_cookie] = $token;
}

// Hashing cookie for MySQL
$_SESSION['user_hash'] = hash('sha256', $_COOKIE[$uid_cookie]);

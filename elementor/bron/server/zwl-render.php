<?php
/**
 * Rendert één URL van staging met PHP op de server zelf, zoals een anonieme
 * bezoeker hem krijgt — zonder Apache/LiteSpeed, dus zonder het staging-wachtwoord.
 *
 *   cd ~/subsites/staging.zwijsen.be && php ~/tmp/zwl/zwl-render.php /elektricien-dendermonde/ > uit.html 2> uit.log
 *
 * stdout = de HTML. stderr = "STATUS <code>", eventueel "REDIRECT <code> <url>",
 * en elke PHP-melding (warning/notice/deprecated) met bestand en regel.
 * Schrijft niets in de site, behalve wat WordPress/Elementor bij een gewoon
 * bezoek ook doen (bv. het CSS-bestand van de pagina aanmaken als het ontbreekt).
 */
if ( PHP_SAPI !== 'cli' ) { exit( 1 ); }
$path = $argv[1] ?? '/';
// Host: staging tenzij ZWL_HOST iets anders zegt (ZWL_HOST=zwijsen.be voor productie, 5d).
$host = getenv( 'ZWL_HOST' ) ?: 'staging.zwijsen.be';
if ( ! in_array( $host, [ 'staging.zwijsen.be', 'zwijsen.be' ], true ) ) { fwrite( STDERR, "onbekende host $host\n" ); exit( 2 ); }
$_SERVER = array_merge( $_SERVER, [
	'HTTP_HOST' => $host, 'SERVER_NAME' => $host, 'HTTPS' => 'on',
	'SERVER_PORT' => '443', 'REQUEST_METHOD' => 'GET', 'REQUEST_URI' => $path,
	'SCRIPT_NAME' => '/index.php', 'PHP_SELF' => '/index.php', 'SCRIPT_FILENAME' => getcwd() . '/index.php',
	'REMOTE_ADDR' => '127.0.0.1', 'HTTP_USER_AGENT' => 'Mozilla/5.0 (zwl-render)', 'HTTP_ACCEPT' => 'text/html',
	'QUERY_STRING' => (string) parse_url( $path, PHP_URL_QUERY ),
] );
$GLOBALS['zwl_status'] = 200;
set_error_handler( function ( $no, $str, $file, $line ) {
	if ( ! ( error_reporting() & $no ) ) { return false; }
	fwrite( STDERR, "PHP[$no] $str in $file:$line\n" );
	return true;
} );
// Haken vóór WordPress laadt (WP bouwt $wp_filter om tot WP_Hook).
$GLOBALS['wp_filter']['status_header'][10][] = [ 'function' => function ( $h, $code ) {
	$GLOBALS['zwl_status'] = $code; return $h; }, 'accepted_args' => 2 ];
$GLOBALS['wp_filter']['wp_redirect'][PHP_INT_MAX][] = [ 'function' => function ( $loc, $code ) {
	fwrite( STDERR, "REDIRECT $code $loc\n" ); $GLOBALS['zwl_status'] = $code; return false; }, 'accepted_args' => 2 ];
register_shutdown_function( function () {
	$e = error_get_last();
	if ( $e && in_array( $e['type'], [ E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR ], true ) ) {
		fwrite( STDERR, "FATAL {$e['message']} in {$e['file']}:{$e['line']}\n" );
	}
	fwrite( STDERR, 'STATUS ' . $GLOBALS['zwl_status'] . "\n" );
} );
define( 'WP_USE_THEMES', true );
require getcwd() . '/wp-blog-header.php';

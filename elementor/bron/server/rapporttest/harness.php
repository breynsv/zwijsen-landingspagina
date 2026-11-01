<?php
/**
 * Eén "verzoek" door de echte WordPress-ingang (wp-admin/admin.php, admin-post.php, wp-login.php ...)
 * vanuit de CLI, met echte inlogcookies. Geen webserver: geen htpasswd, LiteSpeed of verzoekfilter.
 *   php harness.php '<json>'   json: name, script, query{}, post{}, cookies{}
 * Schrijft OUT/<name>.json (status, redirect) en OUT/<name>.body.
 */
$a    = json_decode( $argv[1], true );
$root = getenv( 'HOME' ) . '/subsites/staging.zwijsen.be';
$out  = getenv( 'HOME' ) . '/tmp/zwl/rapport/out';
@mkdir( $out, 0700, true );
chdir( $root );
$qs     = http_build_query( $a['query'] ?? array() );
$script = '/' . ltrim( $a['script'], '/' );
$post   = $a['post'] ?? null;
$_SERVER['HTTP_HOST']       = 'staging.zwijsen.be';
$_SERVER['SERVER_NAME']     = 'staging.zwijsen.be';
$_SERVER['HTTPS']           = 'on';
$_SERVER['SERVER_PORT']     = '443';
$_SERVER['REQUEST_METHOD']  = $post ? 'POST' : 'GET';
$_SERVER['REQUEST_URI']     = $script . ( $qs ? '?' . $qs : '' );
$_SERVER['QUERY_STRING']    = $qs;
$_SERVER['PHP_SELF']        = $script;
$_SERVER['SCRIPT_NAME']     = $script;
$_SERVER['SCRIPT_FILENAME'] = $root . $script;
$_SERVER['REMOTE_ADDR']     = '192.0.2.77';
$_SERVER['HTTP_USER_AGENT'] = 'zwl-rapport-harness/1.0';
$_GET     = $a['query'] ?? array();
$_POST    = $post ?: array();
$_COOKIE  = $a['cookies'] ?? array();
$_REQUEST = array_merge( $_GET, $_POST );
$GLOBALS['zwl_redir'] = null;
$GLOBALS['wp_filter']['wp_redirect'][ PHP_INT_MAX ][] = array(
	'function'      => function ( $l, $s ) { $GLOBALS['zwl_redir'] = array( $l, $s ); return $l; },
	'accepted_args' => 2,
);
ob_start();
register_shutdown_function(
	function () use ( $a, $out ) {
		$parts = array();
		while ( ob_get_level() > 0 ) { array_unshift( $parts, (string) ob_get_clean() ); }
		$body = implode( '', $parts );
		file_put_contents( "$out/{$a['name']}.body", $body );
		$err = error_get_last();
		file_put_contents( "$out/{$a['name']}.json", json_encode( array(
			'name'     => $a['name'],
			'status'   => http_response_code(),
			'redirect' => $GLOBALS['zwl_redir'],
			'user'     => function_exists( 'get_current_user_id' ) ? get_current_user_id() : null,
			'bytes'    => strlen( $body ),
			'fatal'    => ( $err && in_array( $err['type'], array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR ), true ) ) ? $err['message'] : null,
		), JSON_UNESCAPED_SLASHES ) );
	}
);
require $root . $script;

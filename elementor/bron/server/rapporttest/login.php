<?php
// php login.php : echte wp-login.php-POST (wachtwoord) voor de testgebruiker, Hack Guard en Wordfence actief.
$d = __DIR__;
$pw = trim( file_get_contents( "$d/pw.txt" ) );
foreach ( array( 'login_ok' => $pw, 'login_fout' => 'verkeerd-wachtwoord-123' ) as $n => $p ) {
	$spec = array( 'name' => $n, 'script' => 'wp-login.php', 'query' => array(), 'cookies' => array( 'wordpress_test_cookie' => 'WP Cookie check' ),
		'post' => array( 'log' => 'zwl-rapport-test', 'pwd' => $p, 'wp-submit' => 'Inloggen', 'redirect_to' => 'https://staging.zwijsen.be/wp-admin/', 'testcookie' => '1' ) );
	exec( 'php ' . escapeshellarg( "$d/harness.php" ) . ' ' . escapeshellarg( json_encode( $spec ) ) . ' 2>&1' );
	echo file_get_contents( "$d/out/$n.json" ), "\n";
}
$b = file_get_contents( "$d/out/login_fout.body" );
preg_match( '#<div id="login_error"[^>]*>(.*?)</div>#s', $b, $m );
echo 'foutmelding: ', trim( preg_replace( '/\s+/', ' ', strip_tags( $m[1] ?? '(geen)' ) ) ), "\n";

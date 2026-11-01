<?php
// wp eval-file cookies.php <user_id>  -> JSON met auth- en logged_in-cookie en de CSV-nonce voor die sessie.
$uid = (int) $args[0];
$exp = time() + 6*3600;
$token = WP_Session_Tokens::get_instance( $uid )->create( $exp );
$c = array(
	SECURE_AUTH_COOKIE => wp_generate_auth_cookie( $uid, $exp, 'secure_auth', $token ),
	LOGGED_IN_COOKIE   => wp_generate_auth_cookie( $uid, $exp, 'logged_in', $token ),
);
$_COOKIE[ LOGGED_IN_COOKIE ] = $c[ LOGGED_IN_COOKIE ];
wp_set_current_user( $uid );
echo json_encode( array( 'cookies' => $c, 'nonce' => wp_create_nonce( 'zwijsen_sollicitaties_csv' ) ) );

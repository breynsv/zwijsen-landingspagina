<?php
wp_set_current_user( 22 );
foreach ( array( '/elementor/v1/form-submissions', '/elementor/v1/form-submissions/3377', '/wp/v2/users', '/wp/v2/pages/3292' ) as $r ) {
	$q = new WP_REST_Request( 'GET', $r );
	if ( '/wp/v2/pages/3292' === $r ) { $q->set_param( 'context', 'edit' ); }
	$res = rest_do_request( $q );
	echo "$r -> ", $res->get_status(), "\n";
}
$rest_routes = array_filter( array_keys( rest_get_server()->get_routes() ), fn( $x ) => false !== strpos( $x, 'zwijsen' ) );
echo 'zwijsen-REST-routes: ', implode( ', ', $rest_routes ), "\n";
echo 'bestandsnaam: ', zwijsen_snel_csv_bestandsnaam( zwijsen_snel_filter( array( 'van' => '2026-11-01', 'tot' => '2026-11-30', 'regio' => 'Lier en Antwerpen-Zuidrand' ) ) ), ' | ', zwijsen_snel_csv_bestandsnaam( zwijsen_snel_filter( array() ) ), "\n";
echo 'omgekeerde periode: ', json_encode( zwijsen_snel_filter( array( 'van' => '2026-11-30', 'tot' => '2026-11-01', 'regio' => '<script>' ) ) ), "\n";
echo 'zhg disabled 22: ', var_export( zhg_user_is_disabled( 22 ), true ), ', rollen: ', implode( ',', get_userdata( 22 )->roles ), "\n";
echo 'user_can 22 edit_posts/manage_options/upload_files: ', var_export( user_can( 22, 'edit_posts' ), true ), '/', var_export( user_can( 22, 'manage_options' ), true ), '/', var_export( user_can( 22, 'upload_files' ), true ), "\n";
echo 'subscriber 23 cap: ', var_export( user_can( 23, 'zwijsen_bekijk_sollicitaties' ), true ), "\n";

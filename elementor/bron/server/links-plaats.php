<?php
// Productie: post 85 en term 16 gelijkzetten aan de staging-bestanden. Controleert vooraf dat
// productie nog het origineel heeft (md5), schrijft term 16 rechtstreeks (geen kses), leest terug.
global $wpdb;
$d = $args[0];
$p85 = file_get_contents( "$d/post-85-staging.html" ); $t16 = file_get_contents( "$d/term-16-staging.html" );
if ( md5( $p85 ) !== 'b20c2632fcef95dda6dd82ac44e1d81c' || md5( $t16 ) !== '6cc78460fbecbbd2522278d22f1da605' ) { WP_CLI::error( 'bronbestanden kloppen niet' ); }
$nu85 = $wpdb->get_var( "SELECT post_content FROM {$wpdb->posts} WHERE ID=85" );
$nu16 = $wpdb->get_var( "SELECT description FROM {$wpdb->term_taxonomy} WHERE term_id=16 AND taxonomy='category'" );
if ( md5( $nu85 ) === md5( $p85 ) ) { WP_CLI::log( 'post 85 al gelijk' ); }
elseif ( md5( $nu85 ) !== 'bb62380bbfea8d47526bf6a10a874837' ) { WP_CLI::error( 'post 85 is niet het verwachte origineel' ); }
elseif ( 0 !== strpos( $p85, substr( $nu85, 0, -strlen( "\r\n<!-- /wp:paragraph -->" ) ) ) ) { WP_CLI::error( 'post 85: staging begint niet met het productie-origineel' ); }
else {
	$r = wp_update_post( wp_slash( [ 'ID' => 85, 'post_content' => $p85 ] ), true );
	if ( is_wp_error( $r ) ) { WP_CLI::error( $r->get_error_message() ); }
}
if ( md5( $nu16 ) === md5( $t16 ) ) { WP_CLI::log( 'term 16 al gelijk' ); }
elseif ( md5( $nu16 ) !== 'a3fdb881a2ade85640cd57f6ae0af5c8' ) { WP_CLI::error( 'term 16 is niet het verwachte origineel' ); }
elseif ( 0 !== strpos( $t16, $nu16 ) && 0 !== strpos( $t16, rtrim( $nu16 ) ) ) { WP_CLI::error( 'term 16: staging begint niet met het productie-origineel' ); }
else {
	$n = $wpdb->update( $wpdb->term_taxonomy, [ 'description' => $t16 ], [ 'term_id' => 16, 'taxonomy' => 'category' ] );
	if ( 1 !== $n ) { WP_CLI::error( "term 16: $n rijen bijgewerkt" ); }
	clean_term_cache( 16, 'category' );
}
clean_post_cache( 85 );
$na85 = $wpdb->get_var( "SELECT post_content FROM {$wpdb->posts} WHERE ID=85" );
$na16 = $wpdb->get_var( "SELECT description FROM {$wpdb->term_taxonomy} WHERE term_id=16 AND taxonomy='category'" );
WP_CLI::log( 'post 85: ' . md5( $na85 ) . ' ' . strlen( $na85 ) . ' | term 16: ' . md5( $na16 ) . ' ' . strlen( $na16 ) . ' | get_term_field: ' . md5( get_term_field( 'description', 16, 'category', 'raw' ) ) );
if ( md5( $na85 ) !== md5( $p85 ) || md5( $na16 ) !== md5( $t16 ) ) { WP_CLI::error( 'teruglezen klopt niet' ); }
do_action( 'litespeed_purge_post', 85 );
WP_CLI::success( 'post 85 en term 16 = staging' );

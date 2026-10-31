<?php
/**
 * Haalt een landingspagina uit de database als importeerbare Elementor-sjabloon
 * (formaat 0.4, type page, met pagina-instellingen en CSS).
 *   wp eval-file exporteer.php <post-id> > pagina.json
 */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
$id = (int) ( $args[0] ?? 0 );
$post = get_post( $id );
if ( ! $post || ! get_post_meta( $id, '_zwl_landing', true ) ) { WP_CLI::error( "Post $id is geen landingspagina van dit script" ); }
$data = json_decode( get_post_meta( $id, '_elementor_data', true ), true );
$settings = get_post_meta( $id, '_elementor_page_settings', true ) ?: [];
echo wp_json_encode( [
	'content' => $data, 'page_settings' => $settings, 'version' => '0.4',
	'title' => get_the_title( $id ), 'type' => 'page',
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ), "\n";

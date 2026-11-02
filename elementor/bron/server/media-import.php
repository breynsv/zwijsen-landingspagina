<?php
/**
 * 5d stap 1: de 13 foto's van de regiopagina's in de mediabibliotheek van DEZE site zetten.
 *   wp eval-file media-import.php <map met jpg's + staging-media.jsonl> --user=breynsv --skip-plugins=imagify
 * Titel, alt, slug, bijschrift en beschrijving zoals op staging. Herkend aan meta
 * _zwl_staging_bron = staging-id, dus opnieuw draaien maakt niets dubbel.
 * Drukt per foto een JSON-regel af: {sleutel, sid, id, url, alt}.
 */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
$dir = rtrim( (string) ( $args[0] ?? '' ), '/' );
foreach ( file( "$dir/staging-media.jsonl", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES ) as $line ) {
	$m = json_decode( $line, true );
	$bestaand = get_posts( [ 'post_type' => 'attachment', 'post_status' => 'any', 'meta_key' => '_zwl_staging_bron', 'meta_value' => $m['sid'], 'fields' => 'ids', 'numberposts' => 1 ] );
	if ( $bestaand ) {
		$id = $bestaand[0];
	} else {
		$src = "$dir/{$m['file']}";
		if ( ! is_readable( $src ) ) { WP_CLI::error( "ontbreekt: $src" ); }
		$tmp = wp_tempnam( $m['file'] );
		copy( $src, $tmp );
		$id = media_handle_sideload( [ 'name' => $m['file'], 'tmp_name' => $tmp ], 0, $m['title'], [ 'post_excerpt' => $m['caption'], 'post_content' => $m['desc'], 'post_name' => $m['name'] ] );
		if ( is_wp_error( $id ) ) { @unlink( $tmp ); WP_CLI::error( $m['file'] . ': ' . $id->get_error_message() ); }
		update_post_meta( $id, '_wp_attachment_image_alt', $m['alt'] );
		update_post_meta( $id, '_zwl_staging_bron', $m['sid'] );
	}
	$p = get_post( $id );
	if ( $p->post_title !== $m['title'] || get_post_meta( $id, '_wp_attachment_image_alt', true ) !== $m['alt'] ) { WP_CLI::error( "titel/alt klopt niet voor $id" ); }
	if ( md5_file( get_attached_file( $id ) ) !== md5_file( "$dir/{$m['file']}" ) ) { WP_CLI::error( "bestand van $id verschilt van de bron" ); }
	echo wp_json_encode( [ 'sleutel' => pathinfo( $m['file'], PATHINFO_FILENAME ), 'sid' => (int) $m['sid'], 'id' => (int) $id, 'url' => wp_get_attachment_url( $id ), 'alt' => $m['alt'], 'name' => $p->post_name, 'sizes' => count( (array) ( wp_get_attachment_metadata( $id )['sizes'] ?? [] ) ) ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ), "\n";
}

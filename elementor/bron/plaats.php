<?php
/**
 * Zet één landingspagina op de WordPress-site waar dit draait.
 *   wp eval-file plaats.php /pad/naar/pagina-N.json --user=<beheerder>
 *
 * Maakt de pagina aan als ze nog niet bestaat. Bestaat ze al, dan wordt ze
 * alleen bijgewerkt als ze door dit script gemaakt is (meta _zwl_landing),
 * zodat een bestaande pagina met dezelfde slug nooit overschreven wordt.
 */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }

$file = $args[0] ?? '';
$p = json_decode( file_get_contents( $file ), true );
if ( ! is_array( $p ) || empty( $p['slug'] ) || empty( $p['elements'] ) ) {
	WP_CLI::error( "Ongeldig bestand: $file" );
}

$existing = get_page_by_path( $p['slug'], OBJECT, 'page' );
if ( $existing ) {
	if ( ! get_post_meta( $existing->ID, '_zwl_landing', true ) ) {
		WP_CLI::error( "Slug {$p['slug']} bestaat al (post {$existing->ID}) en is niet van dit script — niets aangeraakt." );
	}
	$id = $existing->ID;
	wp_update_post( [ 'ID' => $id, 'post_title' => $p['title'] ] );
} else {
	$id = wp_insert_post( [
		'post_type'   => 'page',
		'post_status' => 'publish',
		'post_title'  => $p['title'],
		'post_name'   => $p['slug'],
		'post_content' => '',
	], true );
	if ( is_wp_error( $id ) ) { WP_CLI::error( $id->get_error_message() ); }
	update_post_meta( $id, '_zwl_landing', 1 );
	update_post_meta( $id, '_wp_page_template', 'default' );
}

$doc = \Elementor\Plugin::$instance->documents->get( $id, false );
$doc->set_is_built_with_elementor( true );
update_post_meta( $id, '_elementor_template_type', 'wp-page' );

$ok = $doc->save( [ 'elements' => $p['elements'], 'settings' => $p['settings'] ] );
if ( ! $ok ) { WP_CLI::error( "Opslaan mislukt voor post $id" ); }

// Teruglezen: is het JSON nog geldig en zijn de widgets er allemaal?
$raw = get_post_meta( $id, '_elementor_data', true );
$back = json_decode( $raw, true );
if ( ! is_array( $back ) ) { WP_CLI::error( "Teruglezen _elementor_data mislukt voor post $id" ); }
$count = function ( $els ) use ( &$count ) { $n = 0; foreach ( $els as $e ) { $n += 1 + $count( $e['elements'] ?? [] ); } return $n; };
$want = $count( $p['elements'] ); $got = $count( $back );
if ( $want !== $got ) { WP_CLI::error( "Aantal elementen klopt niet voor post $id: $got i.p.v. $want" ); }
$settings = get_post_meta( $id, '_elementor_page_settings', true );
if ( empty( $settings['custom_css'] ) || ( $settings['hide_title'] ?? '' ) !== 'yes' ) {
	WP_CLI::error( "Pagina-instellingen niet bewaard voor post $id" );
}

WP_CLI::success( sprintf( '%d %s %s (%d elementen, versie %s)', $id, get_permalink( $id ), $p['title'], $got,
	get_post_meta( $id, '_elementor_version', true ) ) );

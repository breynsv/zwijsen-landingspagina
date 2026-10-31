<?php
/**
 * Vacaturekaarten voor de regiopagina's (staging, 31 okt 2026).
 *
 *   wp eval-file zwl-vacaturekaarten.php <backupmap> --user=breynsv
 *
 * 1. Zet _elementor_data van 1641 (open-vacatures) en 1145 (werken-bij) byte voor byte
 *    terug naar de kopie van vóór het knoppenblok (<backupmap>/staging-elementor-data-<id>-voor-links-20261031-2007.json).
 * 2. Maakt (of werkt bij) twee berichten van het type `vacatures`, herkend aan meta
 *    `_zwl_landing_kaart` = id van de regiopagina. Uitgelichte afbeelding + ACF-veld
 *    `profiel` (dat toont de kaart onder de titel), letterlijk uit "Jouw profiel" van de regiopagina.
 *    De kaart linkt via de dynamische tag post-url (= permalink) naar /Vacatures/<slug>/;
 *    die URL stuurt .htaccess met een 301 door naar de regiopagina.
 * 3. Schrijft de twee 301-regels in het blok `# BEGIN/END zwijsen-landing` van .htaccess.
 *
 * Opnieuw draaien is veilig: niets wordt dubbel aangemaakt. De kaartsjablonen (1595, 1462)
 * en de Posts-widget worden niet aangeraakt.
 * Op productie: andere bijlage-id's (MEDIA) en de pagina-id's van de regiopagina's controleren;
 * stap 1 alleen als daar ook een knoppenblok staat.
 */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
$dir = rtrim( (string) ( $args[0] ?? '' ), '/' ) . '/';

// ---------- 1. pagina's terugzetten ----------
foreach ( [ 1641, 1145 ] as $id ) {
	$f = $dir . "staging-elementor-data-$id-voor-links-20261031-2007.json";
	if ( ! is_readable( $f ) ) { WP_CLI::error( "Kopie ontbreekt: $f" ); }
	$raw = rtrim( file_get_contents( $f ), "\n" );
	if ( ! is_array( json_decode( $raw, true ) ) ) { WP_CLI::error( "Kopie is geen geldige JSON: $f" ); }
	if ( get_post_meta( $id, '_elementor_data', true ) !== $raw ) {
		update_metadata( 'post', $id, '_elementor_data', wp_slash( $raw ) );
	}
	if ( get_post_meta( $id, '_elementor_data', true ) !== $raw ) { WP_CLI::error( "Teruglezen klopt niet voor $id" ); }
	delete_post_meta( $id, '_elementor_element_cache' );
	( new \Elementor\Core\Files\CSS\Post( $id ) )->update();
	clean_post_cache( $id );
	do_action( 'litespeed_purge_post', $id );
	WP_CLI::log( "$id: _elementor_data = kopie van vóór het knoppenblok (" . strlen( $raw ) . ' bytes)' );
}

// ---------- 2. vacatureberichten ----------
$li = function ( array $items ) {
	return "<ul>\n" . implode( "\n", array_map( fn( $t ) => "\t<li>" . esc_html( $t ) . '</li>', $items ) ) . "\n</ul>";
};
$gemeen = [
	'Minstens 3 jaar ervaring als elektricien',
	null, // woonplaats per regio
	'Je hebt minstens rijbewijs B',
	'Taal is geen barrière. Je begrijpt en spreekt voldoende Nederlands, Frans of Engels om veilig samen te werken.',
	'In het bezit zijn van een VCA-attest is een plus. Heb je het niet, dan voorzien wij een opleiding.',
	'Ervaring op werven is mooi meegenomen.',
];
$jobs = [
	[ 'landing' => 3292, 'title' => 'Elektricien – regio Dendermonde', 'slug' => 'elektricien-dendermonde',
	  'media' => 3325, 'woon' => 'Je woont in (of vlakbij) Dendermonde', 'date' => '2026-10-31 21:00:01' ],
	[ 'landing' => 3350, 'title' => 'Elektricien – Lier en Antwerpen-Zuidrand', 'slug' => 'elektricien-lier-antwerpen-zuidrand',
	  'media' => 3312, 'woon' => 'Je woont in (of vlakbij) Hoboken, Wilrijk, Edegem, Mortsel, Hove, Boechout, Lint, Kontich, Duffel of Lier', 'date' => '2026-10-31 21:00:00' ],
];
$regels = [];
foreach ( $jobs as $j ) {
	if ( 'page' !== get_post_type( $j['landing'] ) || 'publish' !== get_post_status( $j['landing'] ) ) { WP_CLI::error( "Regiopagina {$j['landing']} niet gevonden" ); }
	if ( 'attachment' !== get_post_type( $j['media'] ) ) { WP_CLI::error( "Bijlage {$j['media']} niet gevonden" ); }
	$bestaand = get_posts( [ 'post_type' => 'vacatures', 'post_status' => 'any', 'meta_key' => '_zwl_landing_kaart', 'meta_value' => $j['landing'], 'fields' => 'ids', 'numberposts' => 2 ] );
	$post = [ 'post_type' => 'vacatures', 'post_status' => 'publish', 'post_title' => $j['title'], 'post_name' => $j['slug'],
		'post_date' => $j['date'], 'post_content' => '', 'comment_status' => 'closed', 'ping_status' => 'closed' ];
	if ( $bestaand ) { $post['ID'] = $bestaand[0]; $id = wp_update_post( wp_slash( $post ), true ); }
	else { $id = wp_insert_post( wp_slash( $post ), true ); }
	if ( is_wp_error( $id ) ) { WP_CLI::error( $id->get_error_message() ); }
	update_post_meta( $id, '_zwl_landing_kaart', $j['landing'] );
	set_post_thumbnail( $id, $j['media'] );
	$items = $gemeen; $items[1] = $j['woon'];
	update_field( 'field_61e5369895d82', $li( $items ), $id ); // profiel
	// Uit de sitemap: de URL van dit bericht is enkel een doorverwijzing.
	update_post_meta( $id, '_yoast_wpseo_meta-robots-noindex', '1' );
	$pad = wp_parse_url( get_permalink( $id ), PHP_URL_PATH );
	$doel = wp_parse_url( get_permalink( $j['landing'] ), PHP_URL_PATH );
	if ( ! preg_match( '#^/[A-Za-z0-9/-]+/$#', $pad ) ) { WP_CLI::error( "Onverwacht pad $pad" ); }
	$regels[] = 'RewriteRule ^' . trim( $pad, '/' ) . '/?$ https://%{HTTP_HOST}' . $doel . ' [R=301,L,NC]';
	clean_post_cache( $id );
	WP_CLI::log( "vacature $id \"{$j['title']}\" $pad -> 301 $doel (afbeelding {$j['media']})" );
}

// ---------- 3. .htaccess ----------
$ht = ABSPATH . '.htaccess';
$s = file_get_contents( $ht );
if ( ! preg_match( '/# BEGIN zwijsen-landing.*?# END zwijsen-landing/s', $s, $m ) ) { WP_CLI::error( 'Blok zwijsen-landing niet gevonden in .htaccess' ); }
$blok = $m[0];
$blok = preg_replace( '/\n# Vacaturekaarten.*?(?=\n<\/IfModule>)/s', '', $blok ); // eerdere versie weg
$extra = "\n# Vacaturekaarten op /open-vacatures/ (31 okt 2026): de berichten bestaan zodat ze als kaart in\n"
	. "# het overzicht staan; hun eigen URL stuurt door naar de regiopagina.\n" . implode( "\n", $regels );
$blok = str_replace( "\n</IfModule>", $extra . "\n</IfModule>", $blok );
$nieuw = str_replace( $m[0], $blok, $s );
if ( $nieuw !== $s ) {
	file_put_contents( $ht, $nieuw );
	if ( file_get_contents( $ht ) !== $nieuw ) { WP_CLI::error( '.htaccess teruglezen klopt niet' ); }
}
WP_CLI::log( ".htaccess:\n" . $blok );
do_action( 'litespeed_purge_all' );
WP_CLI::success( 'Klaar' );

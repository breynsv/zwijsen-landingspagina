<?php
/**
 * Zet één klein native blok met twee knoppen naar de regiopagina's op een
 * bestaande pagina, direct na het element <na-id>. Raakt verder niets aan.
 *   wp eval-file zwl-links.php <post-id> <na-id> --user=breynsv
 * Opnieuw draaien vervangt het blok (herkend aan de klasse zwl-regio-links).
 * Knopstijl = de bestaande knop "Solliciteer Nu" van de site (kitkleuren accent
 * en geel bij hover, Roboto 15/500, pijl rechts).
 */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
[ $id, $after ] = [ (int) ( $args[0] ?? 0 ), (string) ( $args[1] ?? '' ) ];
$raw = get_post_meta( $id, '_elementor_data', true );
$data = json_decode( $raw, true );
if ( ! is_array( $data ) || ! $after ) { WP_CLI::error( 'Ongeldige pagina of anker' ); }

$rid = function () { return substr( md5( uniqid( '', true ) ), 0, 7 ); };
$knop = function ( $tekst, $url ) use ( $rid ) {
	return [ 'id' => $rid(), 'elType' => 'widget', 'widgetType' => 'button', 'elements' => [], 'settings' => [
		'text' => $tekst,
		'link' => [ 'url' => $url, 'is_external' => '', 'nofollow' => '', 'custom_attributes' => '' ],
		'selected_icon' => [ 'value' => 'fas fa-arrow-right', 'library' => 'fa-solid' ],
		'icon_align' => 'right', 'icon_indent' => [ 'unit' => 'px', 'size' => 15, 'sizes' => [] ],
		'typography_typography' => 'custom', 'typography_font_family' => 'Roboto',
		'typography_font_size' => [ 'unit' => 'px', 'size' => 15, 'sizes' => [] ],
		'typography_font_size_mobile' => [ 'unit' => 'px', 'size' => 14, 'sizes' => [] ],
		'typography_font_weight' => '500',
		'__globals__' => [ 'background_color' => 'globals/colors?id=accent', 'button_background_hover_color' => 'globals/colors?id=78f9a77' ],
		'_element_width' => 'auto',
		'_margin' => [ 'unit' => 'px', 'top' => '0', 'right' => '12', 'bottom' => '12', 'left' => '0', 'isLinked' => false ],
	] ];
};
$blok = [ 'id' => $rid(), 'elType' => 'section', 'isInner' => true, 'settings' => [
		'css_classes' => 'zwl-regio-links',
		'margin' => [ 'unit' => 'px', 'top' => '0', 'right' => 0, 'bottom' => '18', 'left' => 0, 'isLinked' => false ],
	], 'elements' => [ [ 'id' => $rid(), 'elType' => 'column', 'isInner' => true,
		'settings' => [ '_column_size' => 100, '_inline_size' => null ],
		'elements' => [
			$knop( 'Elektricien – regio Dendermonde', '/elektricien-dendermonde/' ),
			$knop( 'Elektricien – Lier en Antwerpen-Zuidrand', '/elektricien-lier-antwerpen-zuidrand/' ),
		] ] ] ];

$done = 0;
$walk = function ( array $els ) use ( &$walk, $after, $blok, &$done ) {
	$out = [];
	foreach ( $els as $e ) {
		if ( ( $e['settings']['css_classes'] ?? '' ) === 'zwl-regio-links' ) { continue; } // oud blok weg
		if ( ! empty( $e['elements'] ) ) { $e['elements'] = $walk( $e['elements'] ); }
		$out[] = $e;
		if ( $e['id'] === $after ) { $out[] = $blok; $done++; }
	}
	return $out;
};
$new = $walk( $data );
if ( 1 !== $done ) { WP_CLI::error( "Anker $after niet (of meer dan eens) gevonden op $id" ); }

// Bewust niet via Document::save: dat zou ook versie-meta en pagina-instellingen
// herschrijven. Alleen _elementor_data verandert; caches van die pagina worden geleegd.
update_metadata( 'post', $id, '_elementor_data', wp_slash( wp_json_encode( $new ) ) );
delete_post_meta( $id, '_elementor_element_cache' );
$back = json_decode( get_post_meta( $id, '_elementor_data', true ), true );
if ( $back !== $new ) { WP_CLI::error( "Teruglezen klopt niet voor $id" ); }
( new \Elementor\Core\Files\CSS\Post( $id ) )->update();
clean_post_cache( $id );
WP_CLI::success( "Blok geplaatst op $id na $after" );

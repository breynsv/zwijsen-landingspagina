<?php
/**
 * Plugin Name: Zwijsen – Meta-domeinverificatie
 * Description: Zet de metatag van Meta Business Suite (domein zwijsen.be) in de <head> van de startpagina.
 *              Code van Jana, 3 november 2026 (Bedrijfsinstellingen > Merkveiligheid > Domeinen > Metatag).
 *              Bron: github breynsv/zwijsen-landingspagina, elementor/bron/mu-plugins/.
 *              Weghalen = dit bestand verwijderen.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'wp_head', function () {
	if ( is_front_page() ) {
		echo '<meta name="facebook-domain-verification" content="f6tolw26448uums8klcr6ji7lrir1i" />' . "\n";
	}
}, 1 );

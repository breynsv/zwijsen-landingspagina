<?php
// wp eval-file data.php : endpointtests (geldig/honeypot/ongeldig) + testinzendingen voor het rapport.
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
global $wpdb;
$T = $wpdb->prefix . 'e_submissions';
$mails = 0;
add_filter( 'pre_wp_mail', function ( $kort ) use ( &$mails ) { $mails++; return $kort; }, 0, 1 );
$geldig = array(
	'formulier' => 'dendermonde', 'werkervaring' => '3-4', 'talen' => array( 'nederlands', 'frans' ),
	'rijbewijs' => 'be', 'woonplaats' => '9200 Dendermonde', 'naam' => 'Rapport Testpersoon',
	'email' => 'rapport.testpersoon@example.com', 'landcode' => 'BE', 'telefoon' => '0470 99 88 77',
	'company_website' => '', 'bron' => '?utm_source=facebook&utm_medium=paid_social&utm_campaign=elektricien_dendermonde&utm_content=video_a&fbclid=IwAR_GEHEIM123&gclid=GCLIDGEHEIM',
	'tijdstip' => '2026-11-01T17:00:00.000Z',
);
function zw_req( $body, $ip ) {
	$_SERVER['REMOTE_ADDR'] = $ip;
	$_SERVER['HTTP_USER_AGENT'] = 'zwijsen-rapporttest/1.0 (rest_do_request) UA-GEHEIM';
	$r = new WP_REST_Request( 'POST', '/zwijsen/v1/sollicitatie' );
	$r->set_header( 'Content-Type', 'application/json' );
	$r->set_body( wp_json_encode( $body ) );
	return rest_do_request( $r );
}
$gevallen = array(
	array( 'geldig Dendermonde, UTM + fbclid', $geldig, '192.0.2.61' ),
	array( 'geldig Lier, utm_term + formule in utm_content', array_merge( $geldig, array( 'formulier' => 'lier', 'talen' => array( 'engels', 'andere' ), 'talen_andere' => 'Pools', 'werkervaring' => 'geen', 'rijbewijs' => 'nee', 'woonplaats' => '2500 Lier', 'naam' => 'Tweede Testpersoon', 'bron' => '?utm_source=meta&utm_campaign=lier_q4&utm_content==HYPERLINK("http://x.example")&utm_term=elektricien' ) ), '192.0.2.61' ),
	array( 'geldig Lier, zonder bron', array_merge( $geldig, array( 'formulier' => 'lier', 'woonplaats' => '2640 Mortsel', 'naam' => 'Derde Testpersoon', 'bron' => '' ) ), '192.0.2.61' ),
	array( 'honeypot', array_merge( $geldig, array( 'company_website' => 'http://spam.example' ) ), '192.0.2.62' ),
	array( 'ongeldig: antwoord buiten lijst + kort nummer', array_merge( $geldig, array( 'werkervaring' => '99', 'telefoon' => '1234' ) ), '192.0.2.63' ),
	array( 'ongeldig: e-mail', array_merge( $geldig, array( 'email' => 'geen-adres' ) ), '192.0.2.63' ),
);
$ids = array();
foreach ( $gevallen as $g ) {
	$voor = (int) $wpdb->get_var( "SELECT MAX(id) FROM $T" );
	$mails = 0;
	$res = zw_req( $g[1], $g[2] );
	$nieuw = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM $T WHERE id > %d", $voor ) );
	$ids = array_merge( $ids, $nieuw );
	printf( "== %s: HTTP %d %s | nieuw: %s | mails: %d\n", $g[0], $res->get_status(), wp_json_encode( $res->get_data(), JSON_UNESCAPED_UNICODE ), $nieuw ? implode( ',', $nieuw ) : '-', $mails );
}
// Derde testrij net na middernacht Brusselse tijd zetten (23:30 UTC 31-10 = 00:30 CET 1-11), voor de periodetest.
if ( isset( $ids[2] ) ) {
	$wpdb->update( $T, array( 'created_at_gmt' => '2026-10-31 23:30:00', 'created_at' => '2026-10-31 23:30:00' ), array( 'id' => (int) $ids[2] ) );
}
foreach ( array( '192.0.2.61', '192.0.2.62', '192.0.2.63' ) as $ip ) { delete_transient( 'zwsnel_' . md5( $ip ) ); }
echo 'TESTIDS=' . implode( ',', $ids ) . "\n";

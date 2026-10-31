<?php
/**
 * Test van de REST-route zwijsen/v1/sollicitatie via rest_do_request (geen webserver).
 *   wp eval-file test-formulier.php
 * Vangt elke wp_mail op (prioriteit 0, laat de mailblokkade van staging gewoon
 * zijn werk doen) en drukt per geval status, antwoord, nieuwe inzendingen en mails af.
 * Gebruikt TEST-NET-adressen (192.0.2.x) als IP en ruimt hun tellers op het einde op.
 */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }

global $wpdb;
$T = $wpdb->prefix . 'e_submissions';
$mails = array();
add_filter( 'pre_wp_mail', function ( $kort, $atts ) use ( &$mails ) { $mails[] = $atts; return $kort; }, 0, 2 );
$alt = array();
add_action( 'phpmailer_init', function ( $m ) use ( &$alt ) { $alt[] = $m->AltBody; }, 99 );

$geldig = array(
	'formulier' => 'dendermonde', 'werkervaring' => '3-4', 'talen' => array( 'nederlands', 'frans' ),
	'rijbewijs' => 'be', 'woonplaats' => '9200 Dendermonde', 'naam' => 'Test Sollicitant',
	'email' => 'test.sollicitant@example.com', 'landcode' => 'BE', 'telefoon' => '0470 12 34 56',
	'company_website' => '', 'vacature' => 'Technieker Algemene Elektriciteit', 'regio_campagne' => 'Dendermonde',
	'bron' => '?utm_source=test&utm_campaign=formuliertest', 'tijdstip' => '2026-10-31T21:40:00.000Z',
);

function zw_req( $body, $ip, $ctype = 'application/json', $raw = null ) {
	$_SERVER['REMOTE_ADDR'] = $ip;
	$_SERVER['HTTP_USER_AGENT'] = 'zwijsen-formuliertest/1.0 (rest_do_request)';
	$r = new WP_REST_Request( 'POST', '/zwijsen/v1/sollicitatie' );
	$r->set_header( 'Content-Type', $ctype );
	$r->set_body( null !== $raw ? $raw : wp_json_encode( $body ) );
	return rest_do_request( $r );
}

$gevallen = array(
	array( 'geldig Dendermonde', $geldig, '192.0.2.10' ),
	array( 'geldig Lier, andere taal', array_merge( $geldig, array( 'formulier' => 'lier', 'talen' => array( 'engels', 'andere' ), 'talen_andere' => 'Pools', 'werkervaring' => 'geen', 'rijbewijs' => 'nee', 'woonplaats' => '2640 Mortsel', 'naam' => 'Test Tweede <b>Sollicitant</b>', 'bron' => '' ) ), '192.0.2.10' ),
	array( 'honeypot', array_merge( $geldig, array( 'company_website' => 'http://spam.example' ) ), '192.0.2.20' ),
	array( 'ongeldig: antwoord buiten lijst + kort nummer', array_merge( $geldig, array( 'werkervaring' => '99', 'telefoon' => '1234' ) ), '192.0.2.30' ),
	array( 'ongeldig: andere taal zonder tekst', array_merge( $geldig, array( 'talen' => array( 'andere' ), 'talen_andere' => '' ) ), '192.0.2.30' ),
	array( 'ongeldig: onbekende pagina', array_merge( $geldig, array( 'formulier' => 'gent' ) ), '192.0.2.30' ),
	array( 'ongeldig: e-mail', array_merge( $geldig, array( 'email' => 'geen-adres' ) ), '192.0.2.30' ),
	array( 'geen JSON (formulierpost)', null, '192.0.2.31', 'application/x-www-form-urlencoded', 'naam=x' ),
	array( 'te groot (9 kB)', array_merge( $geldig, array( 'woonplaats' => str_repeat( 'x', 9000 ) ) ), '192.0.2.32' ),
);
for ( $i = 1; $i <= 6; $i++ ) {
	$gevallen[] = array( "snelheidsgrens poging $i", array_merge( $geldig, array( 'telefoon' => '12' ) ), '192.0.2.40' );
}

$ids = array();
foreach ( $gevallen as $g ) {
	$voor = (int) $wpdb->get_var( "SELECT MAX(id) FROM $T" );
	$mails = array(); $alt = array();
	$res = zw_req( $g[1], $g[2], $g[3] ?? 'application/json', $g[4] ?? null );
	$nieuw = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM $T WHERE id > %d", $voor ) );
	$ids = array_merge( $ids, $nieuw );
	printf( "== %s\n   HTTP %d %s | nieuwe inzendingen: %s | mails: %d\n", $g[0], $res->get_status(), wp_json_encode( $res->get_data(), JSON_UNESCAPED_UNICODE ), $nieuw ? implode( ',', $nieuw ) : '-', count( $mails ) );
	foreach ( $mails as $k => $m ) {
		printf( "   mail %d: To=%s | Subject=%s\n          Headers=%s\n          tekstversie: %s\n", $k + 1, $m['to'], $m['subject'], implode( ' ; ', (array) $m['headers'] ), isset( $alt[ $k ] ) && $alt[ $k ] ? 'ja' : 'n.v.t. (mailblokkade stopt vóór PHPMailer)' );
		if ( getenv( 'ZW_BODY' ) ) { echo "          --- body ---\n", $m['message'], "\n          ---\n"; }
	}
}

foreach ( $ids as $id ) {
	echo "\n== inzending $id\n";
	$row = $wpdb->get_row( $wpdb->prepare( "SELECT id,post_id,referer,referer_title,element_id,form_name,user_ip,user_agent,actions_count,actions_succeeded_count,status,meta FROM $T WHERE id=%d", $id ), ARRAY_A );
	foreach ( $row as $k => $v ) { echo "   $k: $v\n"; }
	foreach ( $wpdb->get_results( $wpdb->prepare( "SELECT `key`,value FROM {$T}_values WHERE submission_id=%d ORDER BY id", $id ) ) as $v ) { echo "   [{$v->key}] {$v->value}\n"; }
	foreach ( $wpdb->get_results( $wpdb->prepare( "SELECT action_name,action_label,status FROM {$T}_actions_log WHERE submission_id=%d", $id ) ) as $v ) { echo "   actie {$v->action_name} ({$v->action_label}): {$v->status}\n"; }
}

foreach ( array( '192.0.2.10', '192.0.2.20', '192.0.2.30', '192.0.2.31', '192.0.2.32', '192.0.2.40' ) as $ip ) {
	delete_transient( 'zwsnel_' . md5( $ip ) );
}
echo "\nTESTIDS=" . implode( ',', $ids ) . "\n";

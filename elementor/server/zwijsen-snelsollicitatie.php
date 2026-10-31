<?php
/**
 * Plugin Name: Zwijsen — snelsollicitatie regiopagina's
 * Description: Ontvangt het korte sollicitatieformulier (4 vragen + contact) van de regiopagina's elektricien. Bewaart elke inzending bij Elementor → Inzendingen, mailt personeel@zwijsen.be en stuurt de sollicitant een bevestiging. Niets gaat naar Meta of een andere derde partij.
 * Version:     1.0.0
 *
 * Route: POST /wp-json/zwijsen/v1/sollicitatie  (JSON)
 * Antwoord: { "ok": true }  of  { "ok": false, "message": "..." }
 *
 * Bron: ~/Sites/zwijsen-landing/elementor/server/zwijsen-snelsollicitatie.php
 * Het formulier zelf: assets/apply-form.js (stuurt alleen als de pagina een
 * endpoint meegeeft, via data-za-endpoint op <div id="apply">).
 *
 * Bescherming: honeypot (gevuld = "gelukt", niets bewaard of gemaild),
 * maximaal 5 inzendingen per IP per 10 minuten, maximaal 8 kB, alleen JSON,
 * geen bestanden. Elk antwoord wordt getoetst aan de gekende keuzes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const ZWIJSEN_SNEL_VERSIE     = '1.0.0';
const ZWIJSEN_SNEL_ELEMENT_ID = 'zwsnelsol';   // vaste "formulier-id" in e_submissions
const ZWIJSEN_SNEL_PERSONEEL  = 'personeel@zwijsen.be';
const ZWIJSEN_SNEL_AFZENDER   = 'website@zwijsen.be';   // de M365-postbus van FluentSMTP
const ZWIJSEN_SNEL_TELEFOON   = '015 305 305';
const ZWIJSEN_SNEL_MAX_BYTES  = 8192;
const ZWIJSEN_SNEL_LIMIET     = 5;      // inzendingen ...
const ZWIJSEN_SNEL_VENSTER    = 600;    // ... per zoveel seconden, per IP

/** Per pagina: welke pagina (op slug, dus gelijk op staging en productie), formuliernaam, regio. */
function zwijsen_snel_paginas() {
	return array(
		'dendermonde' => array(
			'slug'  => 'elektricien-dendermonde',
			'naam'  => 'Snelsollicitatie – Dendermonde',
			'regio' => 'Dendermonde',
		),
		'lier'        => array(
			'slug'  => 'elektricien-lier-antwerpen-zuidrand',
			'naam'  => 'Snelsollicitatie – Lier en Antwerpen-Zuidrand',
			'regio' => 'Lier en Antwerpen-Zuidrand',
		),
	);
}

/** De gekende keuzes, gelijk aan QUESTIONS in apply-form.js. Waarde => leesbare tekst. */
function zwijsen_snel_keuzes() {
	return array(
		'werkervaring' => array(
			'1-2'  => '1-2 jaar',
			'3-4'  => '3-4 jaar',
			'5-10' => '5-10 jaar',
			'10+'  => '+10 jaar',
			'geen' => 'Geen werkervaring',
		),
		'talen'        => array(
			'nederlands' => 'Nederlands',
			'frans'      => 'Frans',
			'engels'     => 'Engels',
			'andere'     => 'Een andere taal',
		),
		'rijbewijs'    => array(
			'b'   => 'Rijbewijs B',
			'be'  => 'Rijbewijs BE',
			'ce'  => 'Rijbewijs C(E)',
			'nee' => 'Nee',
		),
	);
}

function zwijsen_snel_landcodes() {
	return array( 'BE' => '+32', 'NL' => '+31', 'FR' => '+33', 'DE' => '+49', 'LU' => '+352', 'PL' => '+48', 'PT' => '+351', 'RO' => '+40' );
}

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'zwijsen/v1',
			'/sollicitatie',
			array(
				'methods'             => 'POST',
				'callback'            => 'zwijsen_snel_ontvang',
				'permission_callback' => '__return_true',   // publiek formulier
			)
		);
	}
);

function zwijsen_snel_fout( $tekst, $status = 400 ) {
	return new WP_REST_Response( array( 'ok' => false, 'message' => $tekst ), $status );
}

/** Eén regel tekst: geen besturingstekens, geen HTML, ingekort. */
function zwijsen_snel_regel( $v, $max ) {
	if ( ! is_string( $v ) ) {
		return '';
	}
	$v = wp_strip_all_tags( $v, true );
	$v = preg_replace( '/[\x00-\x1F\x7F]+/u', ' ', $v );
	$v = trim( preg_replace( '/\s+/u', ' ', (string) $v ) );
	return mb_substr( $v, 0, $max );
}

function zwijsen_snel_ip() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : '';
	return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '0.0.0.0';
}

/**
 * @param WP_REST_Request $req
 */
function zwijsen_snel_ontvang( $req ) {
	$algemeen = 'Versturen lukte niet. Probeer opnieuw of bel ons op ' . ZWIJSEN_SNEL_TELEFOON . '.';

	// --- Vorm van het verzoek ------------------------------------------------
	if ( strlen( (string) $req->get_body() ) > ZWIJSEN_SNEL_MAX_BYTES ) {
		return zwijsen_snel_fout( $algemeen, 413 );
	}
	$ctype = $req->get_content_type();
	if ( ! empty( $_FILES ) || ! is_array( $ctype ) || 'application/json' !== strtolower( (string) $ctype['value'] ) ) {
		return zwijsen_snel_fout( $algemeen, 415 );
	}
	$in = $req->get_json_params();
	if ( ! is_array( $in ) ) {
		return zwijsen_snel_fout( $algemeen );
	}

	// --- Honeypot: doe alsof het gelukt is, bewaar en mail niets ---------------
	if ( isset( $in['company_website'] ) && '' !== trim( (string) $in['company_website'] ) ) {
		return new WP_REST_Response( array( 'ok' => true ), 200 );
	}

	// --- Snelheidsgrens per IP -------------------------------------------------
	$ip    = zwijsen_snel_ip();
	$sleut = 'zwsnel_' . md5( $ip );
	$teller = get_transient( $sleut );
	if ( is_array( $teller ) && $teller['n'] >= ZWIJSEN_SNEL_LIMIET && $teller['t'] > time() ) {
		return zwijsen_snel_fout( 'Je hebt al enkele keren verstuurd. Probeer het over een paar minuten opnieuw of bel ons op ' . ZWIJSEN_SNEL_TELEFOON . '.', 429 );
	}
	if ( ! is_array( $teller ) || $teller['t'] <= time() ) {
		$teller = array( 'n' => 0, 't' => time() + ZWIJSEN_SNEL_VENSTER );
	}
	$teller['n']++;
	set_transient( $sleut, $teller, max( 1, $teller['t'] - time() ) );

	// --- Velden ----------------------------------------------------------------
	$paginas = zwijsen_snel_paginas();
	$sleutel = isset( $in['formulier'] ) ? (string) $in['formulier'] : '';
	if ( ! isset( $paginas[ $sleutel ] ) ) {
		return zwijsen_snel_fout( $algemeen );
	}
	$cfg    = $paginas[ $sleutel ];
	$keuzes = zwijsen_snel_keuzes();

	$naam      = zwijsen_snel_regel( $in['naam'] ?? '', 100 );
	$email     = sanitize_email( zwijsen_snel_regel( $in['email'] ?? '', 120 ) );
	$tel       = zwijsen_snel_regel( $in['telefoon'] ?? '', 30 );
	$landcodes = zwijsen_snel_landcodes();
	$landcode  = isset( $in['landcode'], $landcodes[ $in['landcode'] ] ) ? (string) $in['landcode'] : 'BE';
	$woonplaats = zwijsen_snel_regel( $in['woonplaats'] ?? '', 80 );

	$fouten = array();
	if ( '' === $naam ) {
		$fouten[] = 'je naam';
	}
	if ( strlen( preg_replace( '/\D/', '', $tel ) ) < 8 || ! preg_match( '/^[0-9 +().\/-]+$/', $tel ) ) {
		$fouten[] = 'een geldig telefoonnummer';
	}
	if ( ! $email || ! is_email( $email ) ) {
		$fouten[] = 'een geldig e-mailadres';
	}

	$werk = isset( $in['werkervaring'] ) && is_string( $in['werkervaring'] ) && isset( $keuzes['werkervaring'][ $in['werkervaring'] ] ) ? $in['werkervaring'] : '';
	$rij  = isset( $in['rijbewijs'] ) && is_string( $in['rijbewijs'] ) && isset( $keuzes['rijbewijs'][ $in['rijbewijs'] ] ) ? $in['rijbewijs'] : '';
	$talen = array();
	$talen_ok = isset( $in['talen'] ) && is_array( $in['talen'] ) && count( $in['talen'] ) > 0 && count( $in['talen'] ) <= 4;
	if ( $talen_ok ) {
		foreach ( $in['talen'] as $t ) {
			if ( ! is_string( $t ) || ! isset( $keuzes['talen'][ $t ] ) ) {
				$talen_ok = false;
				break;
			}
			$talen[ $t ] = true;
		}
	}
	$andere = zwijsen_snel_regel( $in['talen_andere'] ?? '', 60 );
	if ( $talen_ok && isset( $talen['andere'] ) && '' === $andere ) {
		$talen_ok = false;
	}
	if ( '' === $werk || '' === $rij || ! $talen_ok || '' === $woonplaats ) {
		$fouten[] = 'een antwoord op elke vraag';
	}

	if ( $fouten ) {
		return zwijsen_snel_fout( 'Even nakijken: we missen nog ' . implode( ', ', $fouten ) . '. Lukt het niet? Bel ons op ' . ZWIJSEN_SNEL_TELEFOON . '.', 422 );
	}

	$talen_tekst = array();
	foreach ( array_keys( $keuzes['talen'] ) as $t ) {   // vaste volgorde
		if ( isset( $talen[ $t ] ) ) {
			$talen_tekst[] = 'andere' === $t ? 'Andere: ' . $andere : $keuzes['talen'][ $t ];
		}
	}

	$pagina   = get_page_by_path( $cfg['slug'], OBJECT, 'page' );
	$post_id  = $pagina ? (int) $pagina->ID : 0;
	$url      = $post_id ? get_permalink( $post_id ) : home_url( '/' . $cfg['slug'] . '/' );
	$bron     = zwijsen_snel_regel( $in['bron'] ?? '', 300 );
	$bron     = '' === $bron ? '' : ( '?' === $bron[0] ? $bron : '?' . $bron );
	$bron_lees = '' === $bron ? '(geen)' : urldecode( $bron );
	$tijdstip = zwijsen_snel_regel( $in['tijdstip'] ?? '', 40 );
	$ua       = zwijsen_snel_regel( isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) $_SERVER['HTTP_USER_AGENT'] : '', 400 );

	// Leesbare velden, in de volgorde waarin ze in de beheerpagina en de mail staan.
	$velden = array(
		array( 'naam', 'text', 'Naam', $naam ),
		array( 'email', 'email', 'E-mailadres', $email ),
		array( 'telefoon', 'tel', 'Telefoon', $tel . ' (' . $landcode . ' ' . $landcodes[ $landcode ] . ')' ),
		array( 'werkervaring', 'text', 'Hoeveel relevante werkervaring heb je?', $keuzes['werkervaring'][ $werk ] ),
		array( 'talen', 'text', 'In welke talen kun je overleggen met collega’s en veiligheidsinstructies begrijpen?', implode( ', ', $talen_tekst ) ),
		array( 'rijbewijs', 'text', 'Heb je een rijbewijs?', $keuzes['rijbewijs'][ $rij ] ),
		array( 'woonplaats', 'text', 'Waar woon je?', $woonplaats ),
		array( 'regio', 'text', 'Regio (pagina)', $cfg['regio'] ),
		array( 'vacature', 'text', 'Vacature', 'Elektricien – technieker algemene elektriciteit' ),
		array( 'pagina', 'url', 'Pagina', $url ),
		array( 'bron', 'text', 'Bron / UTM', $bron_lees ),
		array( 'tijdstip', 'text', 'Tijdstip (browser)', $tijdstip ),
	);

	// --- Opslaan bij Elementor → Inzendingen -----------------------------------
	$submission_id = zwijsen_snel_bewaar( $post_id, $cfg, $url, $velden, $ip, $ua );

	// --- Mails -----------------------------------------------------------------
	$voornaam = preg_split( '/\s+/u', $naam )[0];
	$hdr_naam = trim( str_replace( array( '"', '<', '>', ',', ';' ), '', $naam ) );

	$ok_personeel = zwijsen_snel_mail(
		ZWIJSEN_SNEL_PERSONEEL,
		'Nieuwe sollicitatie elektricien – ' . $cfg['regio'] . ' – ' . $naam,
		zwijsen_snel_html_melding( $velden, $cfg ),
		zwijsen_snel_tekst_melding( $velden, $cfg ),
		'"' . $hdr_naam . '" <' . $email . '>'
	);
	$ok_bevestiging = zwijsen_snel_mail(
		'"' . $hdr_naam . '" <' . $email . '>',
		'We hebben je sollicitatie goed ontvangen',
		zwijsen_snel_html_bevestiging( $voornaam ),
		zwijsen_snel_tekst_bevestiging( $voornaam ),
		'Electro Zwijsen <' . ZWIJSEN_SNEL_PERSONEEL . '>'
	);

	if ( $submission_id ) {
		zwijsen_snel_log_actie( $submission_id, 'email', 'Melding aan personeel', $ok_personeel );
		zwijsen_snel_log_actie( $submission_id, 'email2', 'Bevestiging aan sollicitant', $ok_bevestiging );
	}

	if ( ! $submission_id && ! $ok_personeel ) {
		// Niet bewaard en niet gemeld: de sollicitant moet het weten.
		error_log( 'zwijsen-snelsollicitatie: opslaan en melding mislukt voor ' . $cfg['naam'] );
		return zwijsen_snel_fout( $algemeen, 500 );
	}
	if ( ! $submission_id || ! $ok_personeel || ! $ok_bevestiging ) {
		error_log( sprintf( 'zwijsen-snelsollicitatie: deels mislukt (inzending %d, melding %d, bevestiging %d)', $submission_id, $ok_personeel, $ok_bevestiging ) );
	}

	return new WP_REST_Response( array( 'ok' => true ), 200 );
}

function zwijsen_snel_bewaar( $post_id, $cfg, $url, $velden, $ip, $ua ) {
	$query_class = '\ElementorPro\Modules\Forms\Submissions\Database\Query';
	$repo_class  = '\ElementorPro\Modules\Forms\Submissions\Database\Repositories\Form_Snapshot_Repository';
	if ( ! $post_id || ! class_exists( $query_class ) ) {
		error_log( 'zwijsen-snelsollicitatie: Elementor Pro-inzendingen niet beschikbaar of pagina niet gevonden' );
		return 0;
	}

	$fields = array();
	foreach ( $velden as $v ) {
		$fields[ $v[0] ] = array( 'id' => $v[0], 'type' => $v[1], 'title' => $v[2], 'value' => $v[3], 'raw_value' => $v[3] );
	}

	$id = $query_class::get_instance()->add_submission(
		array(
			'main_meta_id'            => 0,
			'post_id'                 => $post_id,
			'referer'                 => $url,
			'referer_title'           => get_the_title( $post_id ),
			'element_id'              => ZWIJSEN_SNEL_ELEMENT_ID,
			'form_name'               => mb_substr( $cfg['naam'], 0, 60 ),
			'campaign_id'             => 0,
			'user_id'                 => 0,
			'user_ip'                 => $ip,
			'user_agent'              => $ua,
			'actions_count'           => 2,
			'actions_succeeded_count' => 0,
			'meta'                    => wp_json_encode( array( 'edit_post_id' => (string) $post_id, 'bron' => 'zwijsen-snelsollicitatie ' . ZWIJSEN_SNEL_VERSIE ) ),
		),
		$fields
	);

	// Labels voor de beheerpagina en de export: zelfde mechanisme als een Elementor-formulier.
	if ( $id && class_exists( $repo_class ) ) {
		$repo_class::instance()->create_or_update(
			$post_id,
			ZWIJSEN_SNEL_ELEMENT_ID,
			array(
				'name'   => $cfg['naam'],
				'fields' => array_map(
					function ( $v ) {
						return array( 'id' => $v[0], 'type' => $v[1], 'label' => $v[2] );
					},
					$velden
				),
			)
		);
	}
	return (int) $id;
}

function zwijsen_snel_log_actie( $submission_id, $naam, $label, $gelukt ) {
	$q = '\ElementorPro\Modules\Forms\Submissions\Database\Query';
	$actie = new class( $naam, $label ) {
		private $n;
		private $l;
		public function __construct( $n, $l ) { $this->n = $n; $this->l = $l; }
		public function get_name() { return $this->n; }
		public function get_label() { return $this->l; }
	};
	$q::get_instance()->add_action_log( $submission_id, $actie, $gelukt ? $q::ACTIONS_LOG_STATUS_SUCCESS : $q::ACTIONS_LOG_STATUS_FAILED, $gelukt ? null : 'wp_mail gaf false' );
	if ( $gelukt ) {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( "UPDATE {$q::get_instance()->get_table_submissions()} SET actions_succeeded_count = actions_succeeded_count + 1 WHERE id = %d", $submission_id ) );
	}
}

/** HTML-mail met tekstversie (AltBody). Microsoft Graph verstuurt alleen de HTML; andere routes ook de tekst. */
function zwijsen_snel_mail( $aan, $onderwerp, $html, $tekst, $reply_to ) {
	$alt = function ( $phpmailer ) use ( $tekst ) {
		$phpmailer->AltBody = $tekst;
	};
	add_action( 'phpmailer_init', $alt );
	$ok = wp_mail(
		$aan,
		$onderwerp,
		$html,
		array(
			'Content-Type: text/html; charset=UTF-8',
			'From: Electro Zwijsen <' . ZWIJSEN_SNEL_AFZENDER . '>',
			'Reply-To: ' . $reply_to,
		)
	);
	remove_action( 'phpmailer_init', $alt );
	return (bool) $ok;
}

function zwijsen_snel_html_melding( $velden, $cfg ) {
	$rijen = '';
	foreach ( $velden as $v ) {
		$waarde = 'pagina' === $v[0] ? '<a href="' . esc_url( $v[3] ) . '">' . esc_html( $v[3] ) . '</a>'
			: ( 'email' === $v[0] ? '<a href="mailto:' . esc_attr( $v[3] ) . '">' . esc_html( $v[3] ) . '</a>' : esc_html( $v[3] ) );
		$rijen .= '<tr><td style="padding:6px 12px 6px 0;vertical-align:top;color:#555;width:42%">' . esc_html( $v[2] ) . '</td>'
			. '<td style="padding:6px 0;vertical-align:top;font-weight:bold">' . $waarde . '</td></tr>';
	}
	return '<!doctype html><html lang="nl"><body style="font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.5;color:#1a1a1a">'
		. '<p>Nieuwe sollicitatie via de regiopagina <strong>' . esc_html( $cfg['regio'] ) . '</strong> (snelsollicitatie, geen cv).</p>'
		. '<table style="border-collapse:collapse;max-width:640px">' . $rijen . '</table>'
		. '<p style="color:#555">Antwoorden op deze mail gaat rechtstreeks naar de sollicitant. De inzending staat ook in WordPress onder Elementor → Inzendingen.</p>'
		. '</body></html>';
}

function zwijsen_snel_tekst_melding( $velden, $cfg ) {
	$t = 'Nieuwe sollicitatie via de regiopagina ' . $cfg['regio'] . " (snelsollicitatie, geen cv).\n\n";
	foreach ( $velden as $v ) {
		$t .= $v[2] . "\n  " . $v[3] . "\n";
	}
	return $t . "\nAntwoorden op deze mail gaat rechtstreeks naar de sollicitant. De inzending staat ook in WordPress onder Elementor → Inzendingen.\n";
}

/* ---------------------------------------------------------------------------
 * Bevestiging aan de sollicitant. Tekst ter goedkeuring van Katrien; zie
 * elementor/VERSLAG-formulier.md. Belooft niets meer dan het bedankscherm.
 * ------------------------------------------------------------------------- */

function zwijsen_snel_tekst_bevestiging( $voornaam ) {
	return 'Dag ' . $voornaam . ",\n\n"
		. "Bedankt voor je sollicitatie als elektricien bij Electro Zwijsen.\n"
		. "We hebben je gegevens goed ontvangen. We nemen gauw contact met je op.\n\n"
		. 'Heb je intussen een vraag? Bel ons op ' . ZWIJSEN_SNEL_TELEFOON . " of antwoord gewoon op deze mail.\n\n"
		. "Met vriendelijke groeten,\n"
		. "Het team van Electro Zwijsen\n";
}

function zwijsen_snel_html_bevestiging( $voornaam ) {
	return '<!doctype html><html lang="nl"><body style="font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:1.55;color:#1a1a1a">'
		. '<p>Dag ' . esc_html( $voornaam ) . ',</p>'
		. '<p>Bedankt voor je sollicitatie als elektricien bij Electro Zwijsen.<br>'
		. 'We hebben je gegevens goed ontvangen. We nemen gauw contact met je op.</p>'
		. '<p>Heb je intussen een vraag? Bel ons op <a href="tel:+3215305305">' . ZWIJSEN_SNEL_TELEFOON . '</a> of antwoord gewoon op deze mail.</p>'
		. '<p>Met vriendelijke groeten,<br>Het team van Electro Zwijsen</p>'
		. '</body></html>';
}

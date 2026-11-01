<?php
/**
 * Plugin Name: Zwijsen — snelsollicitatie regiopagina's
 * Description: Ontvangt het korte sollicitatieformulier (4 vragen + contact) van de regiopagina's elektricien. Bewaart elke inzending bij Elementor → Inzendingen, mailt personeel@zwijsen.be en stuurt de sollicitant een bevestiging. Niets gaat naar Meta of een andere derde partij.
 * Version:     1.1.0
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
 *
 * Sinds 1.1.0 ook: een beheerpagina "Sollicitaties" (rapport zonder
 * persoonsgegevens) met CSV-download, en de rol "Rapportage sollicitaties"
 * (zwijsen_rapport) die alleen die pagina en het eigen profiel ziet.
 * Zie elementor/VERSLAG-rapportpagina.md.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const ZWIJSEN_SNEL_VERSIE     = '1.1.0';
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

/* ===========================================================================
 * Rapport "Sollicitaties" (sinds 1.1.0)
 *
 * Een beheerpagina met de snelsollicitaties ZONDER persoonsgegevens, voor de
 * marketeer (vergelijken met Meta-advertenties) en HR. Plus een CSV-download.
 *
 * Privacy, zo gebouwd dat het niet kan mislopen:
 *   - Uit de databank worden alleen de sleutels in zwijsen_snel_rapport_sleutels()
 *     gelezen. naam, email en telefoon worden dus nooit opgehaald, ook niet per
 *     ongeluk; user_ip, user_agent en referer evenmin.
 *   - Van "bron" (de querystring van de pagina) blijven alleen de vijf utm_-velden
 *     over. fbclid, gclid en alle andere parameters vallen weg.
 *   - Er is geen REST-route voor deze gegevens. De CSV loopt via admin-post.php,
 *     met nonce en capability.
 *
 * Toegang: capability zwijsen_bekijk_sollicitaties. Die hebben de beheerders en
 * de rol zwijsen_rapport ("Rapportage sollicitaties"), die verder alleen "read"
 * heeft. Wie alleen die rol heeft, ziet uitsluitend deze pagina en het eigen
 * profiel (wachtwoord wijzigen); elk ander beheerscherm stuurt terug hierheen.
 * De rol krijgt nooit beheerdersrechten, dus de automatische uitschakeling van
 * Hack Guard is niet van toepassing.
 * ======================================================================== */

const ZWIJSEN_SNEL_CAP        = 'zwijsen_bekijk_sollicitaties';
const ZWIJSEN_SNEL_ROL        = 'zwijsen_rapport';
const ZWIJSEN_SNEL_ROL_NAAM   = 'Rapportage sollicitaties';
const ZWIJSEN_SNEL_PAGINA     = 'zwijsen-sollicitaties';
const ZWIJSEN_SNEL_CSV_ACTIE  = 'zwijsen_sollicitaties_csv';
const ZWIJSEN_SNEL_TIJDZONE   = 'Europe/Brussels';
const ZWIJSEN_SNEL_MAX_RIJEN  = 1000;   // op het scherm; de CSV heeft ze allemaal

/** De enige velden die het rapport uit e_submissions_values leest. */
function zwijsen_snel_rapport_sleutels() {
	return array( 'werkervaring', 'talen', 'rijbewijs', 'woonplaats', 'regio', 'bron' );
}

function zwijsen_snel_utm_sleutels() {
	return array( 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term' );
}

/* --- Rol en capability (idempotent) --------------------------------------- */

add_action(
	'init',
	function () {
		$caps = array(
			'read'            => true,
			ZWIJSEN_SNEL_CAP  => true,
		);
		$rol = get_role( ZWIJSEN_SNEL_ROL );
		if ( ! $rol ) {
			add_role( ZWIJSEN_SNEL_ROL, ZWIJSEN_SNEL_ROL_NAAM, $caps );
		} else {
			// Precies deze twee, niets meer: iets dat erbij gekomen is, gaat eraf.
			foreach ( array_keys( (array) $rol->capabilities ) as $cap ) {
				if ( ! isset( $caps[ $cap ] ) ) {
					$rol->remove_cap( $cap );
				}
			}
			foreach ( array_keys( $caps ) as $cap ) {
				if ( empty( $rol->capabilities[ $cap ] ) ) {
					$rol->add_cap( $cap );
				}
			}
		}
		$admin = get_role( 'administrator' );
		if ( $admin && empty( $admin->capabilities[ ZWIJSEN_SNEL_CAP ] ) ) {
			$admin->add_cap( ZWIJSEN_SNEL_CAP );
		}
	},
	5
);

/** Heeft deze gebruiker uitsluitend de rapportrol? Dan krijgt hij de afgeschermde beheeromgeving. */
function zwijsen_snel_alleen_rapport( $user = null ) {
	$user = $user instanceof WP_User ? $user : wp_get_current_user();
	return $user instanceof WP_User && $user->exists()
		&& array( ZWIJSEN_SNEL_ROL ) === array_values( (array) $user->roles );
}

function zwijsen_snel_rapport_url( $args = array() ) {
	return add_query_arg( array_merge( array( 'page' => ZWIJSEN_SNEL_PAGINA ), $args ), admin_url( 'admin.php' ) );
}

/* --- Afgeschermde beheeromgeving voor de rapportrol ------------------------ */

add_filter(
	'login_redirect',
	function ( $redirect_to, $requested, $user ) {
		return ( $user instanceof WP_User && zwijsen_snel_alleen_rapport( $user ) ) ? zwijsen_snel_rapport_url() : $redirect_to;
	},
	99,
	3
);

/** Elk beheerscherm behalve het rapport en het eigen profiel stuurt terug naar het rapport. */
add_action(
	'admin_init',
	function () {
		if ( wp_doing_ajax() || ! zwijsen_snel_alleen_rapport() ) {
			return;
		}
		global $pagenow;
		$page   = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : '';

		$toegelaten = ( 'profile.php' === $pagenow && ( '' === $page || 'wfls' === $page ) )
			|| ( 'admin.php' === $pagenow && in_array( $page, array( ZWIJSEN_SNEL_PAGINA, 'wfls' ), true ) )   // WFLS = tweestapsverificatie van Wordfence
			|| ( 'admin-post.php' === $pagenow && ZWIJSEN_SNEL_CSV_ACTIE === $action );

		if ( ! $toegelaten ) {
			wp_safe_redirect( zwijsen_snel_rapport_url() );
			exit;
		}
	},
	1
);

/**
 * Schermen met een menu-item dat deze rol niet mag zien, stopt WordPress al in
 * menu.php met een 403, nog vóór admin_init. Ook die sturen we naar het rapport.
 */
add_action(
	'admin_page_access_denied',
	function () {
		if ( zwijsen_snel_alleen_rapport() ) {
			wp_safe_redirect( zwijsen_snel_rapport_url() );
			exit;
		}
	}
);

add_action(
	'admin_menu',
	function () {
		if ( ! zwijsen_snel_alleen_rapport() ) {
			return;
		}
		global $menu;
		foreach ( (array) $menu as $item ) {
			$slug = isset( $item[2] ) ? (string) $item[2] : '';
			if ( ! in_array( $slug, array( ZWIJSEN_SNEL_PAGINA, 'profile.php', 'WFLS' ), true ) ) {
				remove_menu_page( $slug );
			}
		}
	},
	9999
);

add_action(
	'wp_before_admin_bar_render',
	function () {
		if ( ! zwijsen_snel_alleen_rapport() ) {
			return;
		}
		global $wp_admin_bar;
		if ( ! $wp_admin_bar instanceof WP_Admin_Bar ) {
			return;
		}
		$houden = array( 'top-secondary', 'my-account', 'user-actions', 'user-info', 'edit-profile', 'logout', 'menu-toggle' );
		foreach ( array_keys( (array) $wp_admin_bar->get_nodes() ) as $id ) {
			if ( ! in_array( $id, $houden, true ) ) {
				$wp_admin_bar->remove_node( $id );
			}
		}
	},
	9999
);

add_filter(
	'show_admin_bar',
	function ( $tonen ) {
		return zwijsen_snel_alleen_rapport() ? false : $tonen;
	},
	99
);

/** Meldingen van andere plugins horen niet op dit scherm voor de rapportrol. */
add_action(
	'in_admin_header',
	function () {
		if ( zwijsen_snel_alleen_rapport() ) {
			remove_all_actions( 'admin_notices' );
			remove_all_actions( 'all_admin_notices' );
		}
	},
	9999
);

/** Applicatiewachtwoorden omzeilen tweestapsverificatie; deze rol heeft ze niet nodig. */
add_filter(
	'wp_is_application_passwords_available_for_user',
	function ( $beschikbaar, $user ) {
		return zwijsen_snel_alleen_rapport( $user ) ? false : $beschikbaar;
	},
	10,
	2
);

/* --- Gegevens ---------------------------------------------------------------- */

/** Datum (J-M-D) uit de invoer, of '' als ze ongeldig is. */
function zwijsen_snel_datum( $v ) {
	$v = is_string( $v ) ? trim( $v ) : '';
	if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $v, $m ) || ! checkdate( (int) $m[2], (int) $m[3], (int) $m[1] ) ) {
		return '';
	}
	return $v;
}

function zwijsen_snel_regios() {
	return array_values( wp_list_pluck( zwijsen_snel_paginas(), 'regio' ) );
}

/** De filter uit $_GET (of een array): van, tot (beide inclusief, Brusselse tijd) en regio. */
function zwijsen_snel_filter( $bron = null ) {
	$bron  = is_array( $bron ) ? $bron : wp_unslash( $_GET );
	$van   = zwijsen_snel_datum( $bron['van'] ?? '' );
	$tot   = zwijsen_snel_datum( $bron['tot'] ?? '' );
	if ( '' !== $van && '' !== $tot && $van > $tot ) {
		list( $van, $tot ) = array( $tot, $van );
	}
	$regio = isset( $bron['regio'] ) && is_string( $bron['regio'] ) && in_array( $bron['regio'], zwijsen_snel_regios(), true ) ? $bron['regio'] : '';
	return array( 'van' => $van, 'tot' => $tot, 'regio' => $regio );
}

/** "?utm_source=facebook&fbclid=..." -> alleen de vijf utm_-velden. Al het andere valt weg. */
function zwijsen_snel_utm( $bron ) {
	$uit = array_fill_keys( zwijsen_snel_utm_sleutels(), '' );
	$bron = is_string( $bron ) ? trim( $bron ) : '';
	if ( '' === $bron || '(geen)' === $bron ) {
		return $uit;
	}
	foreach ( explode( '&', ltrim( $bron, '?' ) ) as $paar ) {
		$kv  = explode( '=', $paar, 2 );
		$key = strtolower( trim( $kv[0] ) );
		if ( array_key_exists( $key, $uit ) && '' === $uit[ $key ] ) {
			$uit[ $key ] = zwijsen_snel_regel( $kv[1] ?? '', 150 );
		}
	}
	return $uit;
}

/**
 * De rijen voor het rapport, nieuwste eerst. Elke rij bevat uitsluitend:
 * datum, uur, regio, de vijf utm-velden, werkervaring, talen, rijbewijs, woonplaats.
 */
function zwijsen_snel_rapport_rijen( $filter ) {
	global $wpdb;
	$t_sub = $wpdb->prefix . 'e_submissions';
	$t_val = $wpdb->prefix . 'e_submissions_values';
	$tz    = new DateTimeZone( ZWIJSEN_SNEL_TIJDZONE );
	$utc   = new DateTimeZone( 'UTC' );

	$waar = array( 's.element_id = %s', "s.status <> 'trash'", 's.form_name LIKE %s' );
	$args = array( ZWIJSEN_SNEL_ELEMENT_ID, $wpdb->esc_like( 'Snelsollicitatie' ) . '%' );
	if ( '' !== $filter['van'] ) {
		$waar[] = 's.created_at_gmt >= %s';
		$args[] = ( new DateTimeImmutable( $filter['van'] . ' 00:00:00', $tz ) )->setTimezone( $utc )->format( 'Y-m-d H:i:s' );
	}
	if ( '' !== $filter['tot'] ) {
		$waar[] = 's.created_at_gmt < %s';
		$args[] = ( new DateTimeImmutable( $filter['tot'] . ' 00:00:00', $tz ) )->modify( '+1 day' )->setTimezone( $utc )->format( 'Y-m-d H:i:s' );
	}

	// Bewust geen user_ip, user_agent, referer of meta in de SELECT.
	$subs = $wpdb->get_results(
		$wpdb->prepare( "SELECT s.id, s.form_name, s.created_at_gmt FROM {$t_sub} s WHERE " . implode( ' AND ', $waar ) . ' ORDER BY s.created_at_gmt DESC, s.id DESC', $args ),
		ARRAY_A
	);
	if ( ! $subs ) {
		return array();
	}

	$waarden = array();
	$sleutels = zwijsen_snel_rapport_sleutels();
	foreach ( array_chunk( array_map( 'intval', wp_list_pluck( $subs, 'id' ) ), 500 ) as $ids ) {
		$ph = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$ks = implode( ',', array_fill( 0, count( $sleutels ), '%s' ) );
		$res = $wpdb->get_results(
			$wpdb->prepare( "SELECT submission_id, `key`, value FROM {$t_val} WHERE submission_id IN ({$ph}) AND `key` IN ({$ks})", array_merge( $ids, $sleutels ) ),
			ARRAY_A
		);
		foreach ( (array) $res as $r ) {
			$waarden[ (int) $r['submission_id'] ][ $r['key'] ] = (string) $r['value'];
		}
	}

	$per_naam = array();
	foreach ( zwijsen_snel_paginas() as $p ) {
		$per_naam[ $p['naam'] ] = $p['regio'];
	}

	$rijen = array();
	foreach ( $subs as $s ) {
		$v     = $waarden[ (int) $s['id'] ] ?? array();
		$regio = '' !== ( $v['regio'] ?? '' ) ? $v['regio'] : ( $per_naam[ $s['form_name'] ] ?? '' );
		if ( '' !== $filter['regio'] && $regio !== $filter['regio'] ) {
			continue;
		}
		$moment = ( new DateTimeImmutable( $s['created_at_gmt'], $utc ) )->setTimezone( $tz );
		$rijen[] = array_merge(
			array(
				'datum' => $moment->format( 'Y-m-d' ),
				'uur'   => $moment->format( 'H:i' ),
				'regio' => $regio,
			),
			zwijsen_snel_utm( $v['bron'] ?? '' ),
			array(
				'werkervaring' => $v['werkervaring'] ?? '',
				'talen'        => $v['talen'] ?? '',
				'rijbewijs'    => $v['rijbewijs'] ?? '',
				'woonplaats'   => $v['woonplaats'] ?? '',
			)
		);
	}
	return $rijen;
}

/** Kolommen van tabel en CSV: sleutel => Nederlandse kop. */
function zwijsen_snel_rapport_kolommen() {
	return array(
		'datum'        => 'Datum',
		'uur'          => 'Uur',
		'regio'        => 'Regio',
		'utm_source'   => 'Bron (utm_source)',
		'utm_medium'   => 'Medium (utm_medium)',
		'utm_campaign' => 'Campagne (utm_campaign)',
		'utm_content'  => 'Advertentie (utm_content)',
		'utm_term'     => 'Term (utm_term)',
		'werkervaring' => 'Werkervaring',
		'talen'        => 'Talen',
		'rijbewijs'    => 'Rijbewijs',
		'woonplaats'   => 'Woonplaats of postcode',
	);
}

function zwijsen_snel_tel( $rijen, $veld ) {
	$n = array();
	foreach ( $rijen as $r ) {
		$k       = '' !== $r[ $veld ] ? $r[ $veld ] : '(geen)';
		$n[ $k ] = ( $n[ $k ] ?? 0 ) + 1;
	}
	arsort( $n );
	return $n;
}

/* --- De beheerpagina -------------------------------------------------------- */

add_action(
	'admin_menu',
	function () {
		add_menu_page( 'Sollicitaties', 'Sollicitaties', ZWIJSEN_SNEL_CAP, ZWIJSEN_SNEL_PAGINA, 'zwijsen_snel_rapport_pagina', 'dashicons-groups', 26 );
	}
);

function zwijsen_snel_rapport_pagina() {
	if ( ! current_user_can( ZWIJSEN_SNEL_CAP ) ) {
		wp_die( 'Geen toegang.', 403 );
	}
	$f     = zwijsen_snel_filter();
	$rijen = zwijsen_snel_rapport_rijen( $f );
	$kol   = zwijsen_snel_rapport_kolommen();

	// utm_medium en utm_term alleen tonen als ze ergens ingevuld zijn (de CSV heeft ze altijd).
	foreach ( array( 'utm_medium', 'utm_term' ) as $optioneel ) {
		if ( ! array_filter( wp_list_pluck( $rijen, $optioneel ) ) ) {
			unset( $kol[ $optioneel ] );
		}
	}

	$csv = wp_nonce_url(
		add_query_arg( array_merge( array( 'action' => ZWIJSEN_SNEL_CSV_ACTIE ), array_filter( $f ) ), admin_url( 'admin-post.php' ) ),
		ZWIJSEN_SNEL_CSV_ACTIE
	);
	?>
	<div class="wrap zwijsen-sollicitaties">
		<h1 class="wp-heading-inline">Sollicitaties</h1>
		<a href="<?php echo esc_url( $csv ); ?>" class="page-title-action">Download CSV</a>
		<hr class="wp-header-end">
		<p>Snelsollicitaties van de regiopagina’s, nieuwste eerst. Datum en uur in Belgische tijd.
			Naam, e-mailadres en telefoon staan hier bewust niet in; die staan alleen in de mail aan personeel@zwijsen.be.</p>

		<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" style="margin:1em 0;display:flex;flex-wrap:wrap;gap:.75em;align-items:flex-end">
			<input type="hidden" name="page" value="<?php echo esc_attr( ZWIJSEN_SNEL_PAGINA ); ?>">
			<label>Van<br><input type="date" name="van" value="<?php echo esc_attr( $f['van'] ); ?>"></label>
			<label>Tot en met<br><input type="date" name="tot" value="<?php echo esc_attr( $f['tot'] ); ?>"></label>
			<label>Regio<br>
				<select name="regio">
					<option value="">Alle regio’s</option>
					<?php foreach ( zwijsen_snel_regios() as $r ) : ?>
						<option value="<?php echo esc_attr( $r ); ?>" <?php selected( $f['regio'], $r ); ?>><?php echo esc_html( $r ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<button type="submit" class="button">Filteren</button>
			<a class="button-link" href="<?php echo esc_url( zwijsen_snel_rapport_url() ); ?>">Alles tonen</a>
		</form>

		<p><strong><?php echo esc_html( count( $rijen ) ); ?></strong> <?php echo 1 === count( $rijen ) ? 'sollicitatie' : 'sollicitaties'; ?>
			<?php echo esc_html( zwijsen_snel_periode_tekst( $f ) ); ?>.</p>

		<?php if ( $rijen ) : ?>
			<div style="display:flex;flex-wrap:wrap;gap:2em;margin-bottom:1.5em">
				<?php foreach ( array( 'regio' => 'Per regio', 'utm_content' => 'Per advertentie (utm_content)' ) as $veld => $titel ) : ?>
					<table class="widefat striped" style="width:auto;min-width:18em">
						<thead><tr><th><?php echo esc_html( $titel ); ?></th><th style="text-align:right">Aantal</th></tr></thead>
						<tbody>
						<?php foreach ( zwijsen_snel_tel( $rijen, $veld ) as $waarde => $aantal ) : ?>
							<tr><td><?php echo esc_html( $waarde ); ?></td><td style="text-align:right"><?php echo esc_html( $aantal ); ?></td></tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				<?php endforeach; ?>
			</div>

			<table class="widefat striped">
				<thead><tr>
					<?php foreach ( $kol as $kop ) : ?>
						<th scope="col"><?php echo esc_html( $kop ); ?></th>
					<?php endforeach; ?>
				</tr></thead>
				<tbody>
				<?php foreach ( array_slice( $rijen, 0, ZWIJSEN_SNEL_MAX_RIJEN ) as $rij ) : ?>
					<tr>
						<?php foreach ( array_keys( $kol ) as $k ) : ?>
							<td><?php echo esc_html( $rij[ $k ] ); ?></td>
						<?php endforeach; ?>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php if ( count( $rijen ) > ZWIJSEN_SNEL_MAX_RIJEN ) : ?>
				<p>Op het scherm staan de nieuwste <?php echo esc_html( ZWIJSEN_SNEL_MAX_RIJEN ); ?>. De CSV bevat ze allemaal.</p>
			<?php endif; ?>
		<?php else : ?>
			<p>Geen sollicitaties voor deze filter.</p>
		<?php endif; ?>
	</div>
	<?php
}

function zwijsen_snel_periode_tekst( $f ) {
	$d = function ( $v ) {
		return DateTimeImmutable::createFromFormat( '!Y-m-d', $v )->format( 'd-m-Y' );
	};
	$t = '';
	if ( '' !== $f['van'] && '' !== $f['tot'] ) {
		$t = 'van ' . $d( $f['van'] ) . ' tot en met ' . $d( $f['tot'] );
	} elseif ( '' !== $f['van'] ) {
		$t = 'vanaf ' . $d( $f['van'] );
	} elseif ( '' !== $f['tot'] ) {
		$t = 'tot en met ' . $d( $f['tot'] );
	} else {
		$t = 'in totaal';
	}
	return $t . ( '' !== $f['regio'] ? ', regio ' . $f['regio'] : '' );
}

/* --- CSV ---------------------------------------------------------------------- */

/** Een cel die met = + - @ begint, zou Excel als formule uitvoeren (utm-waarden komen van buiten). */
function zwijsen_snel_csv_cel( $v ) {
	$v = (string) $v;
	return ( '' !== $v && false !== strpbrk( $v[0], "=+-@\t\r" ) ) ? "'" . $v : $v;
}

function zwijsen_snel_csv_bestandsnaam( $f ) {
	$vandaag = ( new DateTimeImmutable( 'now', new DateTimeZone( ZWIJSEN_SNEL_TIJDZONE ) ) )->format( 'Y-m-d' );
	$naam    = 'sollicitaties-' . ( '' !== $f['van'] ? $f['van'] : 'begin' ) . '-tot-' . ( '' !== $f['tot'] ? $f['tot'] : $vandaag );
	return $naam . ( '' !== $f['regio'] ? '-' . sanitize_title( $f['regio'] ) : '' ) . '.csv';
}

/** Schrijft de CSV naar $uit (een stream). Los van de headers, zodat het te testen is. */
function zwijsen_snel_csv_schrijf( $uit, $rijen ) {
	fwrite( $uit, "\xEF\xBB\xBF" );
	$kol = zwijsen_snel_rapport_kolommen();
	fputcsv( $uit, array_values( $kol ), ',', '"', '' );
	foreach ( $rijen as $rij ) {
		$regel = array();
		foreach ( array_keys( $kol ) as $k ) {
			$regel[] = zwijsen_snel_csv_cel( $rij[ $k ] );
		}
		fputcsv( $uit, $regel, ',', '"', '' );
	}
}

add_action(
	'admin_post_' . ZWIJSEN_SNEL_CSV_ACTIE,
	function () {
		if ( ! current_user_can( ZWIJSEN_SNEL_CAP ) ) {
			wp_die( 'Geen toegang.', 403 );
		}
		check_admin_referer( ZWIJSEN_SNEL_CSV_ACTIE );

		$f = zwijsen_snel_filter();
		nocache_headers();
		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="' . zwijsen_snel_csv_bestandsnaam( $f ) . '"' );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'X-Robots-Tag: noindex' );
		$uit = fopen( 'php://output', 'w' );
		zwijsen_snel_csv_schrijf( $uit, zwijsen_snel_rapport_rijen( $f ) );
		fclose( $uit );
		exit;
	}
);

/** Niet ingelogd: uitdrukkelijk weigeren in plaats van een lege 200. */
add_action(
	'admin_post_nopriv_' . ZWIJSEN_SNEL_CSV_ACTIE,
	function () {
		wp_die( 'Geen toegang.', 403 );
	}
);

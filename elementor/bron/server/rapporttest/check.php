<?php
$o = __DIR__ . '/out';
$pii = '/Testpersoon|rapport\.testpersoon|testpersoon@|0470 99|99 88 77|192\.0\.2\.|UA-GEHEIM|rapporttest|IwAR_GEHEIM|GCLIDGEHEIM|fbclid|gclid/i';
foreach ( array( 'r_page', 'r_page_lier', 'r_page_van', 'r_page_tot', 'r_csv_all', 'r_csv_lier_nov' ) as $f ) {
	$b = file_get_contents( "$o/$f.body" );
	echo "$f: PII-treffers=" . preg_match_all( $pii, $b ) . "\n";
}
foreach ( array( 'r_page', 'r_page_lier', 'r_page_van', 'r_page_tot' ) as $f ) {
	$b = file_get_contents( "$o/$f.body" );
	preg_match( '#<strong>(\d+)</strong> sollicitatie#', $b, $m );
	preg_match_all( '#<tr>\s*<td>(\d{4}-\d\d-\d\d)</td>\s*<td>([\d:]+)</td>\s*<td>([^<]+)</td>#', $b, $r, PREG_SET_ORDER );
	echo "== $f telling={$m[1]} rijen=" . implode( ' | ', array_map( fn( $x ) => "$x[1] $x[2] $x[3]", $r ) ) . "\n";
}
// Menu en adminbalk in r_page
$b = file_get_contents( "$o/r_page.body" );
preg_match( '#<ul id="adminmenu">(.*?)</ul>\s*<div id="collapse-menu"#s', $b, $m );
preg_match_all( '#<div class=\'wp-menu-name\'>([^<]*)#', $m[1] ?? $b, $mm );
echo "menu: " . implode( ', ', $mm[1] ) . "\n";
preg_match_all( '#<li id=\'wp-admin-bar-([a-z0-9_-]+)\'#', $b, $ab );
echo "adminbalk: " . implode( ', ', $ab[1] ) . "\n";
echo "pagina-csvlink: " . ( preg_match( '#admin-post\.php\?action=zwijsen_sollicitaties_csv&amp;_wpnonce=[0-9a-f]+#', $b ) ? 'ja' : 'nee' ) . "\n";
// Profiel
$p = file_get_contents( "$o/r_profile.body" );
echo "profiel: wachtwoordknop=" . ( false !== strpos( $p, 'wp-generate-pw' ) ? 'ja' : 'nee' ) . " appwachtwoorden=" . ( false !== strpos( $p, 'application-passwords-section' ) ? 'ja' : 'nee' ) . "\n";
preg_match( '#<ul id="adminmenu">(.*?)</ul>\s*<div id="collapse-menu"#s', $p, $m );
preg_match_all( '#<div class=\'wp-menu-name\'>([^<]*)#', $m[1] ?? '', $mm );
echo "profiel-menu: " . implode( ', ', $mm[1] ) . "\n";
// CSV
foreach ( array( 'r_csv_all', 'r_csv_lier_nov' ) as $f ) {
	$raw = file_get_contents( "$o/$f.body" );
	echo "== $f BOM=" . ( 0 === strpos( $raw, "\xEF\xBB\xBF" ) ? 'ja' : 'nee' ) . " utf8=" . ( mb_check_encoding( $raw, 'UTF-8' ) ? 'ja' : 'nee' ) . "\n";
	$fh = fopen( 'php://memory', 'r+' ); fwrite( $fh, substr( $raw, 3 ) ); rewind( $fh );
	$n = 0;
	while ( false !== ( $r = fgetcsv( $fh, 0, ',', '"', '' ) ) ) { echo '  [' . count( $r ) . '] ' . implode( ' ¦ ', $r ) . "\n"; $n++; }
}
echo "zichtbaar zonder cap (s_page): " . ( preg_match( $pii, file_get_contents( "$o/s_page.body" ) ) ? 'PII!' : 'geen gegevens' ) . ' / ' . strip_tags( substr( file_get_contents( "$o/s_page.body" ), -600 ) ) . "\n";

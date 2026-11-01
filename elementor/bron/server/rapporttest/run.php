<?php
// php run.php : alle gevallen door harness.php, telkens een apart PHP-proces.
$d = __DIR__;
$c = array( 22 => json_decode( file_get_contents( "$d/c22.json" ), true ), 23 => json_decode( file_get_contents( "$d/c23.json" ), true ) );
$P = 'zwijsen-sollicitaties';
$L = 'Lier en Antwerpen-Zuidrand';
$gevallen = array();
$gevallen[] = array( 'r_page', 22, 'wp-admin/admin.php', array( 'page' => $P ) );
$gevallen[] = array( 'r_page_lier', 22, 'wp-admin/admin.php', array( 'page' => $P, 'regio' => $L ) );
$gevallen[] = array( 'r_page_van', 22, 'wp-admin/admin.php', array( 'page' => $P, 'van' => '2026-11-01' ) );
$gevallen[] = array( 'r_page_tot', 22, 'wp-admin/admin.php', array( 'page' => $P, 'tot' => '2026-10-31' ) );
$gevallen[] = array( 'r_csv_all', 22, 'wp-admin/admin-post.php', array( 'action' => 'zwijsen_sollicitaties_csv', '_wpnonce' => $c[22]['nonce'] ) );
$gevallen[] = array( 'r_csv_lier_nov', 22, 'wp-admin/admin-post.php', array( 'action' => 'zwijsen_sollicitaties_csv', 'regio' => $L, 'van' => '2026-11-01', 'tot' => '2026-11-01', '_wpnonce' => $c[22]['nonce'] ) );
$gevallen[] = array( 'r_csv_badnonce', 22, 'wp-admin/admin-post.php', array( 'action' => 'zwijsen_sollicitaties_csv', '_wpnonce' => 'deadbeef00' ) );
$gevallen[] = array( 'r_csv_nonce', 22, 'wp-admin/admin-post.php', array( 'action' => 'zwijsen_sollicitaties_csv' ) );
$blok = array(
	'index'      => array( 'wp-admin/index.php', array() ),
	'posts'      => array( 'wp-admin/edit.php', array() ),
	'pages'      => array( 'wp-admin/edit.php', array( 'post_type' => 'page' ) ),
	'postnew'    => array( 'wp-admin/post-new.php', array() ),
	'pageedit'   => array( 'wp-admin/post.php', array( 'post' => '3292', 'action' => 'edit' ) ),
	'elementor_editor' => array( 'wp-admin/post.php', array( 'post' => '3292', 'action' => 'elementor' ) ),
	'elementor_menu'   => array( 'wp-admin/admin.php', array( 'page' => 'elementor' ) ),
	'elementor_subs'   => array( 'wp-admin/admin.php', array( 'page' => 'e-form-submissions' ) ),
	'elementor_lib'    => array( 'wp-admin/edit.php', array( 'post_type' => 'elementor_library' ) ),
	'settings'   => array( 'wp-admin/options-general.php', array() ),
	'users'      => array( 'wp-admin/users.php', array() ),
	'useredit1'  => array( 'wp-admin/user-edit.php', array( 'user_id' => '1' ) ),
	'plugins'    => array( 'wp-admin/plugins.php', array() ),
	'tools'      => array( 'wp-admin/tools.php', array() ),
	'hackguard'  => array( 'wp-admin/tools.php', array( 'page' => 'zwijsen-hack-guard' ) ),
	'upload'     => array( 'wp-admin/upload.php', array() ),
	'comments'   => array( 'wp-admin/edit-comments.php', array() ),
	'other_post' => array( 'wp-admin/admin-post.php', array( 'action' => 'iets_anders' ) ),
);
foreach ( $blok as $n => $b ) {
	$gevallen[] = array( 'r_blok_' . $n, 22, $b[0], $b[1] );
}
$gevallen[] = array( 'r_profile', 22, 'wp-admin/profile.php', array() );
$gevallen[] = array( 'r_wfls', 22, 'wp-admin/admin.php', array( 'page' => 'WFLS' ) );
$gevallen[] = array( 's_page', 23, 'wp-admin/admin.php', array( 'page' => $P ) );
$gevallen[] = array( 's_csv', 23, 'wp-admin/admin-post.php', array( 'action' => 'zwijsen_sollicitaties_csv', '_wpnonce' => $c[23]['nonce'] ) );
$gevallen[] = array( 's_profile', 23, 'wp-admin/profile.php', array() );
$gevallen[] = array( 'o_page', 0, 'wp-admin/admin.php', array( 'page' => $P ) );
$gevallen[] = array( 'o_csv', 0, 'wp-admin/admin-post.php', array( 'action' => 'zwijsen_sollicitaties_csv' ) );
$alleen = $argv[1] ?? '';
foreach ( $gevallen as $g ) {
	if ( $alleen && 0 !== strpos( $g[0], $alleen ) ) { continue; }
	$spec = array( 'name' => $g[0], 'script' => $g[2], 'query' => $g[3], 'cookies' => $g[1] ? $c[ $g[1] ]['cookies'] : array() );
	exec( 'php ' . escapeshellarg( "$d/harness.php" ) . ' ' . escapeshellarg( json_encode( $spec ) ) . ' 2>&1', $o );
	echo file_get_contents( "$d/out/{$g[0]}.json" ), "\n";
}

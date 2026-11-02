<?php
// Uitvoer: JSON met dezelfde rijen als het rapport "Sollicitaties" (geen naam/mail/telefoon/IP).
if ( ! function_exists( 'zwijsen_snel_rapport_rijen' ) ) { fwrite( STDERR, "mu-plugin ontbreekt\n" ); exit( 1 ); }
echo wp_json_encode( zwijsen_snel_rapport_rijen( array( 'van' => '', 'tot' => '', 'regio' => '' ) ) );

<?php
// Staging, alleen lezen: post 85 en term 16 byte-exact naar bestanden.
global $wpdb;
$d = $args[0];
file_put_contents( "$d/post-85-staging.html", $wpdb->get_var( "SELECT post_content FROM {$wpdb->posts} WHERE ID=85" ) );
file_put_contents( "$d/term-16-staging.html", $wpdb->get_var( "SELECT description FROM {$wpdb->term_taxonomy} WHERE term_id=16 AND taxonomy='category'" ) );
echo md5_file( "$d/post-85-staging.html" ), ' ', filesize( "$d/post-85-staging.html" ), "\n", md5_file( "$d/term-16-staging.html" ), ' ', filesize( "$d/term-16-staging.html" ), "\n";

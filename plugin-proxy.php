<?php
include __DIR__ . '/download-file.php';

if ( isset( $_GET['plugin'] ) ) {
	$plugin_name = preg_replace( '#[^a-zA-Z0-9\.\-_]#', '', $_GET['plugin'] );
	if ( $plugin_name === 'glotpress-local' ) {
		download_file( 'https://github.com/GlotPress/GlotPress/archive/refs/heads/local-wasm.zip' );
	}

	download_file( 'https://downloads.wordpress.org/plugin/' . $plugin_name . '.zip' );
}

if ( isset( $_GET['theme'] ) ) {
	$theme_name = preg_replace( '#[^a-zA-Z0-9\.\-_]#', '', $_GET['theme'] );
	download_file( 'https://downloads.wordpress.org/theme/' . $theme_name . '.zip' );
}

die( 'Invalid request' );

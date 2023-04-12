<?php
include __DIR__ . '/download-file.php';

if ( isset($_GET['url']) && preg_match( '#^https://translate.wordpress.org/#', $_GET['url'] ) ) {
    download_file( $_GET['url'] );
}

die( 'Invalid request' );

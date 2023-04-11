<?php

function download_file($url)
{
    $cache = __DIR__ . '/cache/' . preg_replace( '#[^a-z0-9-]+#', '-', strtolower( substr( $url, 8 ) ) ) . '.' . crc32( $url );
    if ( file_exists( $cache . '.body' ) ) {
        return [ unserialize( file_get_contents( $cache . '.headers' ) ), file_get_contents( $cache . '.body' ) ];
    }
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

    $response = curl_exec($ch);

    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = array_map('trim', explode("\n", substr($response, 0, $header_size)));
    $body = substr($response, $header_size);
    file_put_contents( $cache . '.headers', serialize( $headers ) );
    file_put_contents( $cache . '.body', $body );

    return [$headers, $body];
}

if (isset($_GET['plugin'])) {
    $plugin_name = preg_replace('#[^a-zA-Z0-9\.\-_]#', '', $_GET['plugin']);
    if ( $plugin_name !== 'glotpress-local' ) {
	    $zip_url = 'https://downloads.wordpress.org/plugin/' . $plugin_name;
    } else {
	    $zip_url = 'https://github.com/GlotPress/GlotPress/archive/refs/heads/local.zip';
    }
} else if (isset($_GET['theme'])) {
    $theme_name = preg_replace('#[^a-zA-Z0-9\.\-_]#', '', $_GET['theme']);
    $zip_url = 'https://downloads.wordpress.org/theme/' . $theme_name;
} else {
    die('Invalid request');
}

[$received_headers, $bytes] = download_file($zip_url);

$forward_headers = [
    'content-length',
    'content-type',
    'content-disposition',
    'x-frame-options',
    'last-modified',
    'etag',
    'date',
    'age',
    'vary',
    'cache-Control'
];

foreach ($received_headers as $received_header) {
    $comparable_header = strtolower($received_header);
    foreach ($forward_headers as $sought_header) {
        if (substr($comparable_header, 0, strlen($sought_header)) === $sought_header) {
            header($received_header);
            break;
        }
    }
}

echo $bytes;

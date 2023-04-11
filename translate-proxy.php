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

if ( ! isset($_GET['url']) || ! preg_match( '#^https://translate.wordpress.org/#', $_GET['url'] ) ) {
    die('Invalid request');
}

[$received_headers, $bytes] = download_file($_GET['url']);

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

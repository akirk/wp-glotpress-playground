<?php

function download_file($url) {
	$cache = __DIR__ . '/cache/' . preg_replace( '#[^a-z0-9-]+#', '-', strtolower( substr( $url, 8 ) ) ) . '.' . crc32( $url );
	if ( false&&file_exists( $cache . '.body' ) ) {
		$headers = unserialize( file_get_contents( $cache . '.headers' ) );
		$body = file_get_contents( $cache . '.body' );
	} else {
		$ch = curl_init( $url );
		curl_setopt( $ch, CURLOPT_HEADER, 1 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_BINARYTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );

		$response = curl_exec( $ch );

		$header_size = curl_getinfo( $ch, CURLINFO_HEADER_SIZE );
		$headers = array();
		$body = substr($response, $header_size);
		foreach ( explode("\n", substr($response, 0, $header_size)) as $header ) {
			[$key, $value] = explode( trim( $header), ':', 2);
			$headers[strtolower($key)] = $value;
		}
		$headers['content-length'] = strlen( $body );
		file_put_contents( $cache . '.headers', serialize( $headers ) );
		file_put_contents( $cache . '.body', $body );
	}

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

	foreach ( $headers as $key => $value ) {
	    if ( in_array( $key, $forward_headers, true ) ) {
	        header($key . ':' . $value );
	    }
	}

	echo $body;
	exit;
}

<?php

/**
 *  
 * User profile caching based on original work by Sergej Müller http://web.archive.org/web/20130804041932/http://playground.ebiene.de/wordpress-gravatar-cache
 * 
 * Copyright (c) 2012 Sergej Müller
 * Copyright (c) 2013-2015 Peter Putzer
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/* No input? */
if (empty ( $_GET ['file'] )) {
	exit ();
}

/* Extract hash */
$hash = basename ( $_GET ['file'], '.png' );
$size = 64;
$fb_id = '';
$matches = array ();
$gravatar = '';
$cache_filename = '';

/* Right format? */
if (! preg_match ( '#^([a-f0-9]{32})-([0-9]+)x\2$#i', $hash, $matches )) {
	if (! preg_match ( '#^facebook-([0-9]+)-([0-9]+)x\2$#i', $hash, $matches )) {
		exit ();
	} else {
		$fb_id = isset ( $matches [1] ) ? $matches [1] : $fb_id;
		$size = isset ( $matches [2] ) ? $matches [2] : $size;
	}
} else {
	$hash = isset ( $matches [1] ) ? $matches [1] : $hash;
	$size = isset ( $matches [2] ) ? $matches [2] : $size;
}

/* Gravatar */
if (empty ( $fb_id )) {
	
	$gravatar = sprintf ( 'https://secure.gravatar.com/avatar/%s.png?s=%d&d=404', $hash, $size );
	
	$cache_filename = $hash;
	
} else { /* Facebook profile pictures */ 
	
	$gravatar = sprintf ( 'https://graph.facebook.com/%s/picture?type=square&width=%d&height=%d', $fb_id, $size, $size );
	
	$cache_filename = 'facebook-' . $fb_id;
}

/* filename */
$filename = dirname ( $_SERVER ['SCRIPT_FILENAME'] );

/* cache file */
$cache = sprintf ( '%s/cache/%s-%dx%d.png', $filename, $cache_filename, $size, $size );

/* Fetch gravatar/Facebook profile picture */
$source = @file_get_contents ( $gravatar );

if (! $source) {
	/* default */
	$source = @file_get_contents ( sprintf ( '%s/default.png', $filename ) );
	
} else {
	/* optimize */
	
	$ysmush = json_decode ( @file_get_contents ( sprintf ( 'http://ws1.adq.ac4.yahoo.com/ysmush.it/ws.php?img=%s', urlencode ( $gravatar ) ) ) );
	
	if ($ysmush && ! empty ( $ysmush->dest )) {
		$source = @file_get_contents ( urldecode ( $ysmush->dest ) );
	}
}

/* Save optimized image */
$response = @file_put_contents ( $cache, $source );

/* Any errors? */
if (! $response) {
	exit ();
}

/* Let's set some HTTP headers */
header ( 'Content-Type: image/png' );
header ( 'Content-Length: ' . $response );
header ( 'Expires: ' . gmdate ( 'D, d M Y H:i:s \G\M\T', time () + (60 * 60 * 24) ) ); // 24 hours

/* Done. */
echo $source;

?>
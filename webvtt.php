<?php
/**
Plugin Name: WebVTT
Plugin URI: https://github.com/bobbywalters/webvtt
Description: Add HTML5 text track files to videos.
Author: Bobby Walters
Author URI: https://github.com/bobbywalters
Version: 1.0.0
Text Domain: webvtt
Domain Path: /languages
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

/*
Copyright (C) 2015 Bobby Walters

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; version 2
of the License.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

defined( 'WPINC' ) or die( 'No direct access.' );

function webvtt_get_video_name( $video ) {
	$n = wp_basename( $video );
	$p = strrpos( $n, '.' );

	return false === $p ? $n : substr( $n, 0, $p );
}

function webvtt_get_video_tracks( $video ) {
	global $wpdb;

	$n = webvtt_get_video_name( $video );
	$nl = strlen( $n ) + 1;

	// $wpdb->esc_like was added in WP 4.0.0 but avoiding this call
	// keeps the required version down at 3.6.0 with addcslashes.
	$en = esc_sql( addcslashes( $n, '_%\\' ) );

	$ts = null;
	$rs = $wpdb->get_results(
		'SELECT ID, post_name FROM ' . $wpdb->posts
		. ' WHERE ('
			. "post_name LIKE '" . $en . "_captions___'"
			. " OR post_name LIKE '" . $en . "_chapters___'"
			. " OR post_name LIKE '" . $en . "_descriptions___'"
		  . " OR post_name LIKE '" . $en . "_metadata___'"
		  . " OR post_name LIKE '" . $en . "_subtitles___'"
		. ") AND post_type='attachment' AND post_mime_type='text/vtt'"
	);
	foreach ( $rs as $t ) {
		$u = wp_get_attachment_url( $t->ID );
		if ( $u ) {
			$ts .= '<track kind="' . substr( $t->post_name, $nl, -3 )
				. '" src="' . $u
				. '" srclang="' . esc_attr( substr( $t->post_name, -2 ) )
				. '">';
		}
	}

	return $ts;
}

function webvtt_video_shortcode_tracks( $output, $atts, $video, $post_id, $library ) {
	if ( ! $video ) {
		foreach ( array( 'mp4', 'webm', 'ogv', 'm4v', 'src' ) as $t ) {
			if ( ! empty( $atts[$t] ) ) {
				$video = $atts[$t];
				break;
			}
		}

		if ( ! $video ) {
			return $output;
		}
	}

	$ts = webvtt_get_video_tracks( $video );
	if ( $ts ) {
		return str_replace( '</video>', $ts . '</video>', $output );
	}

	return $output;
}

add_filter( 'wp_video_shortcode', 'webvtt_video_shortcode_tracks', 10, 5 );

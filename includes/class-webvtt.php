<?php
/**
 * Shared functionality of WebVTT used by public and admin console
 * hooks.
 *
 * @link https://github.com/bobbywalters/webvtt
 * @package WebVTT
 * @since 1.1.0
 */

/**
 * The WebVTT class provides shared functionality across public and
 * administration screens. The plugin bootstrap file sets a global
 * reference to `$webvtt` pointing to an instance of this class.
 *
 * @package WebVTT
 * @author Bobby Walters
 */
class WebVTT {
	/**
	 * Initialize the class along with shared public and admin hooks.
	 */
	function __construct() {
		add_filter( 'wp_video_shortcode', array( &$this, 'wp_video_shortcode' ), 10, 5 );
	}

	/**
	 * Retrieves VTTs that can be used with the supplied video name.
	 *
	 * @param string $name Get VTTs that are associated with this
	 * video name.
	 * @return array An array of WP_Post attachments for all associated
	 * VTTs. The array may be empty if no VTTs match the supplied name.
	 * @uses WebVTT::get_video_name()
	 * @uses WebVTT::posts_where()
	 * @see WP_Query::__construct()
	 * @see WP_Query::query()
	 */
	function get_tracks( $name ) {
		static $filter;
		if ( ! $filter ) {
			$filter = add_filter( 'posts_where', array( &$this, 'posts_where' ), 10, 2 );
		}

		$q = new WP_Query;

		return $q->query(
			array(
				'no_found_rows' => true,
				'order' => 'ASC',
				'orderby' => 'name',
				'post_mime_type' => 'text/vtt',
				'post_status' => 'any',
				'post_type' => 'attachment',
				'posts_per_page' => 25,
				'vtt_like' => $name,
			)
		);
	}

	/**
	 * Generates HTML5 `<track>` elements to be nested within a `<video>`
	 * element.
	 *
	 * @param string $video The URL of an uploaded video.
	 * @return string HTML with `<track>` elements for each associated
	 * VTT that should be used with the supplied video URL.
	 * @since 1.1.0
	 * @uses WebVTT::get_video_name()
	 * @uses WebVTT::get_tracks()
	 * @see wp_get_attachment_url()
	 */
	function get_tracks_html( $video ) {
		$n = $this->get_video_name( $video );
		$nl = strlen( $n ) + 1;

		$ts = null;
		foreach ( $this->get_tracks( $n ) as $t ) {
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

	/**
	 * Get a video post attachment by a VTT attachment name (slug).
	 *
	 * @param string $name The name (slug) of a VTT attachmentto find
	 * it's corresponding video.
	 * @return WP_Post|null A WP_Post that is associated with the VTT
	 * name (slug), otherwise `null`.
	 * @see WP_Query::__construct()
	 * @see WP_Query::query()
	 */
	function get_video_by_vtt_name( $name ) {
		$v = preg_filter(
			'/(.+?)[\W_](?:captions|chapters|descriptions|metadata|subtitles)[\W_][\w]{2}$/',
			'$1',
			$name
		);

		if ( $v ) {
			$q = new WP_Query;
			$q->query(
				array(
					'attachment' => $v,
				)
			);
			$v = $q->post;
		}

		return $v;
	}

	/**
	 * Get the video file name from the supplied URL. The file name
	 * returned will also leave the extension off just like `basename`.
	 *
	 * @param string $video URL to a video.
	 * @return string The video file name without it's extension.
	 * @see wp_basename()
	 */
	function get_video_name( $video ) {
		$n = wp_basename( $video );
		$p = strrpos( $n, '.' );

		return false === $p ? $n : substr( $n, 0, $p );
	}

	/**
	 * A filter matching the same name to modify an SQL WHERE clause if
	 * the query is looking for VTT files.
	 *
	 * @param string   $where    The WHERE clause of the query.
	 * @param WP_Query $wp_query The WP_Query instance (passed by
	 * reference).
	 * @return string A modified WHERE clause if the query is looking
	 * for VTT files, otherwise $where unmodified.
	 * @since 1.1.0
	 * @global $wpdb
	 * @see WP_Query::get()
	 */
	function posts_where( $where, &$wp_query ) {
		if ( $like = $wp_query->get( 'vtt_like' ) ) {
			global $wpdb;

			// $wpdb->esc_like was added in WP 4.0.0 but avoiding this call
			// keeps the required version down at 3.6.0 with addcslashes.
			$like = $wpdb->posts . ".post_name LIKE '" . esc_sql( addcslashes( $like, '_%\\' ) );

			$where = ' AND ('
				. $like . "_captions___' OR "
				. $like . "_chapters___' OR "
				. $like . "_descriptions___' OR "
				. $like . "_metadata___' OR "
				. $like	. "_subtitles___')"
				. $where;
		}

		return $where;
	}

	/**
	 * A filter matching the same name that will add `<track>` elements
	 * to the output of the video shortcode.
	 *
	 * @param string $output  Video shortcode HTML output.
	 * @param array  $atts    Array of video shortcode attributes.
	 * @param string $video   Video file.
	 * @param int    $post_id Post ID.
	 * @param string $library Media library used for the video shortcode.
	 * @return string The output of the video shortcode with added
	 * `<track>`s that match the video being displayed, otherwise $output
	 * unmodified.
	 * @since 1.1.0
	 * @uses WebVTT::get_tracks_html()
	 * @see wp_video_shortcode()
	 */
	function wp_video_shortcode( $output, $atts, $video, $post_id, $library ) {
		if ( ! $video ) {
			foreach ( array( 'mp4', 'webm', 'ogv', 'm4v', 'src' ) as $t ) {
				if ( ! empty( $atts[ $t ] ) ) {
					$video = $atts[ $t ];
					break;
				}
			}

			if ( ! $video ) {
				return $output;
			}
		}

		if ( $ts = $this->get_tracks_html( $video ) ) {
			return str_replace( '</video>', $ts . '</video>', $output );
		}

		return $output;
	}
}

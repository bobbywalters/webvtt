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
		add_action( 'init', array( &$this, 'init' ) );
		add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue_playlist_script' ) );

		add_filter( 'wp_video_shortcode', array( &$this, 'wp_video_shortcode' ), 10, 5 );

		add_shortcode( 'playlist', array( &$this, 'wp_playlist_shortcode' ) );
	}

	/**
	 * Enqueues a modified version of `wp-playlist` to support VTTs.
	 *
	 * @since 1.3.0
	 */
	function enqueue_playlist_script() {
		wp_deregister_script( 'wp-playlist' );
		wp_register_script( 'wp-playlist', plugin_dir_url( __FILE__ ) . 'js/wp-playlist.min.js', array( 'wp-util', 'backbone', 'mediaelement' ), null, true );
	}

	/**
	 * Retrieves VTTs that can be used with the supplied video name.
	 *
	 * @param string $name Get VTTs that are associated with this
	 * video name.
	 * @return array An array of WP_Post attachments for all associated
	 * VTTs. The array may be empty if no VTTs match the supplied name.
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
			if ( $u = wp_get_attachment_url( $t->ID ) ) {
				$ts .= '<track kind="' . substr( $t->post_name, $nl, -3 )
					. '" src="' . $u
					. '" srclang="' . esc_attr( substr( $t->post_name, -2 ) )
					. '">';
			}
		}

		return $ts;
	}

	/**
	 * Generates an array suitable for JSON containing `<track>`
	 * elements to be nested within a `<video>` element.
	 *
	 * @param string $video The URL of an uploaded video.
	 * @return array An array suitable for JSON usage with each entry
	 * an array representing a `<track>` in HTML5 with attribute names
	 * (eg, `kind`, `src`, `srclang`, etc) as keys for each associated
	 * VTT that should be used with the supplied video URL.
	 * @since 1.3.0
	 * @uses WebVTT::get_video_name()
	 * @uses WebVTT::get_tracks()
	 * @see wp_get_attachment_url()
	 */
	function get_tracks_json_data( $video ) {
		$n = $this->get_video_name( $video );
		$nl = strlen( $n ) + 1;

		$ts = null;
		foreach ( $this->get_tracks( $n ) as $t ) {
			if ( $u = wp_get_attachment_url( $t->ID ) ) {
				$ts[] = array(
					'kind' => substr( $t->post_name, $nl, -3 ),
					'src' => $u,
					'srclang' => esc_attr( substr( $t->post_name, -2 ) ),
				);
			}
		}

		return $ts;
	}

	/**
	 * Get a video post attachment by a VTT attachment name (slug).
	 *
	 * @param string $name The name (slug) of a VTT attachment to find
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
		return pathinfo( parse_url( $video, PHP_URL_PATH ), PATHINFO_FILENAME );
	}

	/**
	 * An action matching the same name that loads the text domain for
	 * internationalization (i18n) support.
	 *
	 * The directory containing the gettext files is "languages" at the
	 * base of the plugin directory by default.
	 *
	 * @see load_plugin_textdomain()
	 */
	function init() {
		load_plugin_textdomain( 'webvtt', false, 'webvtt/languages' );
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
		if ( $like = sanitize_title_for_query( $wp_query->get( 'vtt_like' ) ) ) {
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
	 * Builds the Playlist shortcode output.
	 *
	 * This implements the functionality of the playlist shortcode for
	 * displaying a collection of WordPress audio or video files in a
	 * post.
	 *
	 * This method is based on the WordPress core function
	 * `wp_playlist_shortcode` but has been modified to add VTT data.
	 *
	 * @param array $attr {
	 *     Array of default playlist attributes.
	 *
	 *     @type string  $type         Type of playlist to display. Accepts 'audio' or 'video'. Default 'audio'.
	 *     @type string  $order        Designates ascending or descending order of items in the playlist.
	 *                                 Accepts 'ASC', 'DESC'. Default 'ASC'.
	 *     @type string  $orderby      Any column, or columns, to sort the playlist. If $ids are
	 *                                 passed, this defaults to the order of the $ids array ('post__in').
	 *                                 Otherwise default is 'menu_order ID'.
	 *     @type int     $id           If an explicit $ids array is not present, this parameter
	 *                                 will determine which attachments are used for the playlist.
	 *                                 Default is the current post ID.
	 *     @type array   $ids          Create a playlist out of these explicit attachment IDs. If empty,
	 *                                 a playlist will be created from all $type attachments of $id.
	 *                                 Default empty.
	 *     @type array   $exclude      List of specific attachment IDs to exclude from the playlist. Default empty.
	 *     @type string  $style        Playlist style to use. Accepts 'light' or 'dark'. Default 'light'.
	 *     @type bool    $tracklist    Whether to show or hide the playlist. Default true.
	 *     @type bool    $tracknumbers Whether to show or hide the numbers next to entries in the playlist. Default true.
	 *     @type bool    $images       Show or hide the video or audio thumbnail (Featured Image/post
	 *                                 thumbnail). Default true.
	 *     @type bool    $artists      Whether to show or hide artist name in the playlist. Default true.
	 * }
	 * @return string Playlist output. Empty string if the passed type is unsupported.
	 * @since 1.3.0
	 * @global int $content_width
	 * @staticvar int $instance
	 * @see wp_playlist_shortcode
	 */
	function wp_playlist_shortcode( $attr ) {
		static $instance = 0;
		$instance++;

		if ( ! empty( $attr['ids'] ) ) {
			$attr['include'] = $attr['ids'];

			// 'ids' is explicitly ordered, unless you specify otherwise.
			if ( empty( $attr['orderby'] ) ) {
				$attr['orderby'] = 'post__in';
			}
		}

		$output = apply_filters( 'post_playlist', '', $attr, $instance );
		if ( '' !== $output ) {
			return $output;
		}
		unset( $output );

		$atts = shortcode_atts( array(
			'type' => 'audio',
			'order' => 'ASC',
			'orderby' => 'menu_order ID',
			'id' => get_the_ID() ?: 0,
			'include' => '',
			'exclude' => '',
			'style' => 'light',
			'tracklist' => true,
			'tracknumbers' => true,
			'images' => true,
			'artists' => true
		), $attr, 'playlist' );

		if ( 'audio' !== $atts['type'] ) {
			$atts['type'] = 'video';
		}

		$args = array(
			'post_status' => 'inherit',
			'post_type' => 'attachment',
			'post_mime_type' => $atts['type'],
			'order' => $atts['order'],
			'orderby' => $atts['orderby']
		);

		if ( '' !== $atts['include'] ) {
			$args['include'] = $atts['include'];
		} else {
			$args['post_parent'] = intval( $atts['id'] );

			if ( '' !== $atts['exclude'] ) {
				$args['exclude'] = $atts['exclude'];
			}
		}

		$attachments = get_posts( $args );
		if ( empty( $attachments ) ) {
			return '';
		}

		if ( is_feed() ) {
			ob_start();

			echo "\n";
			foreach ( $attachments as $a ) {
				echo wp_get_attachment_link( $a->ID ), "\n";
			}

			return ob_get_clean();
		}

		$outer = 22; // default padding and border of wrapper

		global $content_width;
		$default_width = 640;
		$default_height = 360;

		$theme_width = empty( $content_width ) ? $default_width : ( $content_width - $outer );
		$theme_height = empty( $content_width ) ? $default_height : round( ( $default_height * $theme_width ) / $default_width );

		ob_start();

		if ( 1 === $instance ) {
			do_action( 'wp_playlist_scripts', $atts['type'], $atts['style'] );
		}

		echo '<div class="wp-playlist wp-',
			$atts['type'],
			'-playlist wp-playlist-',
			esc_attr( $atts['style'] ),
			'">';

		if ( 'audio' === $atts['type'] ) {
			echo '<div class="wp-playlist-current-item"></div><audio';
		} else {
			echo '<video height="', $theme_height, '"';
		}

		echo ' controls="controls" preload="none" width="', $theme_width, '">',
			'</', $atts['type'], '>';

		echo '<div class="wp-playlist-next"></div>',
			'<div class="wp-playlist-prev"></div>',
			'<noscript>',
			'<ol>';

		$atts['images'] = wp_validate_boolean( $atts['images'] );

		$tracks = array();
		foreach ( $attachments as $a ) {
			// For <noscript>.
			echo '<li>', wp_get_attachment_link( $a->ID ), '</li>';

			// Remaining is for JSON data.
			$track = array(
				'src' => wp_get_attachment_url( $a->ID ),
				'title' => $a->post_title,
			);

			if ( $a->post_excerpt ) {
				$track['caption'] = $a->post_excerpt;
			}

			if ( $a->post_content ) {
				$track['description'] = $a->post_content;
			}

			if ( $meta = $this->get_tracks_json_data( $track['src'] ) ) {
				$track['webvtt'] = $meta;
			}

			$meta = wp_get_attachment_metadata( $a->ID );
			if ( ! empty( $meta ) ) {
				foreach ( wp_get_attachment_id3_keys( $a ) as $key => $label ) {
					if ( ! empty( $meta[ $key ] ) ) {
						$track['meta'][ $key ] = $meta[ $key ];
					}
				}

				if ( 'video' === $atts['type'] ) {
					if ( ! empty( $meta['width'] ) && ! empty( $meta['height'] ) ) {
						$width = $meta['width'];
						$height = $meta['height'];
						$theme_height = round( ( $height * $theme_width ) / $width );
					} else {
						$width = $default_width;
						$height = $default_height;
					}

					$track['dimensions'] = array(
						'original' => compact( 'width', 'height' ),
						'resized' => array(
							'width' => $theme_width,
							'height' => $theme_height
						)
					);
				}
			}

			if ( true === $atts['images'] && ( $thumb_id = get_post_thumbnail_id( $a->ID ) ) ) {
				$track['image'] = wp_get_attachment_image_src( $thumb_id, 'full' );
				$track['thumb'] = wp_get_attachment_image_src( $thumb_id );
			}

			$tracks[] = $track;
		}

		echo '</ol>',
			'</noscript>',
			'<script type="application/json" class="wp-playlist-script">',
			wp_json_encode( array(
				'type' => $atts['type'],
				// don't pass strings to JSON, will be truthy in JS
				'tracklist' => wp_validate_boolean( $atts['tracklist'] ),
				'tracknumbers' => wp_validate_boolean( $atts['tracknumbers'] ),
				'images' => $atts['images'],
				'artists' => wp_validate_boolean( $atts['artists'] ),
				'tracks' => $tracks,
			) ),
			'</script>',
			'</div>';

		return ob_get_clean();
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

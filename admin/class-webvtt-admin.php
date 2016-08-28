<?php
/**
 * Admin specific functionality of WebVTT.
 *
 * @link https://github.com/bobbywalters/webvtt
 * @package WebVTT
 * @since 1.1.0
 */

/**
 * The main interaction point for WebVTT and the WordPress admin
 * console. Actions, filters, and supporting code will all be
 * loaded from this class if it's admin console related.
 *
 * @package WebVTT
 * @subpackage WebVTT/admin
 * @author Bobby Walters
 */
class WebVTT_Admin {
	/**
	 * Initialize the class and admin hooks.
	 */
	function __construct() {
		global $webvtt;

		add_action( 'admin_enqueue_scripts', array( &$webvtt, 'enqueue_playlist_script' ) );
		add_action( 'wp_ajax_parse-media-shortcode', array( &$webvtt, 'enqueue_playlist_script' ), 1 );

		add_filter( 'attachment_fields_to_edit', array( &$this, 'attachment_fields_to_edit' ), 10, 2 );
		add_filter( 'post_mime_types', array( &$this, 'post_mime_types' ) );
	}

	/**
	 * A filter matching the same name to add additional information
	 * about an attachment if it is a VTT or video.
	 *
	 * @param array   $form_fields An array containing information about
	 * the supplied attachment to display in the media library.
	 * @param WP_Post $post        Attachment post being viewed from the
	 * media library.
	 * @return array Updated $form_fields if the attachment is a VTT or
	 * video.
	 * @since 1.1.0
	 * @uses $webvtt
	 * @see attachment_fields_to_edit()
	 */
	function attachment_fields_to_edit( $form_fields, $post ) {
		global $webvtt;
		$t = $post->post_mime_type;
		if ( 'text/vtt' === $t ) {
			if ( $t = $webvtt->get_video_by_vtt_name( $post->post_name ) ) {
				$t = $this->get_post_link_html( $t );
			} else {
				$t = '--';
			}

			$form_fields['video'] = array(
				'label' => __( 'Video' ),
				'input' => 'html',
				'html' => $t,
			);
		} elseif ( 0 === strpos( $t, 'video/' ) && $ts = $webvtt->get_tracks( $post->post_name ) ) {
			$nl = strlen( $post->post_name ) + 1;

			static $labels, $locale;
			if ( ! $labels ) {
				$labels = array(
					'captions' => __( 'Video Captions', 'webvtt' ),
					'chapters' => __( 'Video Chapters', 'webvtt' ),
					'descriptions' => __( 'Video Descriptions', 'webvtt' ),
					'metadata' => __( 'Video Metadata', 'webvtt' ),
					'subtitles' => __( 'Video Subtitles', 'webvtt' ),
				);

				$locale = class_exists( 'Locale' ) ? get_locale() : false;
			}

			$vtts = array();
			foreach ( $ts as $i ) {
				$t = substr( $i->post_name, $nl, -3 );
				$j = substr( $i->post_name, -2 );
				if ( $locale ) {
					$j = Locale::getDisplayName( $j, $locale );
				}
				$vtts[ $t ][ $j ] = $i;
			}

			ksort( $vtts );
			foreach ( $vtts as $t => $js ) {
				ksort( $js );

				$h = '';
				foreach ( $js as $j => $i ) {
					$h .= '<li>' . $this->get_post_link_html( $i, $j ) . '</li>';
				}

				$form_fields[ 'video_' . $t ] = array(
					'label' => $labels[ $t ],
					'input' => 'html',
					'html' => '<ol>' . $h . '</ol>',
				);
			}
		}

		return $form_fields;
	}

	/**
	 * Generate an HTML edit or view link to the supplied post based on
	 * the current user's authority.
	 *
	 * @param WP_Post $post The post to create an HTML edit link to.
	 * @param string  $text The text of the link. If not supplied the
	 * `$post->post_title` will be used.
	 * @return string An HTML `a` element that points to either the edit
	 * or view page for the post.
	 * @see get_attachment_link()
	 * @see get_edit_post_link()
	 */
	protected function get_post_link_html( $post, $text = false ) {
		if ( ! $i = get_edit_post_link( $post->ID ) ) {
			$i = get_attachment_link( $post );
		}

		return '<a href="' . $i . '">'
			. esc_html( $text ?: $post->post_title )
			. '</a>';
	}

	/**
	 * A filter matching the same name that adds the VTT mime type to
	 * the list of defaults.
	 *
	 * @param array $post_mime_types An array of default post mime types
	 * to filter.
	 * @return array Default list of mime types updated with an entry
	 * for `text/vtt` so VTT files can be filtered within the media
	 * library.
	 * @since 1.1.0
	 * @see post_mime_types()
	 */
	function post_mime_types( $post_mime_types ) {
		$post_mime_types['text/vtt'] = array(
			__( 'Video tracks', 'webvtt' ),
			__( 'Manage Video Tracks', 'webvtt' ),
			_n_noop( 'Video track <span class="count">(%s)</span>', 'Video tracks <span class="count">(%s)</span>', 'webvtt' ),
		);

		return $post_mime_types;
	}
}

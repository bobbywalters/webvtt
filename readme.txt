=== WebVTT ===
Contributors: bobbywalters
Tags: html5, track, video, webvtt
Requires at least: 3.6.0
Tested up to: 4.3
Stable tag: trunk
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Add HTML5 text track files to videos.

== Description ==

Creates any number of HTML5 <track> elements for uploaded WebVTT files for videos displayed using the `[video]` shortcode.

The uploaded WebVTT file names must follow this convention to be recognized:
`<video file name> + <separator> + <WebVTT track kind> + <separator> + <language code> + '.vtt'`

With each value described as:

* `<video file name>` The base file name of the video file (without it's extension).
* `<separator>` A normalized single character separator typically a dash `-` to match the WordPress name normalization pattern.
* `<WebVTT track kind>` The kind of text track. The kind is defined by the HTML5 specification and may be one of:
 * captions
 * chapters
 * descriptions
 * metadata
 * subtitles
* `<separator>` (defined above)
* `<language code>` The language of the track text data. This is a lower case 2 character code that represents the language only part of a full BCP 47 language tag. Here's a listing of available [language codes](http://www.w3schools.com/tags/ref_language_codes.asp) for reference.
* `'.vtt'` The recognized standard file extension for WebVTT files.

As an example, a video file named `'my-video.mp4'` would be able to leverage uploaded track files named:

* `'my-video-chapters-en.vtt'` to add a chapters listing in English
* `'my-video-subtitles-en.vtt'` to add a subtitles track in English
* `'my-video-subtitles-es.vtt'` to add a subtitles track in Spanish

== Installation ==

1. Upload entire **webvtt** directory to `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Upload new or see existing VTT files with the correct naming convention become available to HTML5 videos displayed using the `[video]` shortcode

== Frequently Asked Questions ==

= Why aren't my VTT files available with my video? =

The uploaded WebVTT file names must follow this convention to be recognized:
`<video file name> + <separator> + <WebVTT track kind> + <separator> + <language code> + '.vtt'`

This avoids:

* the need for a settings page
* extra meta data fields and trying to keep these in sync
* having to re-upload a video when a VTT file is changed or updated

Please see the Description section for a full explanation of the VTT file name format.

== Screenshots ==

1. Upload video and associated VTT files following the naming convention.
2. Video being displayed using the `[video]` shortcode with the VTT tracks available.

== Changelog ==

= 1.0.1 =
Released 2015-08-19

* NEW: Added Screen shots and filled in more readme sections
* FIX: Corrected `.vtt` file names in examples

= 1.0.0 =
Initial release.

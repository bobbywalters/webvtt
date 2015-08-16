=== WebVTT ===
Contributors: bobbywalters
Tags: html5, track, video, webvtt
Requires at least: 3.6.0
Tested up to: 4.3
Stable tag: 1.0.0
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Add HTML5 text track files to videos.

== Description ==

Creates any number of HTML5 <track> elements for uploaded WebVTT files for a given video.

The uploaded WebVTT file names should follow this convention to be recognized:
`<video file name> + <separator> + <WebVTT track kind> + <separator> + <language code> + '.vtt'`

With each value described as:

* `<video file name>` The base file name of the video file (without it's extension).
* `<separator>` A normalized single character separator typically a dash `-` or underscore `_`.
* `<WebVTT track kind>` The kind of text track. The kind is defined by the HTML5 specification and may be one of:
 * captions
 * chapters
 * descriptions
 * metadata
 * subtitles
* `<separator>` (defined above)
* `<language code>` The language of the track text data. This is a lower case 2 character code that represents the language only part of a full BCP 47 language tag. Here's a listing of available [language codes](http://www.w3schools.com/tags/ref_language_codes.asp) for reference.
* `'.vtt'` The recognized standard file extension for WebVTT files.

As an example, a video file named `'my_video.mp4'` would be able to leverage uploaded track files named:

* `'my_video_chapters_en.mp4'` to add a chapters listing in English
* `'my_video_subtitles_en.mp4'` to add a subtitle track in English
* `'my_video_subtitles_es.mp4'` to add a subtitle track in Spanish

== Changelog ==

= 1.0.0 =
Initial release.

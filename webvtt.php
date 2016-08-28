<?php
/**
 * Plugin Name: WebVTT
 * Plugin URI: https://github.com/bobbywalters/webvtt
 * Description: Add HTML5 text track files to videos.
 * Author: Bobby Walters
 * Author URI: https://github.com/bobbywalters
 * Version: 1.3.1
 * Text Domain: webvtt
 * Domain Path: /languages
 * License: GPLv2
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * @link https://github.com/bobbywalters/webvtt
 * @package WebVTT
 * @since 1.0.0
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

require 'includes/class-webvtt.php';

$webvtt = new WebVTT;

if ( is_admin() ) {
	require 'admin/class-webvtt-admin.php';

	new WebVTT_Admin;
}

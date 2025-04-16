<?php
/**
 * Disable Generate Thumbnails
 *
 * @package    Disable Generate Thumbnails
 * @subpackage DisableGenerateThumbnails Main Functions
/*
	Copyright (c) 2019- Katsushi Kawamori (email : dodesyoswift312@gmail.com)
	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; version 2 of the License.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$disablegeneratethumbnails = new DisableGenerateThumbnails();

/** ==================================================
 * Main Functions
 */
class DisableGenerateThumbnails {

	/** ==================================================
	 * Construct
	 *
	 * @since 1.00
	 */
	public function __construct() {

		add_filter( 'intermediate_image_sizes_advanced', array( $this, 'remove_image_sizes' ) );

		if ( get_option( 'disablegeneratethumbnails_threshold' ) ) {
			add_filter( 'big_image_size_threshold', '__return_false' );
		}

		if ( get_option( 'disablegeneratethumbnails_exif_rotate' ) ) {
			add_filter( 'wp_image_maybe_exif_rotate', '__return_false' );
		}
	}

	/** ==================================================
	 * Filters the image sizes automatically generated when uploading an image.
	 *
	 * @param array $sizes  sizes.
	 * @return array $sizes  sizes.
	 * @since 1.00
	 */
	public function remove_image_sizes( $sizes ) {

		$list_thumbnails = get_intermediate_image_sizes();
		$disablegeneratethumbnails_settings = get_option( 'disablegeneratethumbnails', array() );
		foreach ( $list_thumbnails as $value ) {
			if ( in_array( $value, $disablegeneratethumbnails_settings ) ) {
				unset( $sizes[ $value ] );
			}
		}

		return $sizes;
	}
}

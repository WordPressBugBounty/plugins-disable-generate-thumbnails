<?php
/**
 * Disable Generate Thumbnails
 *
 * @package    Disable Generate Thumbnails
 * @subpackage DisableGenerateThumbnailsAdmin Management screen
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

$disablegeneratethumbnailsadmin = new DisableGenerateThumbnailsAdmin();

/** ==================================================
 * Management screen
 */
class DisableGenerateThumbnailsAdmin {

	/** ==================================================
	 * Construct
	 *
	 * @since 1.00
	 */
	public function __construct() {

		add_action( 'admin_menu', array( $this, 'plugin_menu' ) );
		add_filter( 'plugin_action_links', array( $this, 'settings_link' ), 10, 2 );

		add_action( 'rest_api_init', array( $this, 'register_rest' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ), 10, 1 );
	}

	/** ==================================================
	 * Add settings page
	 *
	 * @since 1.00
	 */
	public function plugin_menu() {

		add_options_page(
			'Disable Generate Thumbnails Options',
			'Disable Generate Thumbnails',
			'manage_options',
			'disablegeneratethumbnails',
			array( $this, 'plugin_options' )
		);
	}

	/** ==================================================
	 * Add a "Settings" link to the plugins page
	 *
	 * @param  array  $links  links array.
	 * @param  string $file   file.
	 * @return array  $links  links array.
	 * @since 1.00
	 */
	public function settings_link( $links, $file ) {
		static $this_plugin;
		if ( empty( $this_plugin ) ) {
			$this_plugin = 'disable-generate-thumbnails/disablegeneratethumbnails.php';
		}
		if ( $file === $this_plugin ) {
			$links[] = '<a href="' . admin_url( 'options-general.php?page=disablegeneratethumbnails' ) . '">' . __( 'Settings', 'disable-generate-thumbnails' ) . '</a>';
		}
			return $links;
	}

	/** ==================================================
	 * Settings page
	 *
	 * @since 2.00
	 */
	public function plugin_options() {

		global $wp_version;
		$requires = '6.6';
		if ( version_compare( $wp_version, $requires, '>=' ) ) {
			$admin_screen = esc_html__( 'Loading…', 'disable-generate-thumbnails' );
		} else {
			/* translators: WordPress requires version */
			$admin_screen = sprintf( esc_html__( 'WordPress %s or higher is required to view this screen.', 'disable-generate-thumbnails' ), $requires );
		}
		printf( '<div class="wrap" id="disablegeneratethumbnails">%s</div>', esc_html( $admin_screen ) );
	}

	/** ==================================================
	 * Load script
	 *
	 * @param string $hook_suffix  hook_suffix.
	 * @since 2.00
	 */
	public function admin_scripts( $hook_suffix ) {

		if ( 'settings_page_disablegeneratethumbnails' !== $hook_suffix ) {
			return;
		}

		$asset_file = plugin_dir_path( __DIR__ ) . 'guten/build/index.asset.php';

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$asset = include $asset_file;

		wp_enqueue_style(
			'disablegeneratethumbnails-admin-style',
			plugin_dir_url( __DIR__ ) . 'guten/build/index.css',
			array( 'wp-components' ),
			'1.0.0',
		);

		wp_enqueue_script(
			'dgtadmin',
			plugin_dir_url( __DIR__ ) . 'guten/build/index.js',
			$asset['dependencies'],
			$asset['version'],
			array(
				'in_footer' => true,
			)
		);

		wp_set_script_translations( 'dgtadmin', 'disable-generate-thumbnails' );

		$disablegeneratethumbnails_settings = get_option( 'disablegeneratethumbnails', array() );
		$list_thumbnails = get_intermediate_image_sizes();
		$list_checks = array();
		foreach ( $list_thumbnails as $value ) {
			if ( in_array( $value, $disablegeneratethumbnails_settings ) ) {
				$list_checks[ $value ] = true;
			} else {
				$list_checks[ $value ] = false;
			}
		}

		$list_checks2 = array();
		$dgt_threshold = __( 'Large image threshold', 'disable-generate-thumbnails' );
		$dgt_exif_rotate = __( 'Exif automatic rotation', 'disable-generate-thumbnails' );
		$list_checks2[ $dgt_threshold ] = boolval( get_option( 'disablegeneratethumbnails_threshold', false ) );
		$list_checks2[ $dgt_exif_rotate ] = boolval( get_option( 'disablegeneratethumbnails_exif_rotate', false ) );

		wp_localize_script(
			'dgtadmin',
			'dgtadmin_data',
			array(
				'thumbnail_checks' => wp_json_encode( $list_checks, JSON_UNESCAPED_SLASHES ),
				'function_checks' => wp_json_encode( $list_checks2, JSON_UNESCAPED_SLASHES ),
			)
		);

		$this->credit( 'dgtadmin' );
	}

	/** ==================================================
	 * Register Rest API
	 *
	 * @since 2.00
	 */
	public function register_rest() {

		register_rest_route(
			'rf/dgt_api',
			'/token',
			array(
				'methods' => 'POST',
				'callback' => array( $this, 'api_save' ),
				'permission_callback' => array( $this, 'rest_permission' ),
			),
		);
	}

	/** ==================================================
	 * Rest Permission
	 *
	 * @since 2.00
	 */
	public function rest_permission() {

		return current_user_can( 'manage_options' );
	}

	/** ==================================================
	 * Rest API save
	 *
	 * @param object $request  changed data.
	 * @since 2.00
	 */
	public function api_save( $request ) {

		$args = json_decode( $request->get_body(), true );

		$thumbnails = filter_var(
			wp_unslash( $args['thumbnails'] ),
			FILTER_CALLBACK,
			array(
				'options' => function ( $value ) {
					return sanitize_text_field( $value );
				},
			)
		);
		$others = filter_var(
			wp_unslash( $args['others'] ),
			FILTER_CALLBACK,
			array(
				'options' => function ( $value ) {
					return sanitize_text_field( $value );
				},
			)
		);

		foreach ( $thumbnails as $key => $value ) {
			if ( ! $value ) {
				unset( $thumbnails[ $key ] );
			}
		}
		$data = array_keys( $thumbnails );
		update_option( 'disablegeneratethumbnails', $data );

		$dgt_threshold = __( 'Large image threshold', 'disable-generate-thumbnails' );
		$dgt_exif_rotate = __( 'Exif automatic rotation', 'disable-generate-thumbnails' );
		update_option( 'disablegeneratethumbnails_threshold', $others[ $dgt_threshold ] );
		update_option( 'disablegeneratethumbnails_exif_rotate', $others[ $dgt_exif_rotate ] );

		return new WP_REST_Response( $args, 200 );
	}

	/** ==================================================
	 * Credit
	 *
	 * @param string $handle  handle.
	 * @since 2.01
	 */
	private function credit( $handle ) {

		$plugin_name    = null;
		$plugin_ver_num = null;
		$plugin_path    = plugin_dir_path( __DIR__ );
		$plugin_dir     = untrailingslashit( wp_normalize_path( $plugin_path ) );
		$slugs          = explode( '/', $plugin_dir );
		$slug           = end( $slugs );
		$files          = scandir( $plugin_dir );
		foreach ( $files as $file ) {
			if ( '.' === $file || '..' === $file || is_dir( $plugin_path . $file ) ) {
				continue;
			} else {
				$exts = explode( '.', $file );
				$ext  = strtolower( end( $exts ) );
				if ( 'php' === $ext ) {
					$plugin_datas = get_file_data(
						$plugin_path . $file,
						array(
							'name'    => 'Plugin Name',
							'version' => 'Version',
						)
					);
					if ( array_key_exists( 'name', $plugin_datas ) && ! empty( $plugin_datas['name'] ) && array_key_exists( 'version', $plugin_datas ) && ! empty( $plugin_datas['version'] ) ) {
						$plugin_name    = $plugin_datas['name'];
						$plugin_ver_num = $plugin_datas['version'];
						break;
					}
				}
			}
		}

		wp_localize_script(
			$handle,
			'credit',
			array(
				'links'          => __( 'Various links of this plugin', 'disable-generate-thumbnails' ),
				'plugin_version' => __( 'Version:', 'disable-generate-thumbnails' ) . ' ' . $plugin_ver_num,
				/* translators: FAQ Link & Slug */
				'faq'            => sprintf( __( 'https://wordpress.org/plugins/%s/faq', 'disable-generate-thumbnails' ), $slug ),
				'support'        => 'https://wordpress.org/support/plugin/' . $slug,
				'review'         => 'https://wordpress.org/support/view/plugin-reviews/' . $slug,
				'translate'      => 'https://translate.wordpress.org/projects/wp-plugins/' . $slug,
				/* translators: Plugin translation link */
				'translate_text' => sprintf( __( 'Translations for %s', 'disable-generate-thumbnails' ), $plugin_name ),
				'facebook'       => 'https://www.facebook.com/katsushikawamori/',
				'twitter'        => 'https://twitter.com/dodesyo312',
				'youtube'        => 'https://www.youtube.com/channel/UC5zTLeyROkvZm86OgNRcb_w',
				'donate'         => __( 'https://shop.riverforest-wp.info/donate/', 'disable-generate-thumbnails' ),
				'donate_text'    => __( 'Please make a donation if you like my work or would like to further the development of this plugin.', 'disable-generate-thumbnails' ),
				'donate_button'  => __( 'Donate to this plugin &#187;', 'disable-generate-thumbnails' ),
			)
		);
	}
}

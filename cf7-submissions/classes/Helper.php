<?php
namespace Codexpert\CF7_Submissions;

/**
 * if accessed directly, exit.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @package Plugin
 * @subpackage Helper
 * @author Codexpert <hi@codexpert.io>
 */
class Helper {

	public static function pri( $data, $admin_only = true, $hide_adminbar = true ) {

		if( $admin_only && ! current_user_can( 'manage_options' ) ) return;

		echo '<pre>';
		if( is_object( $data ) || is_array( $data ) ) {
			print_r( $data );
		}
		else {
			var_dump( $data );
		}
		echo '</pre>';

		if( is_admin() && $hide_adminbar ) {
			echo '<style>#adminmenumain{display:none;}</style>';
		}
	}

	/**
	 * @param bool $show_cached either to use a cached list of posts or not. If enabled, make sure to wp_cache_delete() with the `save_post` hook
	 */
	public static function get_posts( $args = [], $show_heading = false, $show_cached = false ) {

		$defaults = [
			'post_type'         => 'post',
			'posts_per_page'    => -1,
			'post_status'		=> 'publish'
		];

		$_args = wp_parse_args( $args, $defaults );

		// use cache
		if( true === $show_cached && ( $cached_posts = wp_cache_get( "cf7sub_{$_args['post_type']}", 'cf7s' ) ) ) {
			$posts = $cached_posts;
		}

		// don't use cache
		else {
			$queried = new \WP_Query( $_args );

			$posts = [];
			foreach( $queried->posts as $post ) :
				$posts[ $post->ID ] = $post->post_title;
			endforeach;
			
			wp_cache_add( "cf7sub_{$_args['post_type']}", $posts, 'cf7s', 3600 );
		}

		// Translators: %s is the post type to choose from (e.g., post, page, etc.).
		$posts = $show_heading ? [ '' => sprintf( __( '- Choose a %s -', 'cf7s' ), $_args['post_type'] ) ] + $posts : $posts;

		return apply_filters( 'cf7sub_get_posts', $posts, $_args );
	}

	public static function get_option( $key, $section, $default = '', $repeater = false ) {

		$options = get_option( $key );

		if ( isset( $options[ $section ] ) ) {
			$option = $options[ $section ];

			if( $repeater === true ) {
				$_option = [];
				foreach ( $option as $key => $values ) {
					$index = 0;
					foreach ( $values as $value ) {
						$_option[ $index ][ $key ] = $value;
						$index++;
					}
				}

				return $_option;
			}
			
			return $option;
		}

		return $default;
	}

	/**
	 * Includes a template file resides in /views diretory
	 *
	 * It'll look into /cf7-submissions directory of your active theme
	 * first. if not found, default template will be used.
	 * can be overwriten with cf7-submissions_template_overwrite_dir hook
	 *
	 * @param string $slug slug of template. Ex: template-slug.php
	 * @param string $sub_dir sub-directory under base directory
	 * @param array $fields fields of the form
	 */
	public static function get_template( $slug, $base = 'views', $args = null, $echo = false ) {

		// templates can be placed in this directory
		$overwrite_template_dir = apply_filters( 'cf7sub_template_overwrite_dir', get_stylesheet_directory() . '/cf7-submissions/', $slug, $base, $args );
		
		// default template directory
		$plugin_template_dir = dirname( CF7S ) . "/{$base}/";

		// full path of a template file in plugin directory
		$plugin_template_path =  $plugin_template_dir . $slug . '.php';
		
		// full path of a template file in overwrite directory
		$overwrite_template_path =  $overwrite_template_dir . $slug . '.php';

		$content = '';

		// if template is found in overwrite directory
		if( file_exists( $overwrite_template_path ) ) {

			if ( false === $echo ) {
				ob_start();
				include $overwrite_template_path;
				return ob_get_clean();
			}
			else {
				include $overwrite_template_path;
			}
		}

		// otherwise use default one
		elseif ( file_exists( $plugin_template_path ) ) {

			if ( false === $echo ) {
				ob_start();
				include $plugin_template_path;
				return ob_get_clean();
			}
			else {
				include $plugin_template_path;
			}
		}

		else {
			if ( false === $echo ) {
				return esc_html__( 'Template not found!', 'cf7-submissions' );
			}
			else {
				esc_html( 'Template not found!', 'cf7-submissions' );
			}
		}
	}
}
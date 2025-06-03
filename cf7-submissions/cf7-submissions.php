<?php
/**
 * Plugin Name:			CF7 Submissions
 * Plugin URI:			https://pluggable.io/plugin/cf7-submissions
 * Description:			Securely Store and Manage Contact Form 7 Submissions Hassle-Free
 * Version:				0.26
 * Requires at least:	6.0
 * Requires PHP:		7.4
 * Author:				Codexpert, Inc
 * Author URI:			https://codexpert.io
 * License:				GPLv2 or later
 * License URI:			http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain:			cf7-submissions
 * Domain Path:			/languages
 */

/**
 * CF7 Submissions is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * CF7 Submissions is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 */

namespace Codexpert\CF7_Submissions;

use Codexpert\Plugin\Notice;
use Pluggable\Marketing\Survey;
use Pluggable\Marketing\Feature;
use Pluggable\Marketing\Deactivator;

/**
 * if accessed directly, exit.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main class for the plugin
 * @package Plugin
 * @author Codexpert <hi@codexpert.io>
 */
final class Plugin {

	public $plugin;
	
	/**
	 * Plugin instance
	 * 
	 * @access private
	 * 
	 * @var Plugin
	 */
	private static $_instance;

	/**
	 * The constructor method
	 * 
	 * @access private
	 * 
	 * @since 0.9
	 */
	private function __construct() {

		/**
		 * Includes required files
		 */
		$this->include();

		/**
		 * Defines contants
		 */
		$this->define();

		/**
		 * Runs actual hooks
		 */
		$this->hook();
	}

	/**
	 * Includes files
	 * 
	 * @access private
	 * 
	 * @uses composer
	 * @uses psr-4
	 */
	private function include() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		require_once( ABSPATH . 'wp-includes/PHPMailer/PHPMailer.php' );
		require_once( ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php' );
		require_once( ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php' );
		require_once( dirname( __FILE__ ) . '/vendor/autoload.php' );
	}

	/**
	 * Define variables and constants
	 * 
	 * @access private
	 * 
	 * @uses get_plugin_data
	 * @uses plugin_basename
	 */
	private function define() {

		/**
		 * Define some constants
		 * 
		 * @since 0.9
		 */
		define( 'CF7S', __FILE__ );
		define( 'CF7S_DIR', dirname( CF7S ) );
		define( 'CF7S_ASSET', plugins_url( 'assets', CF7S ) );
		define( 'CF7S_DEBUG', apply_filters( 'cf7-submissions_debug', true ) );

		/**
		 * The plugin data
		 * 
		 * @since 0.9
		 * @var $plugin
		 */
		$this->plugin					= get_plugin_data( CF7S, true, false );
		$this->plugin['basename']		= plugin_basename( CF7S );
		$this->plugin['file']			= CF7S;
		$this->plugin['server']			= apply_filters( 'cf7-submissions_server', 'https://codexpert.io/dashboard' );
		$this->plugin['min_php']		= '7.4';
		$this->plugin['min_wp']			= '6.0';
		$this->plugin['icon']			= CF7S_ASSET . '/img/icon.png';
		$this->plugin['depends']		= [ 'contact-form-7/wp-contact-form-7.php' => 'Contact Form 7' ];
		
		$this->plugin['hash_deactivator'] = 'fd149c48-25af-4612-8e6e-06496e983099';
		$this->plugin['hash_survey']      = '680b0b59-8acf-4c44-a020-ff106ba4c2df';
		$this->plugin['hash_wizard']      = '193060fe-c1f6-42ed-92f7-2b652bd75b0c';
	}

	/**
	 * Hooks
	 * 
	 * @access private
	 * 
	 * Executes main plugin features
	 *
	 * To add an action, use $instance->action()
	 * To apply a filter, use $instance->filter()
	 * To register a shortcode, use $instance->register()
	 * To add a hook for logged in users, use $instance->priv()
	 * To add a hook for non-logged in users, use $instance->nopriv()
	 * 
	 * @return void
	 */
	private function hook() {

		if( is_admin() ) :

			/**
			 * Admin facing hooks
			 */
			$admin = new App\Admin( $this->plugin );
			$admin->action( 'admin_footer', 'modal' );
			$admin->action( 'init', 'i18n' );
			$admin->action( 'admin_enqueue_scripts', 'enqueue_scripts' );
			$admin->filter( "plugin_action_links_contact-form-7/wp-contact-form-7.php", 'action_links' );
			$admin->filter( "plugin_action_links_{$this->plugin['basename']}", 'action_links' );
			$admin->action( 'admin_footer_text', 'footer_text' );
			$admin->filter( 'manage_toplevel_page_wpcf7_columns', 'add_table_column', 11 );
			$admin->filter( 'cx-plugin_table_row_class', 'table_row_class', 10, 2 );
			$admin->action( 'cx-plugin_tablenav', 'filter_buttons', 10, 2 );
			$admin->action( 'admin_menu', 'admin_menu' );
			$admin->action( 'admin_init', 'download' );

			/**
			 * Renders different notices
			 * 
			 * @package Codexpert\Plugin
			 * 
			 * @author Codexpert <hi@codexpert.io>
			 */
			$notice = new Notice( $this->plugin );

			/**
			 * Marketing Modules
			 * 
			 * @package Pluggable\Marketing
			 * 
			 * @author Pluggable <hi@pluggable.io>
			 */
			// new Feature( $this->plugin, [ 'reserved' => [] ] );
			new Survey( $this->plugin );
			new Deactivator( $this->plugin );

		else : // ! is_admin() ?

			/**
			 * Front facing hooks
			 */
			$front = new App\Front( $this->plugin );
			$front->action( 'wp_head', 'head' );
			$front->action( 'wp_footer', 'modal' );
			$front->action( 'wp_enqueue_scripts', 'enqueue_scripts' );
			$front->filter( 'wpcf7_form_hidden_fields', 'show_user_id' );

		endif;

		/**
		 * Installer facing hooks
		 */
		$installer = new App\Installer( $this->plugin );
		$installer->activate( 'install' );
		$installer->deactivate( 'uninstall' );

		/**
		 * Submission hooks
		 *
		 * Executes on both the admin area and front area
		 */
		$submission = new App\Submission( $this->plugin );
		$submission->action( 'wpcf7_mail_sent', 'store' );
		$submission->action( 'admin_init', 'delete' );
		$submission->action( 'admin_init', 'read_unread' );
		$submission->action( 'admin_init', 'bulk_action' );

		/**
		 * AJAX related hooks
		 */
		$ajax = new App\AJAX( $this->plugin );
		$ajax->priv( 'cf7s-contact', 'contact' );
	}

	/**
	 * Cloning is forbidden.
	 * 
	 * @access public
	 */
	public function __clone() { }

	/**
	 * Unserializing instances of this class is forbidden.
	 * 
	 * @access public
	 */
	public function __wakeup() { }

	/**
	 * Instantiate the plugin
	 * 
	 * @access public
	 * 
	 * @return $_instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
}

Plugin::instance();
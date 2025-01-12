<?php
namespace Codexpert\CF7_Submissions\App;

use Codexpert\Plugin\Base;
use Codexpert\CF7_Submissions\Database;

/**
 * if accessed directly, exit.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @package Plugin
 * @subpackage Installer
 * @author Codexpert <hi@codexpert.io>
 */
class Installer extends Base {

	public $plugin;
	public $slug;
	public $name;
	public $server;
	public $version;

	/**
	 * Constructor function
	 */
	public function __construct( $plugin ) {
		$this->plugin	= $plugin;
		$this->slug		= $this->plugin['TextDomain'];
		$this->name		= $this->plugin['Name'];
		$this->server	= $this->plugin['server'];
		$this->version	= $this->plugin['Version'];
	}

	/**
	 * Installer. Runs once when the plugin in activated.
	 *
	 * @since 1.0
	 */
	public function install() {

		if( ! get_option( 'cf7s-submissions_version' ) ){
			update_option( 'cf7s-submissions_version', $this->version );
		}
		
		if( ! get_option( 'cf7s-submissions_install_time' ) ){
			update_option( 'cf7s-submissions_install_time', time() );
		}

		/**
		 * Schedule an event
		 */
		if ( ! wp_next_scheduled( 'codexpert-daily' ) ) {
		    wp_schedule_event( time(), 'daily', 'codexpert-daily' );
		}

		$db = new Database;
		$db->create_tables();
	}

	/**
	 * Uninstaller. Runs once when the plugin in deactivated.
	 *
	 * @since 1.0
	 */
	public function uninstall() {
		/**
		 * Remove scheduled hooks
		 */
		wp_clear_scheduled_hook( 'codexpert-daily' );
	}
}
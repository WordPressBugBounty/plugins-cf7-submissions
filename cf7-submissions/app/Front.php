<?php
namespace Codexpert\CF7_Submissions\App;

use Codexpert\Plugin\Base;
use Codexpert\CF7_Submissions\Helper;

/**
 * if accessed directly, exit.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @package Plugin
 * @subpackage Front
 * @author Codexpert <hi@codexpert.io>
 */
class Front extends Base {

	public $plugin;
	public $slug;
	public $name;
	public $version;

	/**
	 * Constructor function
	 */
	public function __construct( $plugin ) {
		$this->plugin	= $plugin;
		$this->slug		= $this->plugin['TextDomain'];
		$this->name		= $this->plugin['Name'];
		$this->version	= $this->plugin['Version'];
	}

	public function head() {}
	
	/**
	 * Enqueue JavaScripts and stylesheets
	 */
	public function enqueue_scripts() {
		$min = defined( 'CF7S_DEBUG' ) && CF7S_DEBUG ? '' : '.min';

		wp_enqueue_style( $this->slug, plugins_url( "/assets/css/front{$min}.css", CF7S ), '', $this->version, 'all' );

		wp_enqueue_script( $this->slug, plugins_url( "/assets/js/front{$min}.js", CF7S ), [ 'jquery' ], $this->version, true );
		
		$localized = [
			'ajaxurl'	=> admin_url( 'admin-ajax.php' ),
			'cf7sub_nonce'	=> wp_create_nonce( 'cf7-submissions' ),
		];
		wp_localize_script( $this->slug, 'CF7S', apply_filters( "{$this->slug}-localized", $localized ) );
	}

	public function modal() {
		echo '
		<div id="cf7-submissions-modal" style="display: none">
			<img id="cf7-submissions-modal-loader" src="' . esc_attr( CF7S_ASSET . '/img/loader.gif' ) . '" />
		</div>';
	}

	public function show_user_id( $hidden_fields ) {
		$hidden_fields = array_merge( $hidden_fields, cf7sub_hidden_fields() );

		return $hidden_fields;
	}
}
<?php
namespace Codexpert\CF7_Submissions\App;

use Codexpert\Plugin\Base;

/**
 * if accessed directly, exit.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @package Plugin
 * @subpackage AJAX
 * @author Codexpert <hi@codexpert.io>
 */
class AJAX extends Base {

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

	public function contact() {

		if( ! wp_verify_nonce( $this->sanitize( $_REQUEST['cf7sub_nonce'] ), 'cf7-submissions' ) ) {
			wp_send_json_error( [
				'status'	=> 0,
				'message'	=> __( 'Unauthorized', 'cf7-submissions' ),
			], 401 );
		}

		if( ! isset( $_POST['to'] ) || '' == $_POST['to'] ) {
			wp_send_json_success( [
				'status'	=> 0,
				'message'	=> __( 'Failed. Please choose a recipient.', 'cf7-submissions' ),
			], 200 );
		}

		if( ! isset( $_POST['subject'] ) || '' == $_POST['subject'] ) {
			wp_send_json_success( [
				'status'	=> 0,
				'message'	=> __( 'Failed. Please add a subject.', 'cf7-submissions' ),
			], 200 );
		}

		if( ! isset( $_POST['message'] ) || '' == $_POST['message'] ) {
			wp_send_json_success( [
				'status'	=> 0,
				'message'	=> __( 'Failed. Please add a message.', 'cf7-submissions' ),
			], 200 );
		}

		$recipients	= $this->sanitize( $_POST['to'], 'array' );
		$subject	= $this->sanitize( $_POST['subject'] );
		$message	= $this->sanitize( $_POST['message'], 'textarea' );

		wp_mail(
			$recipients,
			$subject,
			$message,
		);

		wp_send_json_success( [
			'status'	=> 1,
			'message'	=> __( 'Email sent successfully', 'cf7-submissions' ),
		], 200 );
	}

}
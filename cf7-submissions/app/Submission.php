<?php
namespace Codexpert\CF7_Submissions\App;

use Codexpert\Plugin\Base;
use Codexpert\CF7_Submissions\Helper;
use Codexpert\CF7_Submissions\Database;
use Codexpert\CF7_Submissions\Filesystem;

/**
 * if accessed directly, exit.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @package Plugin
 * @subpackage Submission
 * @author Codexpert <hi@codexpert.io>
 */
class Submission extends Base {

	public $plugin;
	public $db;
	public $fs;
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

		$this->db	= new Database;
		$this->fs	= new Filesystem;
	}

	public function store( $mail ) {

		/**
		 * @todo
		 */
		if(
			! isset( $_POST['cf7sub_nonce'] )
			|| wp_verify_nonce( $this->sanitize( $_POST['cf7sub_nonce'] ), 'cf7-submissionss' )
		) return;

		$submission		= \WPCF7_Submission::get_instance();
		$posted_data	= $submission->get_posted_data();
		$uploaded_files	= $submission->uploaded_files();
		
		/**
		 * Insert the main submission
		 * 
		 * @return int $submission_id
		 * 
		 * @since 0.9
		 */
		$submission_id = $this->db->insert( 'submissions', [
			'form_id'	=> $this->sanitize( $_POST['_wpcf7'] ),
			'post_id'	=> $this->sanitize( $_POST['_wpcf7_container_post'] ),
			'user_id'	=> $this->sanitize( $_POST['cf7s-user_id'] ),
			'ip'		=> cf7sub_user_ip(),
			'fields'	=> count( $posted_data ),
			'files'		=> count( $uploaded_files ),
			'time'		=> date_i18n( 'U' )
		] );

		/**
		 * Insert submission data
		 * 
		 * @since 0.9
		 */
		foreach ( $posted_data as $key => $value ) {

			// if it's a `file`, don't insert in the data table
			if( ! array_key_exists( $key, cf7sub_hidden_fields() ) && ! array_key_exists( $key, $uploaded_files ) ) {
				$this->db->insert( 'submission_data', [
					'submission_id'	=> $submission_id,
					'field'			=> $key,
					'value'			=> $value
				] );
			}
		}

		$this->fs->prepare_dir( $dir = cf7sub_upload_dir() );

		/**
		 * Insert submission file data
		 * 
		 * @since 0.9
		 */
		foreach ( $uploaded_files as $key => $files ) {

			// loop through all the files
			// Makes it compatible with multi-file fields
			foreach ( $files as $file ) {

				// move the file to a separate directory
				$filename		= basename( $file );
				$new_filename	= $filename;

				if( file_exists( trailingslashit( $dir ) . $new_filename ) ) {
					$new_filename = wp_rand() . $filename;
				}

				if( $this->fs->copy( $file, trailingslashit( $dir ) . $new_filename, true ) ) {
					$this->db->insert( 'submission_files', [
						'submission_id'	=> $submission_id,
						'field'			=> $key,
						'name'			=> $filename,
						'path'			=> $new_filename,
						'type'			=> mime_content_type( $file ),
						'size'			=> $this->fs->size( $file )
					] );
				}
			}
		}
	}

	public function delete() {
		if(
			! isset( $_REQUEST['cf7sub_nonce'] )
			|| ! wp_verify_nonce( $this->sanitize( $_REQUEST['cf7sub_nonce'] ), 'cf7-submissions' )
			|| ! current_user_can( 'manage_options' )
			|| ! isset( $_GET['page'] )
			|| $_GET['page'] != $this->slug
			|| ! isset( $_GET['delete'] )
			|| $_GET['delete'] == ''
		) return;

		$submission_id = (int) $this->sanitize( $_GET['delete'] );

		$this->_delete( $submission_id );

		wp_safe_redirect( add_query_arg( [ 'page' => $this->slug ], admin_url( 'admin.php' ) ) );
	}

	/**
	 * Actually deletes a sumbission along with it data and files
	 */
	private function _delete( $submission_id ) {
		
		$dir = trailingslashit( cf7sub_upload_dir() );

		// delete files first
		$files = $this->db->list( 'submission_files', 'path', [ 'submission_id' => $submission_id ] );

		foreach ( $files as $file ) {
			$this->fs->delete( $dir . $file['path'] );
		}

		// delete db data
		$this->db->delete( 'submissions', [ 'id' => $submission_id ] );
		$this->db->delete( 'submission_data', [ 'submission_id' => $submission_id ] );
		$this->db->delete( 'submission_files', [ 'submission_id' => $submission_id ] );

	}

	public function read_unread() {
		if(
			! isset( $_REQUEST['cf7sub_nonce'] )
			|| ! wp_verify_nonce( $this->sanitize( $_REQUEST['cf7sub_nonce'] ), 'cf7-submissions' )
			|| ! current_user_can( 'manage_options' )
			|| ! isset( $_GET['page'] )
			|| $_GET['page'] != $this->slug
			|| ! ( isset( $_GET['read'] ) || isset( $_GET['unread'] ) )
		) return;

		$id	= isset( $_GET['unread'] ) ? (int) $this->sanitize( $_GET['unread'] ) : (int) $this->sanitize( $_GET['read'] );
		$seen = isset( $_GET['read'] ) ? 1 : 0;

		$this->db->update( 'submissions', [ 'seen' => $seen ], [ 'id' => $id ] );

		wp_safe_redirect( add_query_arg( [ 'page' => $this->slug ], admin_url( 'admin.php' ) ) );
	}

	public function bulk_action() {
		if(
			! isset( $_REQUEST['cf7sub_nonce'] )
			|| ! wp_verify_nonce( $this->sanitize( $_REQUEST['cf7sub_nonce'] ), 'cf7-submissions' )
			|| ! current_user_can( 'manage_options' )
			|| ! isset( $_POST['action'] )
			|| ! isset( $_GET['page'] )
			|| $_GET['page'] != $this->slug
		) return;

		if( ! isset( $_POST['ids'] ) ) return;

		// prepare the list
		$ids = array_map( function( $id ) {
			return (int) str_replace( '#', '', $id );
		}, $this->sanitize( $_POST['ids'], 'array' ) );

		if( $_POST['action'] == 'read' ) {
			foreach ( $ids as $id ) {
				$this->db->update( 'submissions', [ 'seen' => 1 ], [ 'id' => $id ] );
			}
		}
		elseif( $_POST['action'] == 'unread' ) {
			foreach ( $ids as $id ) {
				$this->db->update( 'submissions', [ 'seen' => 0 ], [ 'id' => $id ] );
			}
		}
		elseif( $_POST['action'] == 'delete' ) {
			foreach ( $ids as $id ) {
				$this->_delete( $id );
			}
		}
	}
}
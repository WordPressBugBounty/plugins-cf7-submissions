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
 * @subpackage Admin
 * @author Codexpert <hi@codexpert.io>
 */
class Admin extends Base {

	public $plugin;
	public $db;
	public $fs;
	public $slug;
	public $name;
	public $server;
	public $version;
	public $admin_url;

	/**
	 * Constructor function
	 */
	public function __construct( $plugin ) {
		$this->plugin	= $plugin;
		$this->slug		= $this->plugin['TextDomain'];
		$this->name		= $this->plugin['Name'];
		$this->server	= $this->plugin['server'];
		$this->version	= $this->plugin['Version'];

		$this->db	= new Database;
		$this->fs	= new Filesystem;
	}

	/**
	 * Internationalization
	 */
	public function i18n() {
		load_plugin_textdomain( 'cf7-submissions', false, CF7S_DIR . '/languages/' );
	}

	/**
	 * Enqueue JavaScripts and stylesheets
	 */
	public function enqueue_scripts() {
		$min = defined( 'CF7S_DEBUG' ) && CF7S_DEBUG ? '' : '.min';
		
		wp_enqueue_script( 'chosen', plugins_url( "assets/js/chosen.jquery.min.js", CF7S ), [ 'jquery' ], '1.8.7' );
		wp_enqueue_style( 'chosen', plugins_url( "assets/css/chosen.min.css", CF7S ), '', '1.8.7' );
		
		wp_enqueue_style( $this->slug, plugins_url( "assets/css/admin{$min}.css", CF7S ), '', $this->version, 'all' );

		wp_enqueue_script( $this->slug, plugins_url( "assets/js/admin{$min}.js", CF7S ), [ 'jquery' ], $this->version, true );
		
		$localized = [
			'ajaxurl'		=> admin_url( 'admin-ajax.php' ),
			'cf7sub_nonce'		=> wp_create_nonce( 'cf7-submissions' ),
			'confirm'		=> esc_html__( 'Are you sure you want to delete this? The data and its associated files will be completely erased. This action cannot be undone!', 'cf7-submissions' ),
			'confirm_all'	=> esc_html__( 'Are you sure you want to delete these? The data and their associated files will be completely erased. This action cannot be undone!', 'cf7-submissions' ),
			'submissions'	=> cf7sub_submission_counts(),
		];
		wp_localize_script( $this->slug, 'CF7S', apply_filters( "{$this->slug}-localized", $localized ) );
	}

	public function action_links( $links ) {
		$this->admin_url = admin_url( 'admin.php' );

		$new_links = [
			'settings'	=> sprintf( '<a href="%1$s" style="font-weight: bold">' . esc_html__( 'Submissions', 'cx-plugin' ) . '</a>', esc_url( add_query_arg( 'page', $this->slug, $this->admin_url ) ) )
		];
		
		return array_merge( $new_links, $links );
	}

	public function footer_text( $text ) {
		if( get_current_screen()->parent_base != $this->slug ) return $text;

		// Translators: %1$s is a heart symbol, %2$s is a URL to Codexpert, Inc.
		return sprintf( esc_html__( 'Built with %1$s by the folks at <a href="%2$s" target="_blank">Codexpert, Inc</a>.' ), '&hearts;', 'https://codexpert.io' );
	}

	public function modal() {
		echo '
		<div id="cf7-submissions-modal" style="display: none">
			<img id="cf7-submissions-modal-loader" src="' . esc_attr( CF7S_ASSET . '/img/loader.gif' ) . '" />
		</div>';
	}

	public function add_table_column( $columns ) {

		if( ! isset( $columns['author'] ) ) return $columns;

		$author	= $columns['author'];
		$date	= $columns['date'];

		unset( $columns['author'] );
		unset( $columns['date'] );

		$columns['submissions'] = esc_html__( 'Submissions', 'cf7-submissions' );
		$columns['author']		= $author;
		$columns['date']		= $date;

		return $columns;
	}
	
	public function admin_menu() {
		add_submenu_page(
			'wpcf7',
			esc_html__( 'Submissions', 'cf7-submissions' ),
			esc_html__( 'Submissions', 'cf7-submissions' ),
			'manage_options',
			$this->slug,
			function() {

				printf( '<div class="wrap metabox-holder">' );

				// single submission
				if( isset( $_GET['view'] ) && '' != $_GET['view'] ) {

					printf(
						'<h2>%1$s #%2$d <a class="page-title-action" href="%4$s">%3$s</a></h2>',
						esc_html__( 'Submission', 'cf7-submissions' ),
						$this->sanitize( $_GET['view'] ),
						esc_html__( 'All Submissions', 'cf7-submissions' ),
						esc_url( add_query_arg( 'page', $this->slug, admin_url( 'admin.php' ) ) )
					);

					Helper::get_template( 'single', 'views/submissions', [], true );
				}

				// submission archive
				else {

					printf( '<h2>%s</h2>', esc_html__( 'Submissions', 'cf7-submissions' ) );
					
					Helper::get_template( 'list', 'views/submissions', [], true );
				}
				
				printf( '</div><!-- .wrap.metabox-holder -->' );
			}
		);
	}

	public function download() {
		if( 
			! isset( $_REQUEST['cf7sub_nonce'] )
			|| ! wp_verify_nonce( $this->sanitize( $_REQUEST['cf7sub_nonce'] ), 'cf7-submissions' )
			|| ! isset( $_GET['page'] )
			|| $_GET['page'] != 'cf7-submissions'
			|| ! isset( $_GET['download'] )
			|| is_null( $file_id = $this->sanitize( $_GET['download'] ) )
			|| ! current_user_can( 'manage_options' )
		) return;

		$files = $this->db->list( 'submission_files', 'name,path', [ 'id' => (int) $file_id ] );

		if( count( $files ) <= 0 ) return;

		$file = $files[0];
		$file_path = trailingslashit( cf7sub_upload_dir() ) . $file['path'];

		if ( file_exists( $file_path ) ) {

		    // Set headers to force file download
		    header( 'Content-Description: File Transfer' );
		    header( 'Content-Type: application/octet-stream' );
		    header( 'Content-Disposition: attachment; filename="' . $file['name'] . '"' );
		    header( 'Expires: 0' );
		    header( 'Cache-Control: must-revalidate' );
		    header( 'Pragma: public' );
		    header( 'Content-Length: ' . filesize( $file_path ) );

		    // Read the file and output its contents
		    exit( $this->fs->get_contents( esc_url( $file_path ) ) );
		}
	}

	public function table_row_class( $classes, $item ) {
		
		$classes .= $item['seen'] == 0 ? ' unseen' : ' seen';

		return $classes;
	}

	public function filter_buttons( $config, $which ) {
		if( ! isset( $config['id'] ) || $config['id'] != 'cf7-submissions_list' || $which == 'bottom' ) return;

		esc_html( wp_nonce_field( 'cf7-submissions', 'cf7sub_nonce' ) );

		printf( '<div class="alignleft">' );

		// filter by form
		printf(
			'<select class="" name="form">
				<option value="">%1$s</option>', esc_html__( 'Select a Form', 'cf7-submissions' ) );

		foreach ( Helper::get_posts( [ 'post_type' => 'wpcf7_contact_form' ] ) as $id => $title ) {
			printf(
				'<option value="%2$d" %3$s>%1$s</option>',
				esc_html( $title ),
				esc_html( $id ),
				( isset( $_REQUEST['form'] ) ? selected( $this->sanitize( $_REQUEST['form'] ), $id ) : '' )
			);
		}

		printf( '</select>' );

		// filter by container
		printf(
			'<select class="" name="container">
				<option value="">%1$s</option>', esc_html__( 'Select a Container', 'cf7-submissions' ) );

		foreach ( wp_list_pluck( $this->db->list( 'submissions', 'DISTINCT(post_id)' ), 'post_id' ) as $post_id ) {
			printf(
				'<option value="%2$d" %3$s>%1$s</option>',
				esc_html( get_the_title( $post_id ) ),
				esc_html( $post_id ),
				( isset( $_REQUEST['container'] ) ? selected( $this->sanitize( $_REQUEST['container'] ), $post_id ) : '' )
			);
		}

		printf( '</select>' );

		// filter by user
		printf(
			'<select class="" name="user">
				<option value="">%1$s</option>',
				esc_html__( 'Select a User', 'cf7-submissions' )
		);

		foreach ( wp_list_pluck( $this->db->list( 'submissions', 'DISTINCT(user_id)' ), 'user_id' ) as $user_id ) {
			printf(
				'<option value="%2$d" %3$s>%1$s</option>',
				$user_id != 0 ? esc_html( get_userdata( $user_id )->display_name ) : esc_html__( 'Guest Users', 'cf7-submissions' ),
				esc_html( $user_id ),
				( isset( $_REQUEST['user'] ) ? selected( $this->sanitize( $_REQUEST['user'] ), $user_id ) : '' )
			);
		}

		printf( '</select>' );

		// filter by seen status
		printf(
			'<select class="" name="status">
				<option value="">%1$s</option>', esc_html__( 'Select Status', 'cf7-submissions' ) );

		foreach ( [ 0 => esc_html__( 'Unseen', 'cf7-submissions' ), 1 => esc_html__( 'Seen', 'cf7-submissions' ) ] as $id => $title ) {
			printf(
				'<option value="%2$d" %3$s>%1$s</option>',
				esc_html( $title ),
				esc_html( $id ),
				( isset( $_REQUEST['status'] ) ? selected( $this->sanitize( $_REQUEST['status'] ), $id ) : '' )
			);
		}

		printf( '</select>' );


		printf( ' <input type="submit" class="button button-primary action" value="%s">', esc_html__( 'Filter Submissions', 'cf7-submissions' ) );
		printf( '</div>' );
	}
}
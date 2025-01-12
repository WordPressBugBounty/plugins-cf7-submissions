<?php
use Codexpert\Plugin\Table;
use Codexpert\CF7_Submissions\Helper;
use Codexpert\CF7_Submissions\Database;

/**
 * if accessed directly, exit.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if(
	isset( $_REQUEST['cf7sub_nonce'] )
	&& ! wp_verify_nonce( sanitize_text_field( $_REQUEST['cf7sub_nonce'] ), 'cf7-submissions' )
) return;

$db = new Database;

$args = [];

if( isset( $_REQUEST['form'] ) && $_REQUEST['form'] != '' ) {
	$args['form_id'] = (int) sanitize_text_field( $_REQUEST['form'] );
}

if( isset( $_REQUEST['status'] ) && $_REQUEST['status'] != '' ) {
	$args['seen'] = (int) sanitize_text_field( $_REQUEST['status'] );
}

if( isset( $_REQUEST['container'] ) && $_REQUEST['container'] != '' ) {
	$args['post_id'] = (int) sanitize_text_field( $_REQUEST['container'] );
}

if( isset( $_REQUEST['user'] ) && $_REQUEST['user'] != '' ) {
	$args['user_id'] = (int) sanitize_text_field( $_REQUEST['user'] );
}

$data = $db->list( 'submissions', '*', $args );

/**
 * Format values where required
 * 
 * @uses array_map()
 */
$cf7sub_nonce	= wp_create_nonce( 'cf7-submissions' );
$format		= get_option( 'links_updated_date_format' );
$admin_url	= admin_url( 'admin.php' );

$data = array_map( function( $row ) use ( $cf7sub_nonce, $format, $admin_url ) {
	
	$row_id = $row['id'];

	$row['id']			= sprintf(
		'<a href=\'%2$s\'>#%1$d</a>',
		$row_id,
		esc_url( add_query_arg( [ 'page' => 'cf7-submissions', 'view' => $row_id, 'cf7sub_nonce' => $cf7sub_nonce ] ) )
	);

	$row['user']		= sprintf(
		'<a href="%2$s">%1$s</a>',
		$row['user_id'] != 0 ? esc_html( get_userdata( $row['user_id'] )->display_name ) : esc_html__( 'Guest User', 'cf7-submissions' ),
		esc_url( add_query_arg( [ 'page' => 'cf7-submissions', 'user' => $row['user_id'], 'cf7sub_nonce' => $cf7sub_nonce ], $admin_url ) )
	);

	$row['form']		= sprintf(
		'<a href="%2$s">%1$s</a>',
		esc_html( get_the_title( $row['form_id'] ) ),
		esc_url( add_query_arg( [ 'page' => 'cf7-submissions', 'form' => $row['form_id'], 'cf7sub_nonce' => $cf7sub_nonce ], $admin_url ) )
	);

	$row['container']	= sprintf(
		'<a href="%2$s">%1$s</a>',
		esc_html( get_the_title( $row['post_id'] ) ),
		esc_url( add_query_arg( [ 'page' => 'cf7-submissions', 'container' => $row['post_id'], 'cf7sub_nonce' => $cf7sub_nonce ], $admin_url ) )
	);
	
	$row['time']		= date_i18n( $format, $row['time'] );

	$row['action']	= sprintf(
		'<div>
			<a href="%1$s">%2$s</a> | <a href="%3$s">%4$s</a> | <a href="%5$s" class="cf7s-delete">%6$s</a>
		</div>',
		esc_url( add_query_arg( [ 'page' => 'cf7-submissions', 'view' => $row_id, 'cf7sub_nonce' => $cf7sub_nonce ] ) ),
		esc_html__( 'View', 'cf7-submissions' ),
		$row['seen'] == 1 ? esc_url( add_query_arg( [ 'page' => 'cf7-submissions', 'unread' => $row_id, 'cf7sub_nonce' => $cf7sub_nonce ] ) ) : esc_url( add_query_arg( [ 'page' => 'cf7-submissions', 'read' => $row_id, 'cf7sub_nonce' => $cf7sub_nonce ] ) ),
		$row['seen'] == 1 ? esc_html__( 'Mark Unread', 'cf7-submissions' ) : esc_html__( 'Mark Read', 'cf7-submissions' ),
		esc_url( add_query_arg( [ 'page' => 'cf7-submissions', 'delete' => $row_id, 'cf7sub_nonce' => $cf7sub_nonce ] ) ),
		esc_html__( 'Delete', 'cf7-submissions' ),
	);

	return $row;
}, $data );

/**
 * Construct the config list
 */
$config = [
	'id'			=> 'cf7-submissions_list',
	'per_page'		=> 20,
	'columns'		=> [
		'id'		=> esc_html__( 'Submission ID', 'cf7-submissions' ),
		'user'		=> esc_html__( 'Submitted By', 'cf7-submissions' ),
		'form'		=> esc_html__( 'Form', 'cf7-submissions' ),
		'container'	=> esc_html__( 'Container', 'cf7-submissions' ),
		'time'		=> esc_html__( 'Date &amp; Time', 'cf7-submissions' ),
		'action'	=> '',
	],
	'sortable'		=> [ 'id', 'user', 'form', 'container', 'time' ],
	'orderby'		=> 'time',
	'order'			=> 'desc',
	'data'			=> $data,
	'bulk_actions'	=> [
		'read'		=> esc_html__( 'Mark Read', 'cf7-submissions' ),
		'unread'	=> esc_html__( 'Mark Unread', 'cf7-submissions' ),
		'delete'	=> esc_html__( 'Delete', 'cf7-submissions' ),
	],
];

$table = new Table( $config );
echo '<form method="post" id="cf7s-list-form">';
$table->prepare_items();
$table->display();
echo '</form>';
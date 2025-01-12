<?php
use Codexpert\CF7_Submissions\Database;
use Codexpert\CF7_Submissions\Helper;

if( ! function_exists( 'get_plugin_data' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
}

/**
 * Gets the site's base URL
 * 
 * @uses get_bloginfo()
 * 
 * @return string $url the site URL
 */
if( ! function_exists( 'cf7sub_site_url' ) ) :
function cf7sub_site_url() {
	$url = get_bloginfo( 'url' );

	return $url;
}
endif;

if( ! function_exists( 'cf7sub_upload_dir' ) ) :
function cf7sub_upload_dir() {
    $dir = trailingslashit( wp_upload_dir()['basedir'] ) . 'cf7-submissions';

    return $dir;
}
endif;

if( ! function_exists( 'cf7sub_hidden_fields' ) ) :
function cf7sub_hidden_fields() {
    return [
        'cf7s-user_id'  => get_current_user_id(),
        'cf7sub_nonce'     => wp_create_nonce( 'cf7-submissions' )
    ];
}
endif;

if( ! function_exists( 'cf7sub_submission_counts' ) ) :
function cf7sub_submission_counts() {
    $db = new Database;

    $counts = $list = [];
    
    foreach ( $db->list( 'submissions' ) as $submission ) {
        if( ! isset( $counts[ $submission['form_id'] ] ) ) {
            $counts[ $submission['form_id'] ] = 0;
        }
        $counts[ $submission['form_id'] ]++;
    }

    foreach ( Helper::get_posts( [ 'post_type' => 'wpcf7_contact_form' ] ) as $form_id => $title ) {
        $count = isset( $counts[ $form_id ] ) ? $counts[ $form_id ] : 0;
        
        $list[ $form_id ] = sprintf(
            '<a href="%3$s">%1$d %2$s<a/>',
            $count,
            _n( 'submission', 'submissions', $count, 'cf7-submissions' ),
            esc_url( add_query_arg( [ 'page' => 'cf7-submissions', 'form' => $form_id ], admin_url( 'admin.php' ) ) ),
        );
    }

    return $list;
}
endif;

if( ! function_exists( 'cf7sub_user_ip' ) ) :
function cf7sub_user_ip() {

    $ip = '';

    if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
        $ip = sanitize_text_field( $_SERVER['HTTP_CLIENT_IP'] );
    }
    elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
        $ip = sanitize_text_field( $_SERVER['HTTP_X_FORWARDED_FOR'] );
    }
    else {
        $ip = sanitize_text_field( $_SERVER['REMOTE_ADDR'] );
    }

    return $ip;
}
endif;

/**
 * Converts byte value to human readable format
 * 
 * @link https://stackoverflow.com/a/11860664
 */
if( ! function_exists( 'cf7sub_size_format' ) ) :
function cf7sub_size_format( $size ) { 
    $units = [ 'B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB' ];
    $power = $size > 0 ? floor( log( $size, 1024 ) ) : 0;
    
    return number_format( $size / pow( 1024, $power ), 2, '.', ',' ) . ' ' . $units[ $power ];
}
endif;

/**
 * Previous submission ID
 * 
 * @return int|bool The ID or false if not found
 */
if( ! function_exists( 'cf7sub_prev_submission' ) ) :
function cf7sub_prev_submission( $current_id ) { 
    
    $db = new Database;
    $query = $db->run( "SELECT MAX(id) as prev FROM `{$db->prefix}submissions` WHERE id < $current_id;" );

    return isset( $query[0] ) && isset( $query[0]->prev ) ? (int) $query[0]->prev : false;
}
endif;

/**
 * Next submission ID
 * 
 * @return int|bool The ID or false if not found
 */
if( ! function_exists( 'cf7sub_next_submission' ) ) :
function cf7sub_next_submission( $current_id ) { 
    
    $db = new Database;
    $query = $db->run( "SELECT MIN(id) as next FROM `{$db->prefix}submissions` WHERE id > $current_id;" );

    return isset( $query[0] ) && isset( $query[0]->next ) ? (int) $query[0]->next : false;
}
endif;
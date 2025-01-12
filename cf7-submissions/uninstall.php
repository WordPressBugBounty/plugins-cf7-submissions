<?php
// If uninstall is not called, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

$deletable_options = [ 'cf7s-submissions_version', 'cf7s-submissions_install_time' ];
foreach ( $deletable_options as $option ) {
    delete_option( $option );
}
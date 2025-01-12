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
 * @subpackage Filesystem
 * @author Codexpert <hi@codexpert.io>
 */
class Filesystem extends \WP_Filesystem_Direct {

	/**
	 * Constructor function
	 */
	public function __construct() {}

	public function prepare_dir( $dir ) {
		
		$htaccess_file = path_join( $dir, '.htaccess' );

		if( $this->exists( $htaccess_file ) ) return;
		
		$this->mkdir( $dir, 0755 );

		if ( $this->is_dir( $dir ) && $this->is_writable( $dir ) ) {

			$this->put_contents(
				$htaccess_file,
				'# Apache 2.4+
				<IfModule authz_core_module>
				    Require all denied
				</IfModule>

				# Apache 2.2
				<IfModule !authz_core_module>
				    Deny from all
				</IfModule>',
				0644
			);
		}
	}

}
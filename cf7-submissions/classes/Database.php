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
 * @subpackage Database
 * @author Codexpert <hi@codexpert.io>
 */
class Database {

	public $db;

	public $prefix;

	public $collate;

	/**
	 * Constructor function
	 */
	public function __construct() {
		
		global $wpdb;

		$this->db		= $wpdb;
		$this->prefix	= $this->db->prefix . 'cf7_';
		$this->collate	= $this->db->get_charset_collate();
	}

	public function create_tables() {

		/**
		 * Submissions table
		 */
		$submissions_sql = "CREATE TABLE `{$this->prefix}submissions` (
			id bigint NOT NULL AUTO_INCREMENT,
			form_id bigint NOT NULL,
			post_id bigint DEFAULT 0 NOT NULL,
			user_id mediumint(9) DEFAULT 0 NOT NULL,
			fields smallint(3) NOT NULL,
			files smallint(3) NOT NULL,
			ip varchar(15) DEFAULT '0.0.0.0' NOT NULL,
			seen tinyint(2) DEFAULT 0 NOT NULL,
			time int(10) NOT NULL,
			PRIMARY KEY  (id)
		) $this->collate;";

		$this->exec( $submissions_sql );

		/**
		 * Submission meta table
		 */
		$submission_data_sql = "CREATE TABLE `{$this->prefix}submission_data` (
			id bigint NOT NULL AUTO_INCREMENT,
			submission_id bigint NOT NULL,
			field varchar(255) NOT NULL,
			value longtext NOT NULL,
			PRIMARY KEY  (id)
		) $this->collate;";

		$this->exec( $submission_data_sql );

		/**
		 * Submission meta table
		 */
		$submission_files_sql = "CREATE TABLE `{$this->prefix}submission_files` (
			id bigint NOT NULL AUTO_INCREMENT,
			submission_id bigint NOT NULL,
			field varchar(255) NOT NULL,
			name varchar(255) NOT NULL,
			path text NOT NULL,
			type varchar(255) NOT NULL,
			size bigint NOT NULL,
			PRIMARY KEY  (id)
		) $this->collate;";

		$this->exec( $submission_files_sql );
	}

	public function exec( $query ) {
		dbDelta( $query );
	}

	public function insert( $table, $data = [], $format = [] ) {

		if( $data == [] ) return;

		$this->db->insert(
			$this->prefix . $table,
			$data,
			$format
		);

		return $this->get_last_id();
	}

	public function delete( $table, $where = [], $format = [] ) {

		if( $where == [] ) return;

		$this->db->delete(
			$this->prefix . $table,
			$where,
			$format
		);
	}

	public function update( $table, $data = [], $where = [] ) {

		if( $data == [] ) return;

		$this->db->update(
			$this->prefix . $table,
			$data,
			$where
		);
	}

	public function list( $table, $select = '*', $where = [] ) {
		
		$sql = "SELECT {$select} FROM `{$this->prefix}{$table}` WHERE 1 = 1";

		if( is_array( $where ) && count( $where ) > 0 ) {
			foreach ( $where as $key => $value ) {
				$sql .= " AND {$key} = {$value}";
			}
		}

		return $this->db->get_results( $sql, ARRAY_A );
	}

	public function run( $sql ) {
		return $this->db->get_results( $sql );
	}

	public function get_last_id() {
		return $this->db->insert_id;
	}
}
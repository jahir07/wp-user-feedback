<?php
/**
 * Handles database operations for the plugin.
 *
 * This class is responsible for managing all database interactions
 * related to the WPUserFeedback plugin, including inserting and retrieving feedback entries.
 *
 * @package WPUserFeedback
 * @since   1.0
 */

namespace WPUserFeedback;

use wpdb;
use WP_Error;
/**
 * Class Database
 */
class Database {

	/**
	 * WordPress database instance.
	 *
	 * @var \wpdb
	 */
	protected $db;

	/**
	 * Fully-qualified table name (with blog prefix).
	 *
	 * @var string
	 */
	protected $table;

	/**
	 * Initilize the class.
	 *
	 * @return void
	 */
	public function __construct() {
		global $wpdb;
		$this->db    = $wpdb;
		$this->table = $wpdb->prefix . 'wpuserfeedback';
	}

	/**
	 * Insert a single feedback row.
	 *
	 * @param string $first_name Person's first name. Required.
	 * @param string $last_name  Person's last name. Required.
	 * @param string $email      Email address. Required.
	 * @param string $subject    Subject line. Required.
	 * @param string $message    Message body. Required.
	 * @return int|WP_Error Inserted row ID on success, or WP_Error on failure.
	 */
	public function do_insert( $first_name, $last_name, $email, $subject, $message ) {
		// Bail early if any required field missing.
		if ( empty( $first_name ) || empty( $last_name ) || empty( $email ) || empty( $subject ) || empty( $message ) ) {
			return new \WP_Error( 'missing_fields', __( 'All fields are required.', 'wpuserfeedback' ) );
		}

		$data = array(
			'first_name'   => sanitize_text_field( $first_name ),
			'last_name'    => sanitize_text_field( $last_name ),
			'email'        => sanitize_email( $email ),
			'subject'      => sanitize_text_field( $subject ),
			'message'      => wp_kses_post( $message ),
			'date_created' => current_time( 'mysql' ),
			'date_updated' => current_time( 'mysql' ),
		);

		$formats = array( '%s', '%s', '%s', '%s', '%s', '%s', '%s' );

		$inserted = $this->db->insert( $this->table, $data, $formats );

		if ( false === $inserted ) {
			return new \WP_Error(
				'db_insert_error',
				__( 'Could not insert feedback into database.', 'wpuserfeedback' ),
				$this->db->last_error
			);
		}

		return (int) $this->db->insert_id;
	}

	/**
	 * Get feedback results with pagination.
	 *
	 * @param int $items_per_page Number of items per page.
	 * @param int $page           Page number (1-based).
	 * @return array Array of result objects.
	 */
	public function get_results( $items_per_page = 10, $page = 1 ) {
		$items_per_page = absint( $items_per_page );
		$page           = absint( $page );

		if ( $items_per_page < 1 || $page < 1 ) {
			return array();
		}

		$offset  = ( $page - 1 ) * $items_per_page;
		$query   = $this->db->prepare(
			"SELECT * FROM {$this->table} ORDER BY id DESC LIMIT %d, %d",
			$offset,
			$items_per_page
		);
		$results = $this->db->get_results( $query );

		return $results ? $results : array();
	}

	/**
	 * Get a single feedback row by ID.
	 *
	 * @param int $id Feedback entry ID.
	 * @return object|false Feedback object on success, false if not found.
	 */
	public function get_result_by_id( $id ) {
		$id = absint( $id );
		if ( $id < 1 ) {
			return false;
		}

		$query  = $this->db->prepare( "SELECT * FROM {$this->table} WHERE id = %d LIMIT 1", $id );
		$result = $this->db->get_row( $query );

		return $result ? $result : false;
	}

	/**
	 * Count total feedback rows.
	 *
	 * @return int Total row count.
	 */
	public function count_total() {
		$total = $this->db->get_var( "SELECT COUNT(*) FROM {$this->table}" );

		return (int) $total;
	}
}

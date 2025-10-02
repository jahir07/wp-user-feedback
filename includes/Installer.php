<?php
/**
 * Installer class.
 *
 * Handles the installation process of the WPUserFeedback plugin,
 * including creation of necessary database tables.
 *
 * @package WPUserFeedback
 * @since   1.0.0
 */

namespace WPUserFeedback;

/**
 * Class Installer
 */
class Installer {


	/**
	 * Run the installer.
	 *
	 * Creates required database tables.
	 *
	 * @return void
	 */
	public function do_install(): void {
		$this->create_tables();
	}

	/**
	 * Create all necessary tables for the plugin.
	 *
	 * @return void
	 */
	protected function create_tables(): void {
		include_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$this->create_feedback_table();
	}

	/**
	 * Create the feedback table.
	 *
	 * Table columns:
	 * - id (primary key)
	 * - first_name
	 * - last_name
	 * - email
	 * - subject
	 * - message
	 * - date_created
	 * - date_updated
	 *
	 * @global \wpdb $wpdb WordPress database abstraction object.
	 * @return void
	 */
	protected function create_feedback_table(): void {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'wpuserfeedback';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			first_name VARCHAR(55) NOT NULL,
			last_name VARCHAR(55) NOT NULL,
			email VARCHAR(100) NOT NULL,
			subject VARCHAR(255) NOT NULL,
			message TEXT NOT NULL,
			date_created DATETIME NOT NULL,
			date_updated DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY date_updated (date_updated)
		) {$charset_collate};";

		dbDelta( $sql );
	}
}

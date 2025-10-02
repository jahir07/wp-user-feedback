<?php
/**
 * Handles Admin functionality for the plugin.
 *
 * This class defines all code necessary to run vue settings page.
 *
 * @package WPUserFeedback
 * @since   1.0
 */

namespace WPUserFeedback;

defined( 'ABSPATH' ) || exit;

/**
 * Class Admin
 *
 * @since 1.0
 */
class Admin {

	/** The main admin page slug */
	const SLUG = 'wp-user-feedback';

	/**
	 * Summary of hooks
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );

		// REST endpoints.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Register admin menu and submenus.
	 *
	 * @return void
	 */
	public function register_menu(): void {
		add_menu_page(
			__( 'User Feedback', 'wpuserfeedback' ),
			__( 'User Feedback', 'wpuserfeedback' ),
			'manage_options',
			self::SLUG,
			array( $this, 'render_app' ),
			'dashicons-feedback',
			56
		);

		add_submenu_page(
			self::SLUG,
			__( 'All Feedback', 'wpuserfeedback' ),
			__( 'All Feedback', 'wpuserfeedback' ),
			'manage_options',
			self::SLUG,
			array( $this, 'render_app' )
		);

		add_submenu_page(
			self::SLUG,
			__( 'Info & Shortcodes', 'wpuserfeedback' ),
			__( 'Info', 'wpuserfeedback' ),
			'manage_options',
			self::SLUG . '-info',
			array( $this, 'render_app' )
		);
	}

	/**
	 * Render the admin app container.
	 *
	 * @return void
	 */
	public function render_app(): void {
		echo '<div id="wpuf-admin-app"></div>';
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @return void
	 */
	public function enqueue(): void {
		$screen = get_current_screen();
		if ( ! $screen || strpos( (string) $screen->base, self::SLUG ) === false ) {
			return;
		}

		$asset = include plugin_dir_path( __FILE__ ) . '../assets/admin/index.asset.php';

		wp_enqueue_script(
			'wpuf-admin',
			plugins_url( '../assets/admin/index.js', __FILE__ ),
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_enqueue_style(
			'wpuf-admin',
			plugins_url( '../assets/admin/index.css', __FILE__ ),
			array(),
			$asset['version']
		);

		wp_add_inline_script(
			'wpuf-admin',
			'window.WPUF_ADMIN = ' . wp_json_encode(
				array(
					'root'  => esc_url_raw( rest_url( 'wpuf/v1/' ) ),
					'nonce' => wp_create_nonce( 'wp_rest' ),
					'cap'   => current_user_can( 'manage_options' ),
					'i18n'  => array( 'areYouSure' => __( 'Are you sure?', 'wpuserfeedback' ) ),
				)
			),
			'before'
		);
	}

	/** REST: /wp-json/wpuf/v1/feedback */
	public function register_rest_routes(): void {
		register_rest_route(
			'wpuf/v1',
			'/feedback',
			array(
				'methods'             => 'GET',
				'permission_callback' => array( $this, 'can_manage' ),
				'callback'            => function ( \WP_REST_Request $req ) {
					$page_param = (int) $req->get_param( 'page' );
					$page = $page_param > 0 ? $page_param : 1;

					$per_page_param = (int) $req->get_param( 'per_page' );
					$per = $per_page_param > 0 ? $per_page_param : 10;
					$per = max( 1, min( 100, $per ) );

					$db = new Database();
					$total = (int) $db->count_total();
					$rows = $db->get_results( $per, $page );
					$rows = ! empty( $rows ) ? $rows : array();

					return rest_ensure_response(
						array(
							'items' => array_map(
								static function ( $r ) {
									return (array) $r;
								},
								$rows
							),
							'total' => $total,
						)
					);
				},
			)
		);

		register_rest_route(
			'wpuf/v1',
			'/feedback/(?P<id>\d+)',
			array(
				'methods'             => 'POST',
				'permission_callback' => array( $this, 'can_manage' ),
				'callback'            => function ( \WP_REST_Request $req ) {
					$id = (int) $req['id'];
					$params = $req->get_json_params();
					$params = ! empty( $params ) ? $params : array();
					$subject = isset( $params['subject'] ) ? sanitize_text_field( $params['subject'] ) : '';
					$message = isset( $params['message'] ) ? wp_kses_post( $params['message'] ) : '';

					if ( ! $id ) {
						return new \WP_Error( 'bad_id', __( 'Invalid ID.', 'wpuserfeedback' ), array( 'status' => 400 ) );
					}

					global $wpdb;
					$table = $wpdb->prefix . 'wpuserfeedback';
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$done = $wpdb->update(
						$table,
						array(
							'subject'      => $subject,
							'message'      => $message,
							'date_updated' => current_time( 'mysql' ),
						),
						array( 'id' => $id ),
						array( '%s', '%s', '%s' ),
						array( '%d' )
					);

					if ( false !== $done ) {
						// Clear cache for this feedback row.
						wp_cache_delete( 'feedback_' . $id, 'wpuserfeedback' );
					}

					return rest_ensure_response( array( 'updated' => (bool) $done ) );
				},
			)
		);

		register_rest_route(
			'wpuf/v1',
			'/feedback/(?P<id>\d+)',
			array(
				'methods'             => 'DELETE',
				'permission_callback' => array( $this, 'can_manage' ),
				'callback'            => function ( \WP_REST_Request $req ) {
					$id = (int) $req['id'];
					if ( ! $id ) {
						return new \WP_Error( 'bad_id', __( 'Invalid ID.', 'wpuserfeedback' ), array( 'status' => 400 ) );
					}

					global $wpdb;
					$table = $wpdb->prefix . 'wpuserfeedback';

                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$done = $wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );

					if ( false !== $done ) {
						wp_cache_delete( 'feedback_' . $id, 'wpuserfeedback' );
					}

					return rest_ensure_response(
						array(
							'deleted' => (bool) $done,
						)
					);
				},
			)
		);
	}

	/**
	 * Check if the current user can manage options.
	 *
	 * @return bool
	 */
	public function can_manage(): bool {
		return current_user_can( 'manage_options' );
	}
}

<?php
/**
 * Handles AJAX functionality for the plugin.
 *
 * This class defines all code necessary to run AJAX-related actions
 * and filters in the plugin.
 *
 * @package WPUserFeedback
 * @since   1.0
 */

namespace WPUserFeedback;

use WPUserFeedback\Database;

/**
 * Class Ajax.
 */
class Ajax {

	/**
	 * Database handler instance.
	 *
	 * @var Database
	 */
	protected $db;

	/**
	 * Constructor.
	 *
	 * Optionally inject Database dependency.
	 *
	 * @param Database|null $db Database instance.
	 */
	public function __construct( $db = null ) {
		$this->db = $db instanceof Database ? $db : new Database();

		add_action( 'wp_ajax_wpuserfeedback_form_action', array( $this, 'form_callback' ) );
		add_action( 'wp_ajax_nopriv_wpuserfeedback_form_action', array( $this, 'form_callback' ) );

		// get result only auth user.
		add_action( 'wp_ajax_wpuserfeedback_result_action', array( $this, 'result_callback' ) );

		// get result by id.
		add_action( 'wp_ajax_wpuserfeedback_result_by_id_action', array( $this, 'result_by_id_callback' ) );

		// pagination.
		add_action( 'wp_ajax_wpuserfeedback_pagination_action', array( $this, 'pagination_callback' ) );
	}

	/**
	 * Handle feedback form submission (AJAX).
	 *
	 * @return void
	 */
	public function form_callback(): void {

		// Security: verify nonce first.
		if ( ! check_ajax_referer( 'wpuserfeedback', 'nonce', false ) ) {
			$this->return_json( 'error', __( 'Security check failed.', 'wpuserfeedback' ) );
		}

		// Basic payload guard.
		if ( empty( $_POST['data'] ) || ! is_string( $_POST['data'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified above.
			$this->return_json( 'error', __( 'Invalid request payload.', 'wpuserfeedback' ) );
		}

		// Validate required fields.
		$form_vals = array();

		if ( isset( $_POST['data'] ) && is_string( $_POST['data'] ) ) {
			// Convert serialized query string into array.
			wp_parse_str( wp_unslash( $_POST['data'] ), $form_vals ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized below.
		}

		// Now sanitize from parsed array.
		$first_name = isset( $form_vals['first_name'] ) ? sanitize_text_field( $form_vals['first_name'] ) : '';
		$last_name  = isset( $form_vals['last_name'] ) ? sanitize_text_field( $form_vals['last_name'] ) : '';
		$email      = isset( $form_vals['email'] ) ? sanitize_email( $form_vals['email'] ) : '';
		$subject    = isset( $form_vals['subject'] ) ? sanitize_text_field( $form_vals['subject'] ) : '';
		$message    = isset( $form_vals['message'] ) ? wp_kses_post( $form_vals['message'] ) : '';

		if ( empty( $first_name ) || empty( $last_name ) || empty( $email ) || empty( $subject ) || empty( $message ) ) {
			$this->return_json( 'error', __( 'Please fill in all required fields.', 'wpuserfeedback' ) );
		}

		// Persist to DB (prefer dependency injection; fall back to new instance).
		$insert_id = $this->db->do_insert( $first_name, $last_name, $email, $subject, $message );

		if ( is_wp_error( $insert_id ) ) {
			$this->return_json( 'error', $insert_id->get_error_message() );
		}

		$this->return_json(
			'success',
			__( 'Thank you for sending us your feedback.', 'wpuserfeedback' )
		);
	}

	/**
	 * Get results, but only admins can see them.
	 *
	 * @return void
	 */
	public function result_callback() {
		// Security: verify nonce first.
		if ( ! check_ajax_referer( 'wpuserfeedback', 'nonce' ) ) {
			$this->return_json( 'error' );
		}

		// Capability check.
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			$this->return_json( 'error' );
		}

		// Pagination values.
		$list_per_page = isset( $_GET['list_per_page'] ) ? absint( wp_unslash( $_GET['list_per_page'] ) ) : 10;
		$page          = isset( $_GET['page_no'] ) ? absint( wp_unslash( $_GET['page_no'] ) ) : 1;

		$results = $this->db->get_results( $list_per_page, $page );

		if ( ! empty( $results ) ) : ?>
				<table class="table table-striped wpuserfeedback-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'First Name', 'wpuserfeedback' ); ?></th>
							<th><?php esc_html_e( 'Email', 'wpuserfeedback' ); ?></th>
							<th><?php esc_html_e( 'Subject', 'wpuserfeedback' ); ?></th>
							<th><?php esc_html_e( 'Action', 'wpuserfeedback' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $results as $result ) : ?>
								<tr>
									<td><?php echo esc_html( $result->first_name ); ?></td>
									<td><?php echo esc_html( $result->email ); ?></td>
									<td><?php echo esc_html( $result->subject ); ?></td>
									<td>
										<div
											class="wpuf-view-result"
											data-id="<?php echo esc_attr( $result->id ); ?>"
											title="<?php esc_attr_e( 'View Details', 'wpuserfeedback' ); ?>">
											<span class="dashicons dashicons-visibility"></span>
										</div>
									</td>
								</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php
		endif;

		wp_die();
	}

	/**
	 * Get result by id.
	 */
	public function result_by_id_callback() {

		// Nonce verification.
		if ( ! check_ajax_referer( 'wpuserfeedback', 'nonce', false ) ) {
			$this->return_json( 'error', __( 'Security check failed.', 'wpuserfeedback' ) );
		}

		// Check capability (avoid roles directly).
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			$this->return_json( 'error', __( 'Not allowed.', 'wpuserfeedback' ) );
		}

		// Sanitize & validate ID.
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		if ( ! $id ) {
			$this->return_json( 'error', __( 'Invalid ID.', 'wpuserfeedback' ) );
		}

		// Fetch result.
		$result = $this->db->get_result_by_id( $id );

		// if id not found.
		if ( ! $id ) {
			$this->return_json( 'error' );
		}

		$result = $this->db->get_result_by_id( $id );
		?>
		<h2 class="mt-5">Detail Information</h2>
		<ul class="details-list list-unstyled">
			<li><span><?php esc_html_e( 'First Name', 'wpuserfeedback' ); ?></span>: <?php echo esc_html( $result->first_name ); ?></li>
			<li><span><?php esc_html_e( 'Last Name', 'wpuserfeedback' ); ?></span>: <?php echo esc_html( $result->last_name ); ?></li>
			<li><span><?php esc_html_e( 'Email', 'wpuserfeedback' ); ?></span>: <?php echo esc_html( $result->email ); ?></li>
			<li><span><?php esc_html_e( 'Subject', 'wpuserfeedback' ); ?></span>: <?php echo esc_html( $result->subject ); ?></li>
			<li><span><?php esc_html_e( 'Message', 'wpuserfeedback' ); ?></span>: <?php echo esc_html( $result->message ); ?></li>
		</ul>
		<?php
		wp_die();
	}

	/**
	 * Pagination update.
	 */
	public function pagination_callback() {
		// Nonce verification.
		if ( ! check_ajax_referer( 'wpuserfeedback', 'nonce' ) ) {
			$this->return_json( 'error' );
		}

		// Check capability.
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			$this->return_json( 'error' );
		}

		$page_no       = isset( $_POST['page_no'] ) ? absint( wp_unslash( $_POST['page_no'] ) ) : 1;
		$list_per_page = isset( $_POST['list_per_page'] ) ? absint( wp_unslash( $_POST['list_per_page'] ) ) : 10;

		if ( ! $page_no ) {
			$this->return_json( 'error' );
		}

		// Fetch results.
		$total   = $this->db->count_total();
		$results = $this->db->get_results( $list_per_page, $page_no );

		ob_start();
		if ( ! empty( $results ) ) {
			foreach ( $results as $result ) {
				?>
						<ul class="list-result list-unstyled d-flex gap-3 justify-content-between" data-id="<?php echo esc_attr( $result->id ); ?>">
							<li><?php echo esc_html( $result->first_name ); ?></li>
							<li><?php echo esc_html( $result->last_name ); ?></li>
							<li><?php echo esc_html( $result->email ); ?></li>
							<li><?php echo esc_html( $result->subject ); ?></li>
						</ul>
						<?php
			}
		}
		$list_html = ob_get_clean();

		$pagination = paginate_links(
			array(
				'base'      => '?cpage=%#%',
				'format'    => '',
				'total'     => ceil( $total / $list_per_page ),
				'current'   => $page_no,
				'show_all'  => false,
				'prev_text' => __( '&laquo;', 'wpuserfeedback' ),
				'next_text' => __( '&raquo;', 'wpuserfeedback' ),
			)
		);

		wp_send_json_success(
			array(
				'list_html'  => $list_html,
				'pagination' => $pagination,
			)
		);
	}


	/**
	 * Send JSON response with status and message.
	 *
	 * @param string $status  Response status (e.g. 'success' or 'error').
	 * @param string $message Optional. Response message. Default ''.
	 * @return void
	 */
	public function return_json( string $status, string $message = '' ): void {
		$response = array(
			'status'  => $status,
			'message' => $message,
		);

		wp_send_json( $response ); // This calls wp_die() internally, so no need to call it again.
	}
}
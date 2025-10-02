<?php
/**
 * Shortcode handler class.
 *
 * Handles registration and rendering of all shortcodes for WPUserFeedback.
 *
 * @package WPUserFeedback
 * @since   1.0.0
 */

namespace WPUserFeedback;

use WPUserFeedback\Database;

/**
 * Class Shortcode
 */
class Shortcode {


	/**
	 * Register shortcodes on initialization.
	 */
	public function __construct() {
		add_shortcode( 'wp_user_feedback_form', array( $this, 'render_feedback_form_shortcode' ) );
		add_shortcode( 'wp_user_feedback_results', array( $this, 'render_feedback_results_shortcode' ) );
	}

	/**
	 * Render the [wp_user_feedback_form] shortcode.
	 *
	 * Loads the necessary CSS/JS, builds the feedback form,
	 * and returns the rendered HTML output.
	 *
	 * @param array $atts    Shortcode attributes.
	 * @return string Rendered HTML form.
	 */
	public function render_feedback_form_shortcode( $atts ): string {
		$defaults = array(
			'title' => esc_html__( 'Submit your feedback', 'wpuserfeedback' ),
		);

		$shortcode_atts = shortcode_atts( $defaults, $atts, 'wp_user_feedback_form' );

		// Enqueue assets.
		wp_enqueue_style( 'bootstrap' );
		wp_enqueue_style( 'wpuserfeedback-frontend' );
		wp_enqueue_script( 'wpuserfeedback-frontend' );

		$current_user = wp_get_current_user();

		ob_start();
		?>
		<div class="wpuserfeedback-feedback-section">
			<div class="container">

				<?php if ( ! empty( $shortcode_atts['title'] ) ) : ?>
					<h2 class="my-5 mt-0"><?php echo esc_html( $shortcode_atts['title'] ); ?></h2>
				<?php endif; ?>

				<div class="feedback-area">
					<form action="" class="feedback-form" method="POST">
						<div class="form-group mb-4">
							<label for="inputFirstName"><?php esc_html_e( 'First Name', 'wpuserfeedback' ); ?></label>
							<input name="first_name" type="text" class="form-control rounded-0 inputFirstName"
								placeholder="<?php esc_attr_e( 'First Name', 'wpuserfeedback' ); ?>"
								value="<?php echo esc_attr( $current_user->user_firstname ); ?>" required>
							<div class="alert-msg text-danger"></div>
						</div>

						<div class="form-group mb-4">
							<label for="inputLastName"><?php esc_html_e( 'Last Name', 'wpuserfeedback' ); ?></label>
							<input name="last_name" type="text" class="form-control rounded-0 inputLastName"
								placeholder="<?php esc_attr_e( 'Last Name', 'wpuserfeedback' ); ?>"
								value="<?php echo esc_attr( $current_user->user_lastname ); ?>" required>
							<div class="alert-msg text-danger"></div>
						</div>

						<div class="form-group mb-4">
							<label for="inputEmail"><?php esc_html_e( 'Email', 'wpuserfeedback' ); ?></label>
							<input name="email" type="email" class="form-control rounded-0 inputEmail"
								placeholder="<?php esc_attr_e( 'Email', 'wpuserfeedback' ); ?>"
								value="<?php echo esc_attr( $current_user->user_email ); ?>">
							<div class="alert-msg text-danger"></div>
						</div>

						<div class="form-group mb-4">
							<label for="inputSubject"><?php esc_html_e( 'Subject', 'wpuserfeedback' ); ?></label>
							<input name="subject" type="text" class="form-control rounded-0 inputSubject"
								placeholder="<?php esc_attr_e( 'Subject', 'wpuserfeedback' ); ?>">
							<div class="alert-msg text-danger"></div>
						</div>

						<div class="form-group mb-4">
							<label for="inputMessage"><?php esc_html_e( 'Message', 'wpuserfeedback' ); ?></label>
							<textarea name="message" class="form-control rounded-0 inputMessage"
								placeholder="<?php esc_attr_e( 'Write Your Message', 'wpuserfeedback' ); ?>"></textarea>
							<div class="alert-msg text-danger"></div>
						</div>

						<button type="button" class="action-btn btn btn-success rounded-0 text-white">
							<?php esc_html_e( 'Submit', 'wpuserfeedback' ); ?>
						</button>
					</form>
				</div>

			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render the [wp_user_feedback_results] shortcode.
	 *
	 * Displays a paginated list of feedback results for authorized users.
	 * Includes list table, pagination, and detail section.
	 *
	 * @param array $atts    Shortcode attributes.
	 * @return string Rendered HTML results table or unauthorized message.
	 */
	public function render_feedback_results_shortcode( $atts ): string {
		$defaults = array(
			'title'         => esc_html__( 'Feedback Results', 'wpuserfeedback' ),
			'list_per_page' => 10,
		);

		$shortcode_atts = shortcode_atts( $defaults, $atts, 'wp_user_feedback_results' );
		$title          = $shortcode_atts['title'];
		$list_per_page  = absint( $shortcode_atts['list_per_page'] );

		// Enqueue assets.
		wp_enqueue_style( 'bootstrap' );
		wp_enqueue_style( 'wpuserfeedback-frontend' );
		wp_enqueue_script( 'wpuserfeedback-frontend' );

		ob_start();
		?>
		<div class="wpuserfeedback-feedback-results">
			<div class="container">
				<?php if ( ! empty( $title ) ) : ?>
					<h2 class="my-5"><?php echo esc_html( $title ); ?></h2>
				<?php endif; ?>

				<?php if ( is_user_logged_in() && current_user_can( 'manage_options' ) ) : ?>
					<?php
					$get_results = new Database();
					$total       = (int) $get_results->count_total();
					$page        = isset( $_GET['cpage'] ) ? absint( wp_unslash( $_GET['cpage'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$results     = $get_results->get_results( $list_per_page, $page );
					?>
					<div id="load-lists" data-items="<?php echo esc_attr( $list_per_page ); ?>">
						<?php if ( ! empty( $results ) ) : ?>
							<ul class="list-heading list-unstyled d-flex gap-3 justify-content-between">
								<li><?php esc_html_e( 'First Name', 'wpuserfeedback' ); ?></li>
								<li><?php esc_html_e( 'Last Name', 'wpuserfeedback' ); ?></li>
								<li><?php esc_html_e( 'Email', 'wpuserfeedback' ); ?></li>
								<li><?php esc_html_e( 'Subject', 'wpuserfeedback' ); ?></li>
							</ul>
							<?php foreach ( $results as $result ) : ?>
								<ul class="list-result list-unstyled d-flex gap-3 justify-content-between"
									data-id="<?php echo esc_attr( $result->id ); ?>">
									<li><?php echo esc_html( $result->first_name ); ?></li>
									<li><?php echo esc_html( $result->last_name ); ?></li>
									<li><?php echo esc_html( $result->email ); ?></li>
									<li><?php echo esc_html( $result->subject ); ?></li>
								</ul>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
					<div class="pagination">
						<?php
						echo wp_kses_post(
							paginate_links(
								array(
									'base'      => add_query_arg( 'cpage', '%#%' ),
									'prev_text' => esc_html__( '&laquo;', 'wpuserfeedback' ),
									'next_text' => esc_html__( '&raquo;', 'wpuserfeedback' ),
									'total'     => (int) ceil( $total / $list_per_page ),
									'show_all'  => false,
									'current'   => $page,
									'add_args'  => false,
								)
							)
						);
						?>
					</div>
					<div class="details-block"></div>
				<?php else : ?>
					<div class="not-auth text-center">
						<h3 class="text-danger mb-4">
							<?php esc_html_e( 'You are not authorized to view the content of this page.', 'wpuserfeedback' ); ?>
						</h3>
						<p>
							<a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>">
								<?php esc_html_e( 'Please Login', 'wpuserfeedback' ); ?>
							</a>
						</p>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
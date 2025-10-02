<?php
/**
 * Main Plugin Class
 *
 * This class is responsible for initializing the plugin,
 * setting up hooks, and managing the overall functionality.
 *
 * @package WPUserFeedback
 * @since   1.0
 */

namespace WPUserFeedback;

use WPUserFeedback\Installer;
use WPUserFeedback\Assets;
use WPUserFeedback\Ajax;
use WPUserFeedback\Shortcode;
use WPUserFeedback\Admin;


/**
 * Class Plugin.
 * Hold the entire plugin function
 *
 * @since 1.0
 */
final class Plugin {


	/**
	 * Plugin version
	 *
	 * @var string
	 */
	public $version = '1.0.0';

	/**
	 * Minimum PHP version required
	 *
	 * @var string
	 */
	private $min_php = '7.4';

	/**
	 * Self Instance
	 *
	 * @var Plugin
	 */
	private static $instance = null;

	/**
	 * Hold Various instance
	 *
	 * @var array
	 */
	private $container = array();

	/**
	 * Constructor Magic Method
	 *
	 * Sets up all appropriate hooks and actions
	 * within this plugin.
	 *
	 * @uses register_activation_hook()
	 * @uses register_deactivation_hook()
	 */
	private function __construct() {
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
	}

	/**
	 * Activation callback.
	 *
	 * Deactivates and shows a message if PHP is too old.
	 *
	 * @return void
	 */
	public function activate(): void {
		// Bail if PHP version not supported.
		if ( ! $this->is_supported_php() ) {
			// Deactivate this plugin safely (adjust constant/file as needed).
			deactivate_plugins( plugin_basename( __FILE__ ) );

			$message = sprintf(
				// Translators: 1 - This plugin cannot be activated because it requires at least PHP, 2 - PHP version.
				esc_html__(
					'This plugin cannot be activated because it requires at least PHP version %s.',
					'wpuserfeedback'
				),
				esc_html( $this->min_php )
			);

			$link = sprintf(
				'<p><a href="%s">%s</a></p>',
				esc_url( admin_url( 'plugins.php' ) ),
				esc_html__( 'Back to Plugins', 'wpuserfeedback' )
			);

			wp_die( wp_kses_post( '<p>' . $message . '</p>' . $link ) );
		}

		$installer = new Installer();
		$installer->do_install();
	}

	/**
	 * Placeholder for deactivation.
	 *
	 * Nothing called for now
	 */
	public function deactivate() {
	}

	/**
	 * Initiaze the plugin class.
	 *
	 * - Checks for an existing WPUserFeedback() instance
	 * - If not exists create new one
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new Plugin();

			add_action( 'plugins_loaded', array( self::$instance, 'init_plugin' ) );
		}

		return self::$instance;
	}


	/**
	 * Check PHP version is supported or not.
	 *
	 * @return bool
	 */
	public function is_supported_php() {

		if ( version_compare( PHP_VERSION, $this->min_php, '<=' ) ) {
			return false;
		}

		return true;
	}


	/**
	 * Init plugin.
	 *
	 * @return void
	 */
	public function init_plugin() {
		// Localize our plugin.
		add_action( 'init', array( $this, 'localization_setup' ) );

		// init classes.
		add_action( 'init', array( $this, 'init_classes' ) );

		if ( function_exists( 'register_block_type' ) ) {
			// gutenberg.
			add_action( 'init', array( $this, 'register_block_types' ) );
		}

		$admin = new Admin();
		$admin->hooks();
	}

	/**
	 * Initialize plugin for localization.
	 *
	 * @uses load_plugin_textdomain()
	 */
	public function localization_setup() {
		load_plugin_textdomain( 'wpuserfeedback', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}


	/**
	 * Block register.
	 *
	 * @return void
	 */
	public function register_block_types() {
		register_block_type(
			'wpuserfeedback/form',
			array(
				'render_callback' => array( $this, 'render_form' ),
			)
		);

		register_block_type(
			'wpuserfeedback/result',
			array(
				'render_callback' => array( $this, 'render_result' ),
			)
		);
	}

	/**
	 * Render the [wp_user_feedback_form] shortcode.
	 *
	 * This method delegates rendering of the feedback form
	 * to the Shortcode class and returns the generated HTML.
	 *
	 * @param array $attributes Shortcode attributes passed from WordPress.
	 * @return string Rendered HTML of the feedback form.
	 */
	public function render_form( $attributes ) {
		$shortcode = new Shortcode();
		$result    = $shortcode->render_feedback_form_shortcode( $attributes );

		return $result;
	}

	/**
	 * Render the [wp_user_feedback_result] shortcode.
	 *
	 * This method creates an instance of the Shortcode class
	 * and delegates rendering of feedback results to it.
	 *
	 * @param array $attributes Shortcode attributes passed from WordPress.
	 * @return string Rendered HTML output of the feedback results.
	 */
	public function render_result( $attributes ) {
		$shortcode = new Shortcode();
		return $shortcode->render_feedback_results_shortcode( $attributes );
	}

	/**
	 * Initialize required classes
	 *
	 * @return void
	 */
	public function init_classes() {
		// plugin assets.
		$this->container['assets'] = new Assets();

		// frontend.
		if ( $this->is_request( 'frontend' ) ) {
			// shortcode.
			$this->container['shortcode'] = new Shortcode();
		}

		if ( $this->is_request( 'ajax' ) ) {
			$this->container['ajax'] = new Ajax();
		}
	}

	/**
	 * Check the current request type.
	 *
	 * Determines whether the current execution context is admin, AJAX,
	 * cron, or frontend.
	 *
	 * @param string $type Request type: 'admin', 'ajax', 'cron', or 'frontend'.
	 * @return bool True if the current request matches the given type, false otherwise.
	 */
	private function is_request( $type ): bool {
		switch ( $type ) {
			case 'admin':
				return is_admin();

			case 'ajax':
				return ( defined( 'DOING_AJAX' ) && DOING_AJAX );

			case 'cron':
				return ( defined( 'DOING_CRON' ) && DOING_CRON );

			case 'frontend':
				return ! is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) && ! ( defined( 'DOING_CRON' ) && DOING_CRON );

			default:
				return false;
		}
	}
}

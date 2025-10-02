<?php
/**
 * Handles plugin assets.
 *
 * This class is responsible for managing the assets (e.g., stylesheets, JavaScript files)
 * used by the WPUserFeedback plugin.
 *
 * @package WPUserFeedback
 * @since   1.0
 */

namespace WPUserFeedback;

/**
 * Scripts and Styles Class
 */
class Assets {

	/**
	 * Initilize the class.
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_register' ), 5 );
		add_action( 'enqueue_block_assets', array( $this, 'enqueue_block_script' ) );
	}

	/**
	 * Register scripts and styles.
	 *
	 * @return void
	 */
	public function frontend_register() {
		$this->register_styles( $this->get_styles() );
		$this->register_scripts( $this->get_scripts() );

		// Localize script.
		wp_localize_script(
			'wpuserfeedback-frontend',
			'wpuserfeedback_loc',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'wpuserfeedback' ),
			)
		);
	}

	/**
	 * Register multiple styles.
	 *
	 * Loops through a list of styles and registers them with wp_register_style.
	 *
	 * @param array $styles Associative array of styles where the key is the handle
	 *                      and the value is an array containing:
	 *                      - 'src'  (string) URL to the stylesheet.
	 *                      - 'deps' (array) Optional. Array of style handles this stylesheet depends on.
	 *
	 * @return void
	 */
	public function register_styles( $styles ) {
		foreach ( $styles as $handle => $style ) {
			$deps = isset( $style['deps'] ) ? $style['deps'] : array();
			wp_register_style( $handle, $style['src'], $deps, WPUF_VERSION );
		}
	}

	/**
	 * Register JavaScript files with WordPress.
	 *
	 * Accepts an associative array of scripts and registers each one
	 * using wp_register_script().
	 *
	 * @param array $scripts List of scripts, keyed by handle. Each item should contain:
	 *                       - 'src'       (string)  URL to the script file.
	 *                       - 'deps'      (array)   Optional. Script dependencies. Default [].
	 *                       - 'version'   (string)  Optional. Script version. Default WPUF_VERSION.
	 *                       - 'in_footer' (bool)    Optional. Whether to load in footer. Default false.
	 *
	 * @return void
	 */
	private function register_scripts( $scripts ) {
		foreach ( $scripts as $handle => $script ) {
			$deps      = isset( $script['deps'] ) ? $script['deps'] : array();
			$in_footer = isset( $script['in_footer'] ) ? (bool) $script['in_footer'] : false;
			$version   = isset( $script['version'] ) ? $script['version'] : WPUF_VERSION;

			wp_register_script( $handle, $script['src'], $deps, $version, $in_footer );
		}
	}

	/**
	 * Get frontend registered styles.
	 *
	 * @return array
	 */
	public function get_styles() {

		$styles = array(
			'bootstrap'               => array(
				'src' => WPUF_ASSETS . '/css/bootstrap.min.css',
			),
			'wpuserfeedback-frontend' => array(
				'src' => WPUF_ASSETS . '/css/style.css',
			),
		);

		return $styles;
	}

	/**
	 * Get all frontend registered scripts.
	 *
	 * @return array
	 */
	public function get_scripts() {

		$scripts = array(
			'wpuserfeedback-frontend' => array(
				'src'       => WPUF_ASSETS . '/js/frontend.js',
				'deps'      => array( 'jquery' ), // dependency.
				'version'   => WPUF_VERSION,
				'in_footer' => true,
			),
		);

		return $scripts;
	}

	/**
	 * Enqueue styles and scripts for block editor and frontend.
	 *
	 * @return void
	 */
	public function enqueue_block_script() {
		// Styles with explicit version numbers.
		wp_enqueue_style(
			'bootstrap',
			WPUF_ASSETS . '/css/bootstrap.min.css',
			array(),
			WPUF_VERSION
		);

		wp_enqueue_style(
			'wpuserfeedback-form-block',
			WPUF_ASSETS . '/blocks/form/index.css',
			array(),
			WPUF_VERSION
		);

		// Plain frontend JS (registered elsewhere, enqueued here).
		wp_enqueue_script(
			'wpuserfeedback-frontend',
			'',
			array(),
			WPUF_VERSION,
			true      // load in footer.
		);

		// Block: Form.
		$asset_file = include WPUF_PATH . '/assets/blocks/form/index.asset.php';
		wp_enqueue_script(
			'wpuserfeedback-form',
			WPUF_ASSETS . '/blocks/form/index.js',
			$asset_file['dependencies'],
			$asset_file['version'],
			true
		);

		// Block: Result.
		$result_asset_file = include WPUF_PATH . '/assets/blocks/result/index.asset.php';
		wp_enqueue_script(
			'wpuserfeedback-result',
			WPUF_ASSETS . '/blocks/result/index.js',
			$result_asset_file['dependencies'],
			$result_asset_file['version'],
			true
		);
	}
}

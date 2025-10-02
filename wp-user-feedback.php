<?php
/**
 * Plugin Name: WP User Feedback
 * Plugin URI: https://www.xstheme.com
 * Description: Collect user feedback by WP User Feedback WordPress plugin.
 * Version: 1.0.0
 * Author: Jahirul Islam Mamun
 * Author URI: http://www.xstheme.com
 * Text Domain: wpuserfeedback
 * Domain Path:  /languages
 * License:      GPLv2+
 * License URI:  LICENSE
 */


// don't call the file directly
defined( 'ABSPATH' ) || die( 'No direct access!' );

define('WPUF_VERSION', '1.0.0');
define('WPUF_FILE', __FILE__);
define('WPUF_PATH', dirname(WPUF_FILE));
define('WPUF_URL', plugins_url('', WPUF_FILE));
define('WPUF_ASSETS', WPUF_URL . '/assets');

require_once __DIR__ . '/vendor/autoload.php';

use WPUserFeedback\Plugin;


if ( ! function_exists( 'wpuserfeedback' ) ) {
    /**
     * Returns instanse of the plugin class.
     *
     * @since  1.0
     * @return object
     */
    function wpuserfeedback()
    {
        return Plugin::instance();
    }
}

//lets play.
wpuserfeedback();
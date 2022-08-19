<?php
/**
 * Plugin Name: Latif Pala - Guest Post Submission
 * Description: Multidots assginment to create guest post submission
 * Author: latifpala
 * Author URI: https://profiles.wordpress.org/latifpala/
 * Version: 1.0.0
 * Text Domain: lp-guest-post-submission
 *
 * @author Latif Pala
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
	exit;
}

define('LP_GUEST_POST_SUBMIT_VER', '1.0.0');
define('LP_GUEST_POST_SUBMIT_DIR', plugin_dir_path(__FILE__));
define('LP_GUEST_POST_SUBMIT_ASSETS_DIR', plugin_dir_url(__FILE__).'assets');
define('LP_GUEST_POST_SUBMIT_TXTDOMAIN', 'lp-guest-post-submission');

/**
 * Load File Dependencies
 *
 * @since 1.0.0
 * 
 */
include_once(LP_GUEST_POST_SUBMIT_DIR . '/includes/lp-guest-post-functions.php');
include_once(LP_GUEST_POST_SUBMIT_DIR . '/includes/lp-guest-post-shortcodes.php');
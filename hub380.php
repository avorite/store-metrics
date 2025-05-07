<?php
/**
 * Plugin Name: Store Metrics
 * Plugin URI: https://greenlime.co.il
 * Description: Plugin for displaying store statistics – most popular product, total sales, total deals, and ROI.
 * Version: 1.0.0
 * Author: Maxim Shyian
 * Author URI: https://greenlime.co.il
 * License: GPL-2.0+
 * Text Domain: hub380
 * Domain Path: /languages
 *
 * @package hub380
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Activation hook callback.
 *
 * Flushes rewrite rules on plugin activation.
 */
function hub380_activate() {
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'hub380_activate' );

/**
 * Deactivation hook callback.
 *
 * Flushes rewrite rules on plugin deactivation.
 */
function hub380_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'hub380_deactivate' );

// Include the main class file.
require_once plugin_dir_path( __FILE__ ) . 'includes/class-hub380.php';

// Initialize the plugin.
new Hub380();

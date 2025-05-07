<?php
/**
 * Plugin Name: Store Metrics
 * Plugin URI: 
 * Description: Plugin for displaying store statistics – most popular product, total sales, total deals, and ROI.
 * Version: 1.0.0
 * Author: Maxim Shyian
 * Author URI: 
 * License: GPL-2.0+
 * Text Domain: store-metrics
 * Domain Path: /languages
 *
 * @package store-metrics
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'STORE_METRICS_VERSION', '1.0.0' );
define( 'STORE_METRICS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'STORE_METRICS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'STORE_METRICS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Check if Composer autoload exists.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    // Fallback autoload function if Composer isn't used.
    spl_autoload_register( function ( $class ) {
        // Project-specific namespace prefix.
        $prefix = 'StoreMetrics\\';
        
        // Base directory for the namespace prefix.
        $base_dir = __DIR__ . '/src/';
        
        // Does the class use the namespace prefix?
        $len = strlen( $prefix );
        if ( strncmp( $prefix, $class, $len ) !== 0 ) {
            // No, move to the next registered autoloader.
            return;
        }
        
        // Get the relative class name.
        $relative_class = substr( $class, $len );
        
        // Replace the namespace prefix with the base directory, replace namespace
        // separators with directory separators in the relative class name, append
        // with .php
        $file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';
        
        // If the file exists, require it.
        if ( file_exists( $file ) ) {
            require $file;
        }
    } );
}

/**
 * Activation hook callback.
 *
 * Flushes rewrite rules on plugin activation.
 */
function store_metrics_activate() {
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'store_metrics_activate' );

/**
 * Deactivation hook callback.
 *
 * Flushes rewrite rules on plugin deactivation.
 */
function store_metrics_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'store_metrics_deactivate' );

// Initialize the plugin.
add_action( 'plugins_loaded', function() {
    // Create instance of the main plugin class.
    $plugin = new StoreMetrics\Plugin();
    // Initialize the plugin.
    $plugin->init();
}, 11 ); 
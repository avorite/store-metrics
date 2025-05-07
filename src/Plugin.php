<?php
/**
 * Main plugin class.
 *
 * @package store-metrics
 */

namespace StoreMetrics;

use StoreMetrics\Admin\AdminPage;
use StoreMetrics\Admin\Assets;
use StoreMetrics\Product\CostPriceManager;
use StoreMetrics\Statistics\StatisticsService;
use StoreMetrics\Settings\SettingsManager;

/**
 * Main plugin class.
 */
class Plugin {
    /**
     * Statistics service instance.
     *
     * @var StatisticsService
     */
    private $statistics;

    /**
     * Settings manager instance.
     *
     * @var SettingsManager
     */
    private $settings;

    /**
     * Admin page instance.
     *
     * @var AdminPage
     */
    private $admin_page;

    /**
     * Assets instance.
     *
     * @var Assets
     */
    private $assets;

    /**
     * Cost price manager instance.
     *
     * @var CostPriceManager
     */
    private $cost_price_manager;

    /**
     * Initialize plugin.
     *
     * @return void
     */
    public function init() {
        // Check if WooCommerce is active.
        if ( ! defined( 'WC_VERSION' ) ) {
            add_action( 'admin_notices', array( $this, 'woocommerce_inactive_notice' ) );
            return;
        }

        // Initialize services.
        $this->load_dependencies();
        
        // Load text domain.
        add_action( 'init', array( $this, 'load_textdomain' ) );
    }

    /**
     * Load plugin text domain.
     *
     * @return void
     */
    public function load_textdomain() {
        $locale = determine_locale();
        $locale = apply_filters( 'plugin_locale', $locale, 'store-metrics' );

        unload_textdomain( 'store-metrics' );
        load_textdomain( 'store-metrics', WP_LANG_DIR . '/plugins/store-metrics-' . $locale . '.mo' );
        load_plugin_textdomain( 'store-metrics', false, dirname( plugin_basename( __FILE__ ) ) . '/../languages' );
    }

    /**
     * Load dependencies and initialize services.
     *
     * @return void
     */
    private function load_dependencies() {
        // Initialize settings.
        $this->settings = new SettingsManager();
        
        // Initialize cost price manager first so it can be shared.
        $this->cost_price_manager = new CostPriceManager();
        
        // Initialize statistics service with cost price manager.
        $this->statistics = new StatisticsService($this->settings, $this->cost_price_manager);
        
        // Initialize admin page.
        $this->admin_page = new AdminPage($this->statistics, $this->settings);
        $this->admin_page->hook_menu();
        
        // Initialize assets after admin menu is hooked (hook_suffix will be set by admin_menu action)
        add_action('admin_menu', function() {
            $this->assets = new Assets($this->admin_page->get_hook_suffix());
        }, 20); // Priority 20 ensures it runs after the admin menu is registered (usually at priority 10)
    }

    /**
     * Display notice if WooCommerce is not active.
     *
     * @return void
     */
    public function woocommerce_inactive_notice() {
        if ( ! current_user_can( 'activate_plugins' ) ) {
            return;
        }

        $woocommerce_path = 'woocommerce/woocommerce.php';
        $is_woocommerce_installed = file_exists( WP_PLUGIN_DIR . '/' . $woocommerce_path );

        if ( $is_woocommerce_installed ) {
            $action_url = wp_nonce_url( 
                add_query_arg(
                    array(
                        'action' => 'activate',
                        'plugin' => $woocommerce_path
                    ),
                    admin_url( 'plugins.php' )
                ),
                'activate-plugin_' . $woocommerce_path
            );
            $message = sprintf(
                /* translators: %1$s: Plugin name, %2$s: WooCommerce activation link */
                esc_html__( '%1$s requires WooCommerce to be active. Please %2$s.', 'store-metrics' ),
                '<strong>Store Metrics</strong>',
                '<a href="' . esc_url( $action_url ) . '">' . esc_html__( 'activate WooCommerce', 'store-metrics' ) . '</a>'
            );
        } else {
            $action_url = wp_nonce_url(
                add_query_arg(
                    array(
                        'action' => 'install-plugin',
                        'plugin' => 'woocommerce'
                    ),
                    admin_url( 'update.php' )
                ),
                'install-plugin_woocommerce'
            );
            $message = sprintf(
                /* translators: %1$s: Plugin name, %2$s: WooCommerce installation link */
                esc_html__( '%1$s requires WooCommerce to be installed and active. Please %2$s.', 'store-metrics' ),
                '<strong>Store Metrics</strong>',
                '<a href="' . esc_url( $action_url ) . '">' . esc_html__( 'install WooCommerce', 'store-metrics' ) . '</a>'
            );
        }

        echo '<div class="notice notice-error"><p>' . wp_kses_post( $message ) . '</p></div>';
    }
} 
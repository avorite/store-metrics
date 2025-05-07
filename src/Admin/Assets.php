<?php
/**
 * Admin Assets.
 *
 * @package store-metrics
 */

namespace StoreMetrics\Admin;

/**
 * Class for managing admin assets.
 */
class Assets {
    /**
     * Hook suffix for admin page.
     *
     * @var string
     */
    private $hook_suffix;

    /**
     * Constructor.
     *
     * @param string $hook_suffix Hook suffix for admin page.
     */
    public function __construct( $hook_suffix ) {
        $this->hook_suffix = $hook_suffix;
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
    }

    /**
     * Enqueue admin CSS and JS files.
     *
     * @param string $hook The current admin page hook.
     * @return void
     */
    public function enqueue_admin_assets( $hook ) {
        // Load assets only on our plugin admin page.
        if ( $hook !== $this->hook_suffix ) {
            return;
        }

        // Get plugin version for cache busting
        $version = defined('STORE_METRICS_VERSION') ? STORE_METRICS_VERSION : '1.0.0';

        // Енкуимо стили с указанием версии для сброса кэша
        wp_enqueue_style(
            'store-metrics-admin-style',
            plugin_dir_url( dirname( __FILE__, 2 ) ) . 'admin/css/store-metrics-admin.css',
            array(),
            $version . '.' . time() // Добавляем timestamp для гарантированного сброса кэша при отладке
        );

        wp_enqueue_script(
            'store-metrics-admin-script',
            plugin_dir_url( dirname( __FILE__, 2 ) ) . 'admin/js/store-metrics-admin.js',
            array( 'jquery' ),
            $version . '.' . time(), // Добавляем timestamp для гарантированного сброса кэша при отладке
            true
        );

        // Добавим inline комментарий для отладки
        wp_add_inline_script('store-metrics-admin-script', 'console.log("Store Metrics: Admin JS loaded successfully!");');
    }
} 
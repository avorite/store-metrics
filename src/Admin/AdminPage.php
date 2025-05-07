<?php
/**
 * Admin Page.
 *
 * @package store-metrics
 */

namespace StoreMetrics\Admin;

use StoreMetrics\Statistics\StatisticsInterface;
use StoreMetrics\Settings\SettingsInterface;

/**
 * Class for managing admin page.
 */
class AdminPage {
    /**
     * Hook suffix for admin page.
     *
     * @var string
     */
    private $hook_suffix = '';

    /**
     * Statistics service instance.
     *
     * @var StatisticsInterface
     */
    private $statistics;

    /**
     * Settings manager instance.
     *
     * @var SettingsInterface
     */
    private $settings;

    /**
     * Constructor.
     *
     * @param StatisticsInterface $statistics Statistics service instance.
     * @param SettingsInterface $settings Settings manager instance.
     */
    public function __construct( StatisticsInterface $statistics, SettingsInterface $settings ) {
        $this->statistics = $statistics;
        $this->settings = $settings;
    }

    /**
     * Hook into admin menu action.
     *
     * @return void
     */
    public function hook_menu(): void {
        add_action('admin_menu', array($this, 'register_admin_menu'));
    }

    /**
     * Register admin menu page.
     *
     * @return void
     */
    public function register_admin_menu(): void {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }
        $this->hook_suffix = add_menu_page(
            esc_html__( 'Store Metrics', 'store-metrics' ),
            esc_html__( 'Store Metrics', 'store-metrics' ),
            'manage_woocommerce',
            'store-metrics',
            array( $this, 'admin_page_callback' ),
            'dashicons-chart-area',
            56
        );
    }

    /**
     * Get hook suffix.
     *
     * @return string
     */
    public function get_hook_suffix(): string {
        return $this->hook_suffix;
    }

    /**
     * Callback function for admin page.
     *
     * @return void
     */
    public function admin_page_callback(): void {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'store-metrics'));
        }
        $selected_year  = isset( $_GET['store_metrics_year'] ) ? intval( sanitize_text_field( $_GET['store_metrics_year'] ) ) : date( 'Y' );
        $selected_month = isset( $_GET['store_metrics_month'] ) ? intval( sanitize_text_field( $_GET['store_metrics_month'] ) ) : date( 'n' );

        $top_products = $this->statistics->get_top_products($selected_year, $selected_month);
        $total_sales  = $this->statistics->get_total_sales($selected_year, $selected_month);
        $total_deals  = $this->statistics->get_total_deals($selected_year, $selected_month);
        $roi          = $this->statistics->calculate_roi($selected_year, $selected_month);

        // Check for admin notice
        $notice = ( isset( $_GET['store_metrics_notice'] ) ) ? sanitize_text_field( wp_unslash( $_GET['store_metrics_notice'] ) ) : '';
        ?>
        <div class="wrap store-metrics-admin-wrap">
            <h1><?php esc_html_e( 'Store Metrics', 'store-metrics' ); ?></h1>
            
            <?php if ( ! empty( $notice ) ) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php echo esc_html( $notice ); ?></p>
                </div>
            <?php endif; ?>

            <!-- Period Selection Form -->
            <div class="store-metrics-period-selection">
                <form method="get" action="">
                    <input type="hidden" name="page" value="store-metrics">
                    <select name="store_metrics_year" id="store_metrics_year">
                        <?php
                        $current_year = date( 'Y' );
                        for ( $y = $current_year - 5; $y <= $current_year; $y++ ) {
                            printf(
                                '<option value="%d" %s>%d</option>',
                                $y,
                                selected( $selected_year, $y, false ),
                                $y
                            );
                        }
                        ?>
                    </select>

                    <select name="store_metrics_month" id="store_metrics_month">
                        <?php
                        for ( $m = 1; $m <= 12; $m++ ) {
                            printf(
                                '<option value="%d" %s>%s</option>',
                                $m,
                                selected( $selected_month, $m, false ),
                                date_i18n( 'F', mktime( 0, 0, 0, $m, 1 ) )
                            );
                        }
                        ?>
                    </select>

                    <input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'store-metrics' ); ?>">
                </form>
            </div>

            <!-- Settings Form -->
            <form method="post" action="options.php">
                <?php
                settings_fields( 'store_metrics_new_options' );
                do_settings_sections( 'store_metrics_new' );
                submit_button( esc_html__( 'Save Settings', 'store-metrics' ) );
                ?>
            </form>

            <hr />

            <!-- Top Products Table -->
            <h2><?php esc_html_e( 'Top 5 Products', 'store-metrics' ); ?></h2>
            <?php if ( ! empty( $top_products ) ) : ?>
                <table class="widefat" cellspacing="0">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Image', 'store-metrics' ); ?></th>
                            <th><?php esc_html_e( 'Product', 'store-metrics' ); ?></th>
                            <th><?php esc_html_e( 'Sales', 'store-metrics' ); ?></th>
                            <th><?php esc_html_e( 'Price', 'store-metrics' ); ?></th>
                            <th><?php esc_html_e( 'Cost Price', 'store-metrics' ); ?>*</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $top_products as $product ) : ?>
                            <tr>
                                <td>
                                    <?php if ( ! empty( $product['image'] ) ) : ?>
                                        <img src="<?php echo esc_url( $product['image'] ); ?>" width="50" height="50" alt="">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo esc_url( $product['permalink'] ); ?>" target="_blank">
                                        <?php echo esc_html( $product['name'] ); ?>
                                    </a>
                                </td>
                                <td><?php echo esc_html( $product['sales_count'] ); ?></td>
                                <td><?php echo wc_price( $product['price'] ); ?></td>
                                <td><?php echo wc_price( $product['cost_price'] ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p class="description store-metrics-cost-price-info">
                    <?php esc_html_e( '* Cost Price is the product cost value that can be set on the product edit page in the General tab.', 'store-metrics' ); ?>
                </p>
            <?php else : ?>
                <p><?php esc_html_e( 'No products found for selected period', 'store-metrics' ); ?></p>
            <?php endif; ?>

            <hr />

            <!-- Statistics Table -->
            <h2><?php esc_html_e( 'Current Statistics', 'store-metrics' ); ?></h2>
            <table class="widefat fixed" cellspacing="0">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Metric', 'store-metrics' ); ?></th>
                        <th><?php esc_html_e( 'Value', 'store-metrics' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php esc_html_e( 'Total Sales', 'store-metrics' ); ?></td>
                        <td><?php echo wc_price( $total_sales ); ?></td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e( 'Total Deals', 'store-metrics' ); ?></td>
                        <td><?php echo esc_html( $total_deals ); ?></td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e( 'ROI', 'store-metrics' ); ?></td>
                        <td><?php echo esc_html( $roi ); ?></td>
                    </tr>
                </tbody>
            </table>

            <!-- Refresh Statistics Form -->
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="store-metrics-refresh-form">
                <?php wp_nonce_field( 'store_metrics_refresh_action', 'store_metrics_nonce' ); ?>
                <input type="hidden" name="action" value="store_metrics_refresh">
                <input type="hidden" name="store_metrics_year" value="<?php echo esc_attr( $selected_year ); ?>">
                <input type="hidden" name="store_metrics_month" value="<?php echo esc_attr( $selected_month ); ?>">
                <p>
                    <input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Refresh Statistics', 'store-metrics' ); ?>">
                </p>
            </form>
        </div>
        <?php
    }
} 
<?php
/**
 * Statistics Service.
 *
 * @package store-metrics
 */

namespace StoreMetrics\Statistics;

use StoreMetrics\Settings\SettingsInterface;
use StoreMetrics\Product\CostPriceManager;

/**
 * Service for collecting statistics.
 */
class StatisticsService implements StatisticsInterface {
    /**
     * Settings manager instance.
     *
     * @var SettingsInterface
     */
    private $settings;

    /**
     * Cost price manager instance.
     *
     * @var CostPriceManager
     */
    private $cost_price_manager;

    /**
     * Statistics calculator instance.
     *
     * @var StatisticsCalculator
     */
    private $calculator;

    /**
     * Constructor.
     *
     * @param SettingsInterface $settings Settings manager instance.
     * @param CostPriceManager $cost_price_manager Cost price manager instance.
     */
    public function __construct( SettingsInterface $settings, CostPriceManager $cost_price_manager ) {
        $this->settings = $settings;
        $this->cost_price_manager = $cost_price_manager;
        $this->calculator = new StatisticsCalculator( $settings );
        
        // Register admin_post action for refresh.
        add_action( 'admin_post_store_metrics_refresh', array( $this, 'handle_refresh_stats' ) );
    }

    /**
     * Handle refresh statistics action.
     *
     * @return void
     */
    public function handle_refresh_stats(): void {
        // Verify nonce.
        if ( ! isset( $_POST['store_metrics_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['store_metrics_nonce'] ) ), 'store_metrics_refresh_action' ) ) {
            wp_die( esc_html__( 'Nonce verification failed', 'store-metrics' ) );
        }

        $year  = isset( $_POST['store_metrics_year'] ) ? intval( $_POST['store_metrics_year'] ) : date( 'Y' );
        $month = isset( $_POST['store_metrics_month'] ) ? intval( $_POST['store_metrics_month'] ) : date( 'n' );

        // Additional actions to refresh statistics can be added here.
        $redirect_url = add_query_arg(
            array(
                'store_metrics_notice' => esc_attr__( 'Statistics refreshed successfully!', 'store-metrics' ),
                'store_metrics_year'   => $year,
                'store_metrics_month'  => $month,
            ),
            admin_url( 'admin.php?page=store-metrics' )
        );
        wp_safe_redirect( $redirect_url );
        exit;
    }

    /**
     * Get top products.
     *
     * @param int $year Year.
     * @param int $month Month.
     * @param int $limit Limit.
     * @return array
     */
    public function get_top_products( int $year, int $month, int $limit = 5 ): array {
        $args = array(
            'limit'      => -1,
            'status'     => $this->get_valid_order_statuses(),
            'return'     => 'ids',
            'date_query' => $this->get_date_query($year, $month),
        );
        $order_ids = wc_get_orders( $args );
        
        $product_sales = array();
        
        foreach ( $order_ids as $order_id ) {
            $order = wc_get_order( absint( $order_id ) );
            if ( ! $order ) {
                continue;
            }
            
            foreach ( $order->get_items() as $item ) {
                $product_id = $item->get_product_id();
                $quantity = $item->get_quantity();
                
                if ( ! isset( $product_sales[$product_id] ) ) {
                    $product_sales[$product_id] = 0;
                }
                $product_sales[$product_id] += $quantity;
            }
        }
        
        if ( empty( $product_sales ) ) {
            return array();
        }

        arsort( $product_sales );
        $top_products = array_slice( $product_sales, 0, $limit, true );
        $result = array();

        foreach ( $top_products as $product_id => $sales_count ) {
            $product = wc_get_product( $product_id );
            if ( ! $product ) {
                continue;
            }

            $result[] = array(
                'id' => $product_id,
                'name' => $product->get_name(),
                'permalink' => get_permalink( $product_id ),
                'image' => wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' ),
                'sales_count' => $sales_count,
                'price' => $product->get_price(),
                'cost_price' => $this->cost_price_manager->get_product_cost_price( $product_id )
            );
        }
        
        return $result;
    }

    /**
     * Get total sales.
     *
     * @param int $year Year.
     * @param int $month Month.
     * @return float
     */
    public function get_total_sales( int $year, int $month ): float {
        $args = array(
            'limit'      => -1,
            'status'     => $this->get_valid_order_statuses(),
            'return'     => 'ids',
            'date_query' => $this->get_date_query($year, $month),
        );
        $order_ids  = wc_get_orders( $args );
        $total_sales = 0;
        foreach ( $order_ids as $order_id ) {
            $order = wc_get_order( absint( $order_id ) );
            if ( $order ) {
                $total_sales += floatval( $order->get_total() );
            }
        }
        return $total_sales;
    }

    /**
     * Get total deals.
     *
     * @param int $year Year.
     * @param int $month Month.
     * @return int
     */
    public function get_total_deals( int $year, int $month ): int {
        $args = array(
            'limit'      => -1,
            'status'     => $this->get_valid_order_statuses(),
            'return'     => 'ids',
            'date_query' => $this->get_date_query($year, $month),
        );
        $order_ids = wc_get_orders( $args );
        return count( $order_ids );
    }

    /**
     * Calculate total cost price from all valid order items.
     *
     * @param int $year Year.
     * @param int $month Month.
     * @return float
     */
    public function get_total_cost_price( int $year, int $month ): float {
        $args = array(
            'limit'      => -1,
            'status'     => $this->get_valid_order_statuses(),
            'return'     => 'ids',
            'date_query' => $this->get_date_query($year, $month),
        );
        $order_ids = wc_get_orders( $args );
        $total_cost = 0;

        foreach ( $order_ids as $order_id ) {
            $order = wc_get_order( absint( $order_id ) );
            if ( ! $order ) {
                continue;
            }

            foreach ( $order->get_items() as $item ) {
                $product_id = $item->get_product_id();
                $quantity = $item->get_quantity();
                $cost_price = $this->cost_price_manager->get_product_cost_price( $product_id );
                $total_cost += $cost_price * $quantity;
            }
        }

        return $total_cost;
    }

    /**
     * Calculate ROI.
     *
     * @param int $year Year.
     * @param int $month Month.
     * @return string Formatted ROI percentage.
     */
    public function calculate_roi( int $year, int $month ): string {
        $total_sales = $this->get_total_sales($year, $month);
        $pr_budget = $this->settings->get_monthly_value( 'store_metrics_new_pr_budget', $year, $month );
        $additional_costs = $this->settings->get_monthly_value( 'store_metrics_new_additional_costs', $year, $month );
        $total_cost_price = $this->get_total_cost_price($year, $month);
        
        return $this->calculator->calculate_roi($total_sales, $total_cost_price, $pr_budget, $additional_costs);
    }

    /**
     * Get array of valid order statuses for statistics.
     *
     * @return array
     */
    private function get_valid_order_statuses(): array {
        return array(
            'wc-completed',
            'wc-processing',
            'wc-on-hold'
        );
    }

    /**
     * Get date query from year and month.
     *
     * @param int $year Year.
     * @param int $month Month.
     * @return array
     */
    private function get_date_query( int $year, int $month ): array {
        return array(
            array(
                'year'  => $year,
                'month' => $month,
            )
        );
    }
} 
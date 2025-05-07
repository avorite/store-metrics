<?php
/**
 * Settings Manager.
 *
 * @package store-metrics
 */

namespace StoreMetrics\Settings;

/**
 * Class for managing plugin settings.
 */
class SettingsManager implements SettingsInterface {
    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    /**
     * Register plugin settings.
     *
     * @return void
     */
    public function register_settings() {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }
        register_setting( 
            'store_metrics_new_options', 
            'store_metrics_new_pr_budget_monthly', 
            array(
                'type' => 'array',
                'sanitize_callback' => array( $this, 'sanitize_monthly_data' ),
                'default' => array()
            )
        );
        register_setting( 
            'store_metrics_new_options', 
            'store_metrics_new_additional_costs_monthly', 
            array(
                'type' => 'array',
                'sanitize_callback' => array( $this, 'sanitize_monthly_data' ),
                'default' => array()
            )
        );
        
        add_settings_section(
            'store_metrics_new_settings_section',
            esc_html__( 'ROI Settings', 'store-metrics' ),
            null,
            'store_metrics_new'
        );

        add_settings_field(
            'store_metrics_new_pr_budget',
            esc_html__( 'PR Budget', 'store-metrics' ),
            array( $this, 'pr_budget_callback' ),
            'store_metrics_new',
            'store_metrics_new_settings_section'
        );

        add_settings_field(
            'store_metrics_new_additional_costs',
            esc_html__( 'Additional Costs', 'store-metrics' ),
            array( $this, 'additional_costs_callback' ),
            'store_metrics_new',
            'store_metrics_new_settings_section'
        );
    }

    /**
     * Sanitize monthly data.
     *
     * @param array $input Input data.
     * @return array Sanitized data.
     */
    public function sanitize_monthly_data( $input ) {
        if ( !is_array( $input ) ) {
            return array();
        }

        // Get existing data first
        $existing_data = array();
        if (current_filter() === 'sanitize_option_store_metrics_new_pr_budget_monthly') {
            $existing_data = get_option('store_metrics_new_pr_budget_monthly', array());
        } elseif (current_filter() === 'sanitize_option_store_metrics_new_additional_costs_monthly') {
            $existing_data = get_option('store_metrics_new_additional_costs_monthly', array());
        }

        // Process each value in input
        foreach ($input as $key => $value) {
            if (preg_match('/^\d{4}-\d{2}$/', $key)) { // Validate key format (YYYY-MM)
                $existing_data[$key] = floatval($value);
            }
        }

        return $existing_data;
    }

    /**
     * Get monthly value.
     *
     * @param string $option_name Option name.
     * @param int $year Year.
     * @param int $month Month.
     * @return float
     */
    public function get_monthly_value( $option_name, $year, $month ) {
        $monthly_data = get_option( $option_name . '_monthly', array() );
        $key = $year . '-' . str_pad( $month, 2, '0', STR_PAD_LEFT );
        return isset( $monthly_data[$key] ) ? floatval( $monthly_data[$key] ) : 0;
    }

    /**
     * Callback for PR budget field.
     *
     * @return void
     */
    public function pr_budget_callback() {
        $year = isset( $_GET['store_metrics_year'] ) ? intval( $_GET['store_metrics_year'] ) : date( 'Y' );
        $month = isset( $_GET['store_metrics_month'] ) ? intval( $_GET['store_metrics_month'] ) : date( 'n' );
        $key = $year . '-' . str_pad( $month, 2, '0', STR_PAD_LEFT );
        $pr_budget = $this->get_monthly_value( 'store_metrics_new_pr_budget', $year, $month );
        
        // Add hidden field to preserve other months' values
        $monthly_data = get_option( 'store_metrics_new_pr_budget_monthly', array() );
        foreach ($monthly_data as $existing_key => $value) {
            if ($existing_key !== $key) {
                echo '<input type="hidden" name="store_metrics_new_pr_budget_monthly[' . esc_attr($existing_key) . ']" value="' . esc_attr($value) . '" />';
            }
        }
        
        echo '<input type="number" step="0.01" name="store_metrics_new_pr_budget_monthly[' . esc_attr($key) . ']" value="' . esc_attr( $pr_budget ) . '" />';
        echo '<p class="description">' . sprintf( 
            esc_html__( 'PR Budget for %s %d', 'store-metrics' ),
            date_i18n( 'F', mktime( 0, 0, 0, $month, 1 ) ),
            $year
        ) . '</p>';
    }

    /**
     * Callback for additional costs field.
     *
     * @return void
     */
    public function additional_costs_callback() {
        $year = isset( $_GET['store_metrics_year'] ) ? intval( $_GET['store_metrics_year'] ) : date( 'Y' );
        $month = isset( $_GET['store_metrics_month'] ) ? intval( $_GET['store_metrics_month'] ) : date( 'n' );
        $key = $year . '-' . str_pad( $month, 2, '0', STR_PAD_LEFT );
        $additional_costs = $this->get_monthly_value( 'store_metrics_new_additional_costs', $year, $month );
        
        // Add hidden field to preserve other months' values
        $monthly_data = get_option( 'store_metrics_new_additional_costs_monthly', array() );
        foreach ($monthly_data as $existing_key => $value) {
            if ($existing_key !== $key) {
                echo '<input type="hidden" name="store_metrics_new_additional_costs_monthly[' . esc_attr($existing_key) . ']" value="' . esc_attr($value) . '" />';
            }
        }
        
        echo '<input type="number" step="0.01" name="store_metrics_new_additional_costs_monthly[' . esc_attr($key) . ']" value="' . esc_attr( $additional_costs ) . '" />';
        echo '<p class="description">' . sprintf( 
            esc_html__( 'Additional Costs for %s %d', 'store-metrics' ),
            date_i18n( 'F', mktime( 0, 0, 0, $month, 1 ) ),
            $year
        ) . '</p>';
    }
} 
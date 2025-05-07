<?php
/**
 * Statistics Calculator.
 *
 * @package store-metrics
 */

namespace StoreMetrics\Statistics;

use StoreMetrics\Settings\SettingsInterface;

/**
 * Calculator for statistical data.
 */
class StatisticsCalculator {
    /**
     * Settings manager instance.
     *
     * @var SettingsInterface
     */
    private $settings;

    /**
     * Constructor.
     *
     * @param SettingsInterface $settings Settings manager instance.
     */
    public function __construct( SettingsInterface $settings ) {
        $this->settings = $settings;
    }

    /**
     * Calculate ROI based on provided data.
     *
     * @param float $total_sales Total sales amount.
     * @param float $total_cost_price Total cost price.
     * @param float $pr_budget PR budget.
     * @param float $additional_costs Additional costs.
     * @return string Formatted ROI percentage.
     */
    public function calculate_roi( $total_sales, $total_cost_price, $pr_budget, $additional_costs ) {
        $investment = $pr_budget + $total_cost_price + $additional_costs;
        
        if ( $investment <= 0 ) {
            return '0%';
        }
        
        $roi = ( $total_sales - $investment ) / $investment;
        return number_format( $roi * 100, 2 ) . '%';
    }
} 
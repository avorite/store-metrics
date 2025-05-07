<?php
/**
 * Statistics Interface.
 *
 * @package store-metrics
 */

namespace StoreMetrics\Statistics;

/**
 * Interface for statistics services.
 */
interface StatisticsInterface {
    /**
     * Get top products.
     *
     * @param int $year Year.
     * @param int $month Month.
     * @param int $limit Limit.
     * @return array
     */
    public function get_top_products( $year, $month, $limit = 5 );

    /**
     * Get total sales.
     *
     * @param int $year Year.
     * @param int $month Month.
     * @return float
     */
    public function get_total_sales( $year, $month );

    /**
     * Get total deals.
     *
     * @param int $year Year.
     * @param int $month Month.
     * @return int
     */
    public function get_total_deals( $year, $month );

    /**
     * Calculate ROI.
     *
     * @param int $year Year.
     * @param int $month Month.
     * @return string Formatted ROI percentage.
     */
    public function calculate_roi( $year, $month );
} 
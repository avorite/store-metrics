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
    public function get_top_products( int $year, int $month, int $limit = 5 ): array;

    /**
     * Get total sales.
     *
     * @param int $year Year.
     * @param int $month Month.
     * @return float
     */
    public function get_total_sales( int $year, int $month ): float;

    /**
     * Get total deals.
     *
     * @param int $year Year.
     * @param int $month Month.
     * @return int
     */
    public function get_total_deals( int $year, int $month ): int;

    /**
     * Calculate ROI.
     *
     * @param int $year Year.
     * @param int $month Month.
     * @return string Formatted ROI percentage.
     */
    public function calculate_roi( int $year, int $month ): string;
} 
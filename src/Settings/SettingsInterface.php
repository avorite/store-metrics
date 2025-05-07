<?php
/**
 * Settings Interface.
 *
 * @package store-metrics
 */

namespace StoreMetrics\Settings;

/**
 * Interface for settings management.
 */
interface SettingsInterface {
    /**
     * Register plugin settings.
     *
     * @return void
     */
    public function register_settings(): void;

    /**
     * Get monthly value.
     *
     * @param string $option_name Option name.
     * @param int $year Year.
     * @param int $month Month.
     * @return float
     */
    public function get_monthly_value( string $option_name, int $year, int $month ): float;
} 
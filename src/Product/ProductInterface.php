<?php
/**
 * Product Interface.
 *
 * @package store-metrics
 */

namespace StoreMetrics\Product;

/**
 * Interface for product management.
 */
interface ProductInterface {
    /**
     * Get product cost price.
     *
     * @param int $product_id Product ID.
     * @return float Cost price or 0 if not set.
     */
    public function get_product_cost_price( $product_id );
} 
<?php
/**
 * Product Repository.
 *
 * @package store-metrics
 */

namespace StoreMetrics\Product;

/**
 * Repository for product operations.
 */
class ProductRepository implements ProductInterface {
    /**
     * Cost price manager instance.
     *
     * @var CostPriceManager
     */
    private $cost_price_manager;

    /**
     * Constructor.
     * 
     * @param CostPriceManager $cost_price_manager Cost price manager instance.
     */
    public function __construct( CostPriceManager $cost_price_manager ) {
        $this->cost_price_manager = $cost_price_manager;
    }

    /**
     * Get product cost price.
     *
     * @param int $product_id Product ID.
     * @return float Cost price or 0 if not set.
     */
    public function get_product_cost_price( int $product_id ): float {
        return $this->cost_price_manager->get_product_cost_price( $product_id );
    }
} 
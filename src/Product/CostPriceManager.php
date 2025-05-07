<?php
/**
 * Cost Price Manager.
 *
 * @package store-metrics
 */

namespace StoreMetrics\Product;

/**
 * Class for managing product cost prices.
 */
class CostPriceManager {
    /**
     * Meta key for cost price.
     *
     * @var string
     */
    const COST_PRICE_META_KEY = '_store_metrics_new_cost_price';

    /**
     * Flag to track if the field has been added already.
     *
     * @var bool
     */
    private static $field_added = false;

    /**
     * Constructor.
     */
    public function __construct() {
        // Add Cost Price field to WooCommerce products.
        add_action( 'woocommerce_product_options_pricing', array( $this, 'add_cost_price_field' ) );
        add_action( 'woocommerce_process_product_meta', array( $this, 'save_cost_price_field' ) );
    }

    /**
     * Add Cost Price field to WooCommerce product pricing section.
     *
     * @return void
     */
    public function add_cost_price_field(): void {
        // Prevent adding the field multiple times
        if (self::$field_added) {
            return;
        }

        woocommerce_wp_text_input( array(
            'id'          => self::COST_PRICE_META_KEY,
            'label'       => __( 'Cost Price', 'store-metrics' ),
            'desc_tip'    => true,
            'description' => __( 'Enter the cost price for this product', 'store-metrics' ),
            'type'        => 'number',
            'custom_attributes' => array(
                'step' => 'any',
                'min'  => '0'
            )
        ) );

        // Mark field as added
        self::$field_added = true;
    }

    /**
     * Save Cost Price field value.
     *
     * @param int $post_id Product ID.
     * @return void
     */
    public function save_cost_price_field( int $post_id ): void {
        $cost_price = isset( $_POST[self::COST_PRICE_META_KEY] ) ? wc_clean( wp_unslash( $_POST[self::COST_PRICE_META_KEY] ) ) : '';
        update_post_meta( $post_id, self::COST_PRICE_META_KEY, $cost_price );
    }

    /**
     * Get product cost price.
     *
     * @param int $product_id Product ID.
     * @return float Cost price or 0 if not set.
     */
    public function get_product_cost_price( int $product_id ): float {
        $cost_price = get_post_meta( $product_id, self::COST_PRICE_META_KEY, true );
        return ! empty( $cost_price ) ? floatval( $cost_price ) : 0;
    }
} 
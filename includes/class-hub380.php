<?php
/**
 * Main class for hub380 Plugin.
 *
 * @package hub380
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Hub380' ) ) :

class Hub380 {

	/**
	 * Hook suffix for admin page.
	 *
	 * @var string
	 */
	private $hook_suffix = '';

	/**
	 * Constructor.
	 *
	 * We hook our initialization to 'plugins_loaded' so that WooCommerce has time to load.
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init_plugin' ), 11 );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		
		// Add Cost Price field to WooCommerce products
		add_action( 'woocommerce_product_options_pricing', array( $this, 'add_cost_price_field' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_cost_price_field' ) );
	}

	/**
	 * Initialize plugin functionality after all plugins have loaded.
	 */
	public function init_plugin() {
		// Check if WooCommerce is active by testing for WC_VERSION constant.
		if ( ! defined( 'WC_VERSION' ) ) {
			add_action( 'admin_notices', array( $this, 'woocommerce_inactive_notice' ) );
			return;
		}

		// Load translation files.
		add_action( 'init', array( $this, 'load_textdomain' ) );

		// Register admin menu.
		$this->register_admin_menu();

		// Enqueue admin assets.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// Register admin_post action for refresh.
		add_action( 'admin_post_hub380_refresh', array( $this, 'handle_refresh_stats' ) );
	}

	/**
	 * Display notice if WooCommerce is not active.
	 */
	public function woocommerce_inactive_notice() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		$woocommerce_path = 'woocommerce/woocommerce.php';
		$is_woocommerce_installed = file_exists( WP_PLUGIN_DIR . '/' . $woocommerce_path );

		if ( $is_woocommerce_installed ) {
			$action_url = wp_nonce_url( 
				add_query_arg(
					array(
						'action' => 'activate',
						'plugin' => $woocommerce_path
					),
					admin_url( 'plugins.php' )
				),
				'activate-plugin_' . $woocommerce_path
			);
			$message = sprintf(
				/* translators: %1$s: Plugin name, %2$s: WooCommerce activation link */
				esc_html__( '%1$s requires WooCommerce to be active. Please %2$s.', 'hub380' ),
				'<strong>Hub380</strong>',
				'<a href="' . esc_url( $action_url ) . '">' . esc_html__( 'activate WooCommerce', 'hub380' ) . '</a>'
			);
		} else {
			$action_url = wp_nonce_url(
				add_query_arg(
					array(
						'action' => 'install-plugin',
						'plugin' => 'woocommerce'
					),
					admin_url( 'update.php' )
				),
				'install-plugin_woocommerce'
			);
			$message = sprintf(
				/* translators: %1$s: Plugin name, %2$s: WooCommerce installation link */
				esc_html__( '%1$s requires WooCommerce to be installed and active. Please %2$s.', 'hub380' ),
				'<strong>Hub380</strong>',
				'<a href="' . esc_url( $action_url ) . '">' . esc_html__( 'install WooCommerce', 'hub380' ) . '</a>'
			);
		}

		echo '<div class="notice notice-error"><p>' . wp_kses_post( $message ) . '</p></div>';
	}

	/**
	 * Load plugin text domain.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'hub380', false, dirname( plugin_basename( __FILE__ ) ) . '/../languages' );
	}

	/**
	 * Register admin menu page.
	 */
	public function register_admin_menu() {
		if (!current_user_can('manage_woocommerce')) {
			return;
		}
		$this->hook_suffix = add_menu_page(
			esc_html__( 'Store Metrics', 'hub380' ),
			esc_html__( 'Store Metrics', 'hub380' ),
			'manage_woocommerce',
			'hub380',
			array( $this, 'admin_page_callback' ),
			'dashicons-chart-area',
			56
		);
	}

	/**
	 * Enqueue admin CSS and JS files.
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		// Load assets only on our plugin admin page.
		if ( $hook !== $this->hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'hub380-admin-style',
			plugin_dir_url( __FILE__ ) . '../admin/css/hub380-admin.css',
			array(),
			'1.0.0'
		);

		wp_enqueue_script(
			'hub380-admin-script',
			plugin_dir_url( __FILE__ ) . '../admin/js/hub380-admin.js',
			array( 'jquery' ),
			'1.0.0',
			true
		);
	}

	/**
	 * Get top 5 products based on sales in selected period.
	 *
	 * @return array Array of product data
	 */
	private function get_top_products() {
		$args = array(
			'limit'      => -1,
			'status'     => $this->get_valid_order_statuses(),
			'return'     => 'ids',
			'date_query' => $this->get_date_query(),
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
		$top_products = array_slice( $product_sales, 0, 5, true );
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
				'cost_price' => $this->get_product_cost_price( $product_id )
			);
		}
		
		return $result;
	}

	/**
	 * Callback function for admin page.
	 */
	public function admin_page_callback() {
		if (!current_user_can('manage_woocommerce')) {
			wp_die(__('You do not have sufficient permissions to access this page.', 'hub380'));
		}
		$selected_year  = isset( $_GET['hub380_year'] ) ? intval( sanitize_text_field( $_GET['hub380_year'] ) ) : date( 'Y' );
		$selected_month = isset( $_GET['hub380_month'] ) ? intval( sanitize_text_field( $_GET['hub380_month'] ) ) : date( 'n' );

		$top_products = $this->get_top_products();
		$total_sales  = $this->get_total_sales();
		$total_deals  = $this->get_total_deals();
		$roi          = $this->calculate_roi( $total_sales );

		// Check for admin notice
		$notice = ( isset( $_GET['hub380_notice'] ) ) ? sanitize_text_field( wp_unslash( $_GET['hub380_notice'] ) ) : '';
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Store Metrics', 'hub380' ); ?></h1>
			
			<?php if ( ! empty( $notice ) ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php echo esc_html( $notice ); ?></p>
				</div>
			<?php endif; ?>

			<!-- Period Selection Form -->
			<div class="hub380-period-selection">
				<form method="get" action="">
					<input type="hidden" name="page" value="hub380">
					<select name="hub380_year" id="hub380_year">
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

					<select name="hub380_month" id="hub380_month">
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

					<input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'hub380' ); ?>">
				</form>
			</div>

			<!-- Settings Form -->
			<form method="post" action="options.php">
				<?php
				settings_fields( 'hub380_options' );
				do_settings_sections( 'hub380' );
				submit_button( esc_html__( 'Save Settings', 'hub380' ) );
				?>
			</form>

			<hr />

			<!-- Top Products Table -->
			<h2><?php esc_html_e( 'Top 5 Products', 'hub380' ); ?></h2>
			<?php if ( ! empty( $top_products ) ) : ?>
				<table class="widefat" cellspacing="0">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Image', 'hub380' ); ?></th>
							<th><?php esc_html_e( 'Product', 'hub380' ); ?></th>
							<th><?php esc_html_e( 'Sales', 'hub380' ); ?></th>
							<th><?php esc_html_e( 'Price', 'hub380' ); ?></th>
							<th><?php esc_html_e( 'Cost Price', 'hub380' ); ?>*</th>
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
				<p class="description hub380-cost-price-info">
					<?php esc_html_e( '* Cost Price is the product cost value that can be set on the product edit page in the General tab.', 'hub380' ); ?>
				</p>
			<?php else : ?>
				<p><?php esc_html_e( 'No products found for selected period', 'hub380' ); ?></p>
			<?php endif; ?>

			<hr />

			<!-- Statistics Table -->
			<h2><?php esc_html_e( 'Current Statistics', 'hub380' ); ?></h2>
			<table class="widefat fixed" cellspacing="0">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Metric', 'hub380' ); ?></th>
						<th><?php esc_html_e( 'Value', 'hub380' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><?php esc_html_e( 'Total Sales', 'hub380' ); ?></td>
						<td><?php echo wc_price( $total_sales ); ?></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Total Deals', 'hub380' ); ?></td>
						<td><?php echo esc_html( $total_deals ); ?></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'ROI', 'hub380' ); ?></td>
						<td><?php echo esc_html( $roi ); ?></td>
					</tr>
				</tbody>
			</table>

			<!-- Refresh Statistics Form -->
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="hub380-refresh-form">
				<?php wp_nonce_field( 'hub380_refresh_action', 'hub380_nonce' ); ?>
				<input type="hidden" name="action" value="hub380_refresh">
				<input type="hidden" name="hub380_year" value="<?php echo esc_attr( $selected_year ); ?>">
				<input type="hidden" name="hub380_month" value="<?php echo esc_attr( $selected_month ); ?>">
				<p>
					<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Refresh Statistics', 'hub380' ); ?>">
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Handle refresh statistics action.
	 *
	 * This function verifies the nonce and redirects back with a notice.
	 */
	public function handle_refresh_stats() {
		// Verify nonce.
		if ( ! isset( $_POST['hub380_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['hub380_nonce'] ) ), 'hub380_refresh_action' ) ) {
			wp_die( esc_html__( 'Nonce verification failed', 'hub380' ) );
		}

		$year  = isset( $_POST['hub380_year'] ) ? intval( $_POST['hub380_year'] ) : date( 'Y' );
		$month = isset( $_POST['hub380_month'] ) ? intval( $_POST['hub380_month'] ) : date( 'n' );

		// Additional actions to refresh statistics can be added here.
		$redirect_url = add_query_arg(
			array(
				'hub380_notice' => esc_attr__( 'Statistics refreshed successfully!', 'hub380' ),
				'hub380_year'   => $year,
				'hub380_month'  => $month,
			),
			admin_url( 'admin.php?page=hub380' )
		);
		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Get the most popular product based on total sales meta.
	 *
	 * @return string
	 */
	private function get_most_popular_product() {
		$args = array(
			'limit'      => -1,
			'status'     => $this->get_valid_order_statuses(),
			'return'     => 'ids',
			'date_query' => $this->get_date_query(),
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
		
		if ( ! empty( $product_sales ) ) {
			arsort( $product_sales );
			$top_product_id = array_key_first( $product_sales );
			$product = wc_get_product( $top_product_id );
			if ( $product ) {
				return sprintf(
					'%s (%d %s)',
					$product->get_name(),
					$product_sales[$top_product_id],
					_n( 'sale', 'sales', $product_sales[$top_product_id], 'hub380' )
				);
			}
		}
		
		return esc_html__( 'No product found', 'hub380' );
	}

	/**
	 * Get array of valid order statuses for statistics
	 *
	 * @return array
	 */
	private function get_valid_order_statuses() {
		return array(
			'wc-completed',
			'wc-processing',
			'wc-on-hold'
		);
	}

	/**
	 * Get total sales from valid orders.
	 *
	 * @return float
	 */
	private function get_total_sales() {
		$args = array(
			'limit'      => -1,
			'status'     => $this->get_valid_order_statuses(),
			'return'     => 'ids',
			'date_query' => $this->get_date_query(),
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
	 * Get total number of valid orders.
	 *
	 * @return int
	 */
	private function get_total_deals() {
		$args = array(
			'limit'      => -1,
			'status'     => $this->get_valid_order_statuses(),
			'return'     => 'ids',
			'date_query' => $this->get_date_query(),
		);
		$order_ids = wc_get_orders( $args );
		return count( $order_ids );
	}

	/**
	 * Calculate total cost price from all valid order items.
	 *
	 * @return float
	 */
	private function get_total_cost_price() {
		$args = array(
			'limit'      => -1,
			'status'     => $this->get_valid_order_statuses(),
			'return'     => 'ids',
			'date_query' => $this->get_date_query(),
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
				$cost_price = $this->get_product_cost_price( $product_id );
				$total_cost += $cost_price * $quantity;
			}
		}

		return $total_cost;
	}

	/**
	 * Calculate ROI based on total sales, cost price and PR budget
	 *
	 * @param float $total_sales Total sales amount
	 * @return string Formatted ROI percentage
	 */
	private function calculate_roi( $total_sales ) {
		$year = isset( $_GET['hub380_year'] ) ? intval( $_GET['hub380_year'] ) : date( 'Y' );
		$month = isset( $_GET['hub380_month'] ) ? intval( $_GET['hub380_month'] ) : date( 'n' );
		
		$pr_budget = $this->get_monthly_value( 'hub380_pr_budget', $year, $month );
		$additional_costs = $this->get_monthly_value( 'hub380_additional_costs', $year, $month );
		$total_cost_price = $this->get_total_cost_price();
		
		$investment = $pr_budget + $total_cost_price + $additional_costs;
		
		if ( $investment <= 0 ) {
			return '0%';
		}
		
		$roi = ( $total_sales - $investment ) / $investment;
		return number_format( $roi * 100, 2 ) . '%';
	}

	/**
	 * Register plugin settings
	 */
	public function register_settings() {
		if (!current_user_can('manage_woocommerce')) {
			return;
		}
		register_setting( 
			'hub380_options', 
			'hub380_pr_budget_monthly', 
			array(
				'type' => 'array',
				'sanitize_callback' => array( $this, 'sanitize_monthly_data' ),
				'default' => array()
			)
		);
		register_setting( 
			'hub380_options', 
			'hub380_additional_costs_monthly', 
			array(
				'type' => 'array',
				'sanitize_callback' => array( $this, 'sanitize_monthly_data' ),
				'default' => array()
			)
		);
		
		add_settings_section(
			'hub380_settings_section',
			esc_html__( 'ROI Settings', 'hub380' ),
			null,
			'hub380'
		);

		add_settings_field(
			'hub380_pr_budget',
			esc_html__( 'PR Budget', 'hub380' ),
			array( $this, 'pr_budget_callback' ),
			'hub380',
			'hub380_settings_section'
		);

		add_settings_field(
			'hub380_additional_costs',
			esc_html__( 'Additional Costs', 'hub380' ),
			array( $this, 'additional_costs_callback' ),
			'hub380',
			'hub380_settings_section'
		);
	}

	/**
	 * Sanitize monthly data
	 */
	public function sanitize_monthly_data( $input ) {
		if ( !is_array( $input ) ) {
			return array();
		}

		// Get existing data first
		$existing_data = array();
		if (current_filter() === 'sanitize_option_hub380_pr_budget_monthly') {
			$existing_data = get_option('hub380_pr_budget_monthly', array());
		} elseif (current_filter() === 'sanitize_option_hub380_additional_costs_monthly') {
			$existing_data = get_option('hub380_additional_costs_monthly', array());
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
	 * Get monthly value
	 */
	private function get_monthly_value( $option_name, $year, $month ) {
		$monthly_data = get_option( $option_name . '_monthly', array() );
		$key = $year . '-' . str_pad( $month, 2, '0', STR_PAD_LEFT );
		return isset( $monthly_data[$key] ) ? floatval( $monthly_data[$key] ) : 0;
	}

	/**
	 * Callback for PR budget field
	 */
	public function pr_budget_callback() {
		$year = isset( $_GET['hub380_year'] ) ? intval( $_GET['hub380_year'] ) : date( 'Y' );
		$month = isset( $_GET['hub380_month'] ) ? intval( $_GET['hub380_month'] ) : date( 'n' );
		$key = $year . '-' . str_pad( $month, 2, '0', STR_PAD_LEFT );
		$pr_budget = $this->get_monthly_value( 'hub380_pr_budget', $year, $month );
		
		// Add hidden field to preserve other months' values
		$monthly_data = get_option( 'hub380_pr_budget_monthly', array() );
		foreach ($monthly_data as $existing_key => $value) {
			if ($existing_key !== $key) {
				echo '<input type="hidden" name="hub380_pr_budget_monthly[' . esc_attr($existing_key) . ']" value="' . esc_attr($value) . '" />';
			}
		}
		
		echo '<input type="number" step="0.01" name="hub380_pr_budget_monthly[' . esc_attr($key) . ']" value="' . esc_attr( $pr_budget ) . '" />';
		echo '<p class="description">' . sprintf( 
			esc_html__( 'PR Budget for %s %d', 'hub380' ),
			date_i18n( 'F', mktime( 0, 0, 0, $month, 1 ) ),
			$year
		) . '</p>';
	}

	/**
	 * Callback for additional costs field
	 */
	public function additional_costs_callback() {
		$year = isset( $_GET['hub380_year'] ) ? intval( $_GET['hub380_year'] ) : date( 'Y' );
		$month = isset( $_GET['hub380_month'] ) ? intval( $_GET['hub380_month'] ) : date( 'n' );
		$key = $year . '-' . str_pad( $month, 2, '0', STR_PAD_LEFT );
		$additional_costs = $this->get_monthly_value( 'hub380_additional_costs', $year, $month );
		
		// Add hidden field to preserve other months' values
		$monthly_data = get_option( 'hub380_additional_costs_monthly', array() );
		foreach ($monthly_data as $existing_key => $value) {
			if ($existing_key !== $key) {
				echo '<input type="hidden" name="hub380_additional_costs_monthly[' . esc_attr($existing_key) . ']" value="' . esc_attr($value) . '" />';
			}
		}
		
		echo '<input type="number" step="0.01" name="hub380_additional_costs_monthly[' . esc_attr($key) . ']" value="' . esc_attr( $additional_costs ) . '" />';
		echo '<p class="description">' . sprintf( 
			esc_html__( 'Additional Costs for %s %d', 'hub380' ),
			date_i18n( 'F', mktime( 0, 0, 0, $month, 1 ) ),
			$year
		) . '</p>';
	}

	/**
	 * Add Cost Price field to WooCommerce product pricing section
	 */
	public function add_cost_price_field() {
		woocommerce_wp_text_input( array(
			'id'          => '_hub380_cost_price',
			'label'       => __( 'Cost Price', 'hub380' ),
			'desc_tip'    => true,
			'description' => __( 'Enter the cost price for this product', 'hub380' ),
			'type'        => 'number',
			'custom_attributes' => array(
				'step' => 'any',
				'min'  => '0'
			)
		) );
	}

	/**
	 * Save Cost Price field value
	 *
	 * @param int $post_id Product ID
	 */
	public function save_cost_price_field( $post_id ) {
		$cost_price = isset( $_POST['_hub380_cost_price'] ) ? wc_clean( wp_unslash( $_POST['_hub380_cost_price'] ) ) : '';
		update_post_meta( $post_id, '_hub380_cost_price', $cost_price );
	}

	/**
	 * Get product cost price
	 *
	 * @param int $product_id Product ID
	 * @return float Cost price or 0 if not set
	 */
	public function get_product_cost_price( $product_id ) {
		$cost_price = get_post_meta( $product_id, '_hub380_cost_price', true );
		return ! empty( $cost_price ) ? floatval( $cost_price ) : 0;
	}

	/**
	 * Get date query from filter parameters (year and month)
	 *
	 * @return array
	 */
	private function get_date_query() {
		$year  = isset( $_GET['hub380_year'] ) ? intval( sanitize_text_field( $_GET['hub380_year'] ) ) : date( 'Y' );
		$month = isset( $_GET['hub380_month'] ) ? intval( sanitize_text_field( $_GET['hub380_month'] ) ) : date( 'n' );
		return array(
			array(
				'year'  => $year,
				'month' => $month,
			)
		);
	}
}

endif;

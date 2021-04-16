<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://gurukullab.com/
 * @since      1.0.0
 *
 * @package    Wc_Buy1_Get1_Free
 * @subpackage Wc_Buy1_Get1_Free/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wc_Buy1_Get1_Free
 * @subpackage Wc_Buy1_Get1_Free/admin
 * @author     Gurukul Lab <info@gurukullab.com>
 */
class Wc_Buy1_Get1_Free_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function wcbogo_admin_enqueue_scripts_callback() {
		global $post;

		if ( empty( $post->ID ) || 'product' !== get_post_type( $post->ID ) ) {
			return;
		}

		wp_enqueue_style(
			$this->plugin_name,
			WCBOGO_PLUGIN_URL . 'admin/css/wc-buy1-get1-free-admin.css',
			array(),
			filemtime( WCBOGO_PLUGIN_PATH . 'admin/css/wc-buy1-get1-free-admin.css' )
		);

		wp_enqueue_script(
			$this->plugin_name,
			WCBOGO_PLUGIN_URL . 'admin/js/wc-buy1-get1-free-admin.js',
			array( 'jquery' ),
			filemtime( WCBOGO_PLUGIN_PATH . 'admin/js/wc-buy1-get1-free-admin.js' ),
			true
		);

		wp_localize_script(
			$this->plugin_name,
			'WCBOGO_Admin_JS_Obj',
			array(
				'ajaxurl'                          => admin_url( 'admin-ajax.php' ),
				'free_product_select_empty_option' => __( 'Select a product', 'wcbogo' ),
			)
		);
	}

	/**
	 * Admin settings for buy one get one.
	 *
	 * @param array $sections Array of WC products tab sections.
	 */
	public function wcbogo_woocommerce_get_sections_products_callback( $sections ) {
		$sections['wcbogo'] = __( 'Buy One Get One', 'wcbogo' );

		return $sections;
	}

	/**
	 * Add custom section to WooCommerce settings products tab.
	 *
	 * @param array $settings Holds the woocommerce settings fields array.
	 * @param array $current_section Holds the wcbogo settings fields array.
	 * @return array
	 */
	public function wcbogo_woocommerce_get_settings_products_callback( $settings, $current_section ) {
		// Check the current section is what we want.
		if ( 'wcbogo' === $current_section ) {
			return $this->wcbogo_general_settings_fields();
		} else {
			return $settings;
		}
	}

	/**
	 * Return the fields for general settings.
	 *
	 * @return array
	 */
	private function wcbogo_general_settings_fields() {
		$offer_scopes_arr = array(
			'everyone'     => esc_html__( 'Everyone', 'wcbogo' ),
			'loggedin'     => esc_html__( 'Loggedin members', 'wcbogo' ),
			'non-loggedin' => esc_html__( 'Non-loggedin members', 'wcbogo' ),
		);
		/**
		 * Offer scopes.
		 *
		 * Filter allowed to modify offer scopes. User can add custom scopes as per the need.
		 *
		 * @param array $offer_scopes_arr Holds the offer scope array.
		 * @return array
		 */
		$scope_options = apply_filters( 'wcbogo_offer_scopes', $offer_scopes_arr );

		return apply_filters(
			'woocommerce_wcbogo_general_settings',
			array(
				array(
					'title' => __( 'BOGO Settings', 'wcbogo' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'wcbogo_general_settings_title',
				),
				array(
					'name'     => esc_html__( 'Scope', 'wcbogo' ),
					'type'     => 'select',
					'options'  => $scope_options,
					'class'    => 'wc-enhanced-select',
					'desc'     => esc_html__( 'This holds the offer scope.', 'wcbogo' ),
					'desc_tip' => true,
					'default'  => '',
					'id'       => 'wcbogo_offer_scope',
				),
				array(
					'name'    => esc_html__( 'Disable coupons', 'wcbogo' ),
					'type'    => 'checkbox',
					'desc'    => esc_html__( 'Disable the coupons usage if there is a BOGO item in the cart.', 'wcbogo' ),
					'default' => 'yes',
					'id'      => 'wcbogo_disable_coupons',
				),
				array(
					'type' => 'sectionend',
					'id'   => 'wcbogo_general_settings_end',
				),
			)
		);
	}

	/**
	 * Add a custom tab in woocommerce product edit data.
	 *
	 * @since    1.0.0
	 * @param array $tabs Array of product data tabs.
	 * @return array
	 */
	public function wcbogo_woocommerce_product_data_tabs_callback( $tabs = array() ) {
		global $post;

		if ( empty( $post->ID ) ) {
			return $tabs;
		}

		$product = wc_get_product( $post->ID );

		if ( false === $product ) {
			return $tabs;
		}

		if ( ! $product->is_type( 'simple' ) ) {
			return $tabs;
		}

		if ( array_key_exists( wcbogo_free_product_key(), $tabs ) ) {
			return $tabs;
		}

		$tabs[ wcbogo_free_product_key() ] = array(
			'label'    => __( 'Free Product', 'wcbogo' ),
			'target'   => wcbogo_free_product_key(),
			'class'    => array( 'wcbogo-free-product-tab' ),
			'priority' => 80,
		);

		return $tabs;
	}

	/**
	 * Add html markup for associated free product selection.
	 *
	 * @return void
	 */
	public function wcbogo_woocommerce_product_data_panels_callback() {
		global $post;

		if ( empty( $post->ID ) ) {
			return;
		}

		$product_id = $post->ID;
		$product    = wc_get_product( $post->ID );

		if ( false === $product ) {
			return;
		}

		if ( ! $product->is_type( 'simple' ) ) {
			return;
		}

		echo $this->wcbogo_product_meta_settings( $product_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Ignore this line as it disturbs the select box rendering.
	}

	/**
	 * AJAX served to fetch products.
	 */
	public function wcbogo_fetch_products_callback() {
		$action = filter_input( INPUT_POST, 'action', FILTER_SANITIZE_STRING );

		if ( ! empty( $action ) && 'wcbogo_fetch_products' === $action ) {
			$product_id   = filter_input( INPUT_POST, 'product_id', FILTER_SANITIZE_STRING );
			$free_porduct = get_post_meta( $product_id, 'wcbogo_free_product', true );
			$product_ids  = wcbogo_get_final_products_list( -1 );
			$products     = array();

			if ( ! empty( $product_ids ) && is_array( $product_ids ) ) {
				foreach ( $product_ids as $product_id ) {
					$products[] = array(
						'id'    => $product_id,
						'title' => get_the_title( $product_id ),
					);
				}
			}

			wp_send_json_success(
				array(
					'code'         => 'wcbogo-products-fetched',
					'products'     => $products,
					'free_product' => (int) $free_porduct,
				)
			);
			wp_die();
		}
	}

	/**
	 * Save the selected free product.
	 *
	 * @since    1.0.0
	 * @param int $product_id Holds the product ID.
	 */
	public function wcbogo_woocommerce_process_product_meta_callback( $product_id ) {
		$this->wcbogo_update_product_meta( $product_id );
	}

	/**
	 * Update product meta for free product data.
	 *
	 * @param int    $product_id Holds the product ID.
	 * @param string $loop Holds the loop index for variations listing.
	 */
	private function wcbogo_update_product_meta( $product_id, $loop = '' ) {
		if ( ! empty( $loop ) && is_int( $loop ) ) {
			$quantity_to_buy      = ( ! empty( $_POST['wcbogo-quantity-to-buy'][ $loop ] ) ) ? wp_unslash( $_POST['wcbogo-quantity-to-buy'][ $loop ] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$quantity_free_given  = ( ! empty( $_POST['wcbogo-quantity-of-free-product-given'][ $loop ] ) ) ? wp_unslash( $_POST['wcbogo-quantity-of-free-product-given'][ $loop ] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$free_product         = ( ! empty( $_POST['wcbogo-free-product'][ $loop ] ) ) ? wp_unslash( $_POST['wcbogo-free-product'][ $loop ] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$free_product_message = ( ! empty( $_POST['wcbogo-message-for-free-product'][ $loop ] ) ) ? wp_unslash( $_POST['wcbogo-message-for-free-product'][ $loop ] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$offer_start          = ( ! empty( $_POST['wcbogo-offer-start'][ $loop ] ) ) ? wp_unslash( $_POST['wcbogo-offer-start'][ $loop ] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$offer_end            = ( ! empty( $_POST['wcbogo-offer-end'][ $loop ] ) ) ? wp_unslash( $_POST['wcbogo-offer-end'][ $loop ] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		} else {
			$quantity_to_buy      = filter_input( INPUT_POST, 'wcbogo-quantity-to-buy', FILTER_SANITIZE_NUMBER_INT );
			$quantity_free_given  = filter_input( INPUT_POST, 'wcbogo-quantity-of-free-product-given', FILTER_SANITIZE_NUMBER_INT );
			$free_product         = filter_input( INPUT_POST, 'wcbogo-free-product', FILTER_SANITIZE_NUMBER_INT );
			$free_product_message = filter_input( INPUT_POST, 'wcbogo-message-for-free-product', FILTER_SANITIZE_STRING );
			$offer_start          = filter_input( INPUT_POST, 'wcbogo-offer-start', FILTER_SANITIZE_STRING );
			$offer_end            = filter_input( INPUT_POST, 'wcbogo-offer-end', FILTER_SANITIZE_STRING );
		}

		// Update the database now.
		if ( ! empty( $quantity_to_buy ) ) {
			update_post_meta( $product_id, 'wcbogo_quantity_to_buy', $quantity_to_buy );
		}

		if ( ! empty( $quantity_free_given ) ) {
			update_post_meta( $product_id, 'wcbogo_quantity_of_free_product_given', $quantity_free_given );
		}

		if ( ! empty( $free_product ) ) {
			update_post_meta( $product_id, 'wcbogo_free_product', $free_product );
		}

		if ( ! empty( $free_product_message ) ) {
			update_post_meta( $product_id, 'wcbogo_message_for_free_product', $free_product_message );
		}

		if ( ! empty( $offer_start ) ) {
			update_post_meta( $product_id, 'wcbogo_offer_start', $offer_start );
		}

		if ( ! empty( $offer_end ) ) {
			update_post_meta( $product_id, 'wcbogo_offer_end', $offer_end );
		}
	}

	/**
	 * HTML markup to select the free product for variations.
	 *
	 * @param int    $loop Holds the loop index value.
	 * @param array  $variation_data Holds the variation basic data.
	 * @param object $variation Holds the variation post object data.
	 */
	public function wcbogo_woocommerce_product_after_variable_attributes_callback( $loop, $variation_data, $variation ) {
		$product_id = $variation->ID;
		echo $this->wcbogo_product_meta_settings( $product_id, $loop ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Ignore this line as it disturbs the select box rendering.
	}

	/**
	 * Save the free product/variation.
	 *
	 * @param int $variation_id Holds the variation ID.
	 * @param int $loop Holds the loop index for variations listing.
	 */
	public function wcbogo_woocommerce_save_product_variation_callback( $variation_id, $loop ) {
		$this->wcbogo_update_product_meta( $variation_id, $loop );
	}

	/**
	 * Return the product meta settings.
	 *
	 * @param int $product_id Holds the product ID.
	 * @param int $loop Holds the loop index value.
	 * @return string
	 */
	private function wcbogo_product_meta_settings( $product_id, $loop = '' ) {
		$free_porduct = get_post_meta( $product_id, 'wcbogo_free_product', true );
		$options      = ( ! empty( $free_porduct ) ) ? array( $free_porduct => get_the_title( $free_porduct ) ) : array();
		ob_start();
		?>
		<div id="<?php echo esc_attr( wcbogo_free_product_key() ); ?>" class="panel woocommerce_options_panel">
			<div class="options_group">
				<?php
				// Quantity to buy.
				woocommerce_wp_text_input(
					array(
						'id'                => ( is_int( $loop ) ) ? "wcbogo-quantity-to-buy{$loop}" : 'wcbogo-quantity-to-buy',
						'name'              => ( is_int( $loop ) ) ? "wcbogo-quantity-to-buy[{$loop}]" : 'wcbogo-quantity-to-buy',
						'label'             => __( 'Quantity to buy', 'wcbogo' ),
						'placeholder'       => 0,
						'description'       => __( 'Quantity to buy to qualify for free product.', 'wcbogo' ),
						'desc_tip'          => true,
						'type'              => 'number',
						'value'             => get_post_meta( $product_id, 'wcbogo_quantity_to_buy', true ),
						'custom_attributes' => array(
							'min'  => 1,
							'step' => 1,
						),
					)
				);

				// Quantity of free product given.
				woocommerce_wp_text_input(
					array(
						'id'                => ( is_int( $loop ) ) ? "wcbogo-quantity-of-free-product-given{$loop}" : 'wcbogo-quantity-of-free-product-given',
						'name'              => ( is_int( $loop ) ) ? "wcbogo-quantity-of-free-product-given[{$loop}]" : 'wcbogo-quantity-of-free-product-given',
						'label'             => __( 'Quantity of free product given', 'wcbogo' ),
						'placeholder'       => 0,
						'description'       => __( 'How much quantity of the product will be given free.', 'wcbogo' ),
						'desc_tip'          => true,
						'type'              => 'number',
						'value'             => get_post_meta( $product_id, 'wcbogo_quantity_of_free_product_given', true ),
						'custom_attributes' => array(
							'min'  => 1,
							'step' => 1,
						),
					)
				);

				// Free product.
				woocommerce_wp_select(
					array(
						'id'                => ( is_int( $loop ) ) ? "wcbogo-free-product{$loop}" : 'wcbogo-free-product',
						'name'              => ( is_int( $loop ) ) ? "wcbogo-free-product[{$loop}]" : 'wcbogo-free-product',
						'wrapper_class'     => 'wcbogo-free-product',
						'label'             => __( 'Free product', 'wcbogo' ),
						'description'       => __( 'Product ID of the product that will be offered as free product, if left empty same product will be offered for free.', 'wcbogo' ),
						'desc_tip'          => true,
						'options'           => $options,
						'value'             => get_post_meta( $product_id, 'wcbogo_free_product', true ),
						'class'             => 'enhanced',
						'custom_attributes' => array(
							'data-productid' => $product_id,
						),
					)
				);

				// Message for free product.
				woocommerce_wp_textarea_input(
					array(
						'id'          => ( is_int( $loop ) ) ? "wcbogo-message-for-free-product{$loop}" : 'wcbogo-message-for-free-product',
						'name'        => ( is_int( $loop ) ) ? "wcbogo-message-for-free-product[{$loop}]" : 'wcbogo-message-for-free-product',
						'class'       => 'wcbogo-message-for-free-product short',
						'label'       => __( 'Message for free product', 'wcbogo' ),
						'description' => __( 'Message shown on the product page, use this short codes, [buy_quantity] => quantity of product you have to buy, [free_quantity]=> quantity that you will get free, [free_name] => Free product title.', 'wcbogo' ),
						'desc_tip'    => true,
						'value'       => get_post_meta( $product_id, 'wcbogo_message_for_free_product', true ),
						'placeholder' => __( 'Purchase this product for the offer..', 'wcbogo' ),
					)
				);

				// Offer start.
				woocommerce_wp_text_input(
					array(
						'id'          => ( is_int( $loop ) ) ? "wcbogo-offer-start{$loop}" : 'wcbogo-offer-start',
						'name'        => ( is_int( $loop ) ) ? "wcbogo-offer-start[{$loop}]" : 'wcbogo-offer-start',
						'label'       => __( 'Offer starts on', 'wcbogo' ),
						'placeholder' => 'YYYY-MM-DD',
						'description' => __( 'Date and time when the offer will become active, leave blank if you want it to start right away.', 'wcbogo' ),
						'desc_tip'    => true,
						'type'        => 'text',
						'value'       => get_post_meta( $product_id, 'wcbogo_offer_start', true ),
						'class'       => 'wcbogo-input-date',
					)
				);

				// Offer end.
				woocommerce_wp_text_input(
					array(
						'id'          => ( is_int( $loop ) ) ? "wcbogo-offer-end{$loop}" : 'wcbogo-offer-end',
						'name'        => ( is_int( $loop ) ) ? "wcbogo-offer-end[{$loop}]" : 'wcbogo-offer-end',
						'label'       => __( 'Offer ends on', 'wcbogo' ),
						'placeholder' => 'YYYY-MM-DD',
						'description' => __( 'Date and time when the offer will become inactive, leave blank if you want it to start right away.', 'wcbogo' ),
						'desc_tip'    => true,
						'type'        => 'text',
						'value'       => get_post_meta( $product_id, 'wcbogo_offer_end', true ),
						'class'       => 'wcbogo-input-date',
					)
				);
				?>
			</div>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * WCBOGO shortcode template.
	 *
	 * @param array $args Holds the shortcode arguments array.
	 * @return string
	 */
	public function wcbogo_offers_shortcode_callback( $args = array() ) {

		return $this->wcbogo_offers_shortcode_markup( $args );
	}

	/**
	 * Create WCBOGO shortcode template.
	 *
	 * @param array $args Holds the shortcode arguments array.
	 * @return string
	 */
	private function wcbogo_offers_shortcode_markup( $args ) {
		$posts_per_page = get_option( 'posts_per_page' );
		$product_ids    = wcbogo_get_final_products_list( $posts_per_page );
		ob_start();
		return ob_get_clean();
	}
}

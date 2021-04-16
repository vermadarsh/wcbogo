<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://gurukullab.com/
 * @since      1.0.0
 *
 * @package    Wc_Buy1_Get1_Free
 * @subpackage Wc_Buy1_Get1_Free/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Wc_Buy1_Get1_Free
 * @subpackage Wc_Buy1_Get1_Free/public
 * @author     Gurukul Lab <info@gurukullab.com>
 */
class Wc_Buy1_Get1_Free_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 * @param string $plugin_name The name of the plugin.
	 * @param string $version The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function wcbogo_wp_enqueue_scripts_callback() {
		wp_enqueue_style(
			$this->plugin_name,
			WCBOGO_PLUGIN_URL . 'public/css/wc-buy1-get1-free-public.css',
			array(),
			filemtime( WCBOGO_PLUGIN_PATH . 'public/css/wc-buy1-get1-free-public.css' )
		);

		wp_enqueue_script(
			$this->plugin_name,
			WCBOGO_PLUGIN_URL . 'public/js/wc-buy1-get1-free-public.js',
			array( 'jquery' ),
			filemtime( WCBOGO_PLUGIN_PATH . 'public/js/wc-buy1-get1-free-public.js' ),
			true
		);

		wp_localize_script(
			$this->plugin_name,
			'WCBOGO_Public_JS_Obj',
			array(
				'ajaxurl'            => admin_url( 'admin-ajax.php' ),
				'processing_btn_txt' => __( 'Processing...', 'wcbogo' ),
				'is_cart'            => ( is_cart() ) ? 'yes' : 'no',
			)
		);
	}

	/**
	 * Show the offer message on product details page.
	 *
	 * @return void
	 */
	public function wcbogo_woocommerce_before_add_to_cart_form_callback() {
		$product_id = get_the_ID();

		// Return, if the offer is not available on the current date.
		if ( ! wcbogo_is_offer_available( $product_id ) ) {
			return;
		}

		$offer_scope = wcbogo_get_offer_scope();
		$show_offer  = true;

		if ( is_user_logged_in() && 'non-loggedin' === $offer_scope ) {
			$show_offer = false;
		}

		if ( ! is_user_logged_in() && 'loggedin' === $offer_scope ) {
			$show_offer = false;
		}

		if ( apply_filters( 'wcbogo_show_offer_simple_product_details_page', $show_offer, $product_id ) ) {
			echo wp_kses_post( wcbogo_get_product_offer_message_html( $product_id ) );
		}
	}

	/**
	 * Return the variation description adding the offer html.
	 *
	 * @param array  $data Holds the variation data array.
	 * @param object $variable_product Holds the variable product object.
	 * @param object $variation Holds the variation object.
	 * @return array
	 */
	public function wcbogo_woocommerce_available_variation_callback( $data, $variable_product, $variation ) {
		$variation_id = $variation->get_id();

		// Return, if the offer is not available on the current date.
		if ( ! wcbogo_is_offer_available( $variation_id ) ) {
			return $data;
		}

		$offer_scope = wcbogo_get_offer_scope();
		$show_offer  = true;

		if ( is_user_logged_in() && 'non-loggedin' === $offer_scope ) {
			$show_offer = false;
		}

		if ( ! is_user_logged_in() && 'loggedin' === $offer_scope ) {
			$show_offer = false;
		}

		if ( apply_filters( 'wcbogo_show_offer_variable_product_details_page', $show_offer, $variation_id ) ) {
			$offer_html                     = wcbogo_get_product_offer_message_html( $variation_id );
			$data['variation_description'] .= $offer_html;
		}

		return $data;
	}

	/**
	 * Show offer as a woocommerce message.
	 */
	public function wcbogo_show_free_product_availability_callback() {
		if ( ! function_exists( 'WC' ) ) {
			return;
		}

		// Get cart.
		$cart = WC()->cart->get_cart();

		if ( empty( $cart ) || ! is_array( $cart ) ) {
			return;
		}

		// Loop in the cart items to check for offer available for each one.
		foreach ( $cart as $cart_key => $cart_item ) {
			// Check offer's scope.
			$offer_scope = wcbogo_get_offer_scope();
			$show_offer  = true;

			if ( is_user_logged_in() && 'non-loggedin' === $offer_scope ) {
				$show_offer = false;
			}

			if ( ! is_user_logged_in() && 'loggedin' === $offer_scope ) {
				$show_offer = false;
			}

			if ( false === $show_offer ) {
				continue;
			}

			$prod_id            = wcbogo_product_id( $cart_item['product_id'], $cart_item['variation_id'] );
			$is_offer_available = wcbogo_is_offer_available( $prod_id );

			if ( ! $is_offer_available ) {
				continue; // Skip the product if offer is not available for that product.
			}

			// Check to see, if the added product is a free product.
			if ( ! empty( $cart_item['is_free_product'] ) && 'yes' === $cart_item['is_free_product'] ) {
				continue;
			}

			// Fetch offer details.
			$offer         = wcbogo_get_offer( $prod_id );
			$cart_quantity = $cart_item['quantity'];

			if ( strval( $cart_quantity ) < strval( $offer['quantity_to_buy'] ) ) {
				continue;
			}

			// Calculate the free product quantity to be made available.
			$free_product_quantity = wcbogo_get_free_product_quantity( $cart_quantity, $offer['quantity_to_buy'], $offer['quantity_to_give_free'] );

			// Check to see if free product is already added to cart.
			$free_product_cart_key = wcbogo_is_free_product_in_cart( $offer['free_product'] );

			/**
			 * If the free product is not in the cart, means the full quantity of the free product is available to be added to the cart.
			 * In other case, we'll calculate the left over quantity which should be shown to the customer.
			 *
			 * Assign the total free product quantity as the remaining.
			 */
			$remaining_free_product_quantity = $free_product_quantity;
			if ( false !== $free_product_cart_key ) {
				$remaining_free_product_quantity = wcbogo_remaining_free_product_quantity( $free_product_cart_key, $offer['free_product'], $free_product_quantity );

				/**
				 * If remaining free product quantity is 0, that means, offer shouldn't be shown.
				 * Else, show the offer for the remaining quantity.
				 */
				if ( 0 === $remaining_free_product_quantity ) {
					$show_offer = false;
				}
			}

			/**
			 * Show the offer if it is available.
			 */
			if ( apply_filters( 'wcbogo_show_free_product_add_to_cart_offer', $show_offer ) ) {
				$this->show_offer_data( $prod_id, $offer['free_product'], $remaining_free_product_quantity );
			}
		}
	}

	/**
	 * Echo the offer HTML.
	 *
	 * @param int $prod_id Holds the product ID.
	 * @param int $free_product_id Holds the product ID.
	 * @param int $free_quantity Holds the product quantity.
	 */
	private function show_offer_data( $prod_id = 0, $free_product_id = 0, $free_quantity = 0 ) {
		$prod_id         = (int) $prod_id;
		$free_product_id = (int) $free_product_id;
		$free_quantity   = (int) $free_quantity;

		if ( 0 === $free_product_id ) {
			return;
		}

		if ( 0 === $free_quantity ) {
			return;
		}

		$free_product_title = get_the_title( $free_product_id );
		ob_start();
		?>
		<div class="woocommerce-message" role="alert">
			<a data-offerprod="<?php echo esc_attr( $prod_id ); ?>" data-product="<?php echo esc_attr( $free_product_id ); ?>" data-quantity="<?php echo esc_attr( $free_quantity ); ?>" href="#" class="button wc-forward wcbogo-add-free-product-to-cart"><?php esc_html_e( 'Add to cart', 'wcbogo' ); ?></a>
			<?php /* translators: 1: %d: free product quantity, 2: %s: free product title */ ?>
			&nbsp;<?php echo sprintf( esc_html__( '%1$d × “%2$s” is available to be added to cart for free.', 'wcbogo' ), esc_html( $free_quantity ), esc_html( $free_product_title ) ); ?>
		</div>
		<?php
		echo wp_kses_post(
			apply_filters( 'wcbogo_offer_html', ob_get_clean(), $free_product_id, $free_quantity )
		);
	}

	/**
	 * AJAX to add free product to cart.
	 */
	public function wcbogo_add_free_product_to_cart_callback() {
		$action = filter_input( INPUT_POST, 'action', FILTER_SANITIZE_STRING );

		if ( ! empty( $action ) && 'add_free_product_to_cart' === $action ) {
			$offer_product_id = (int) filter_input( INPUT_POST, 'offer_product_id', FILTER_SANITIZE_NUMBER_INT );
			$product_id       = (int) filter_input( INPUT_POST, 'product_id', FILTER_SANITIZE_NUMBER_INT );
			$quantity         = (int) filter_input( INPUT_POST, 'quantity', FILTER_SANITIZE_NUMBER_INT );

			/**
			 * Set the product ID in the session.
			 * This is because this ID is required to update custom data to the cart item.
			 */
			WC()->session->set( 'wcbogo_product_id', $product_id );

			/**
			 * Set the offer product ID in the session.
			 * This is because this ID is required to show the offer message on cart page.
			 */
			WC()->session->set( 'wcbogo_offer_product_id', $offer_product_id );

			/**
			 * Before adding free product to cart.
			 *
			 * This hook fires right before adding free product to the cart.
			 *
			 * @param int $product_id Holds the product ID.
			 * @param int $quantity Holds the product quantity.
			 */
			do_action( 'wcbogo_before_adding_free_product_to_cart', $product_id, $quantity );

			// Add the free product to cart now.
			WC()->cart->add_to_cart( $product_id, $quantity );

			/**
			 * After adding free product to cart.
			 *
			 * This hook fires right after adding free product to the cart.
			 *
			 * @param int $product_id Holds the product ID.
			 * @param int $quantity Holds the product quantity.
			 */
			do_action( 'wcbogo_after_adding_free_product_to_cart', $product_id, $quantity );

			wp_send_json_success(
				array(
					'code'               => 'wcbogo-free-product-added-to-cart',
					'cart_url'           => WC()->cart->get_cart_url(),
					'view_cart_btn_text' => apply_filters( 'wcbogo_view_cart_button_text', __( 'View cart', 'wcbogo' ), $product_id ),
					'free_product_title' => get_the_title( $product_id ),
					'success_message'    => __( 'has been added to the cart.', 'wcbogo' ),
				)
			);
			wp_die();
		}
	}

	/**
	 * AJAX to fetch mini cart markup.
	 */
	public function wcbogo_update_mini_cart_callback() {
		$action = filter_input( INPUT_POST, 'action', FILTER_SANITIZE_STRING );

		if ( ! empty( $action ) && 'update_mini_cart' === $action ) {
			echo wp_kses_post( woocommerce_mini_cart() );
			wp_die();
		}
	}

	/**
	 * Add custom data to the cart item when free product is added to the cart.
	 *
	 * @param array $cart_item_data Holds the cart item data.
	 * @param int   $product_id Holds the product ID.
	 * @param int   $variation_id Holds the variation ID.
	 * @return array
	 */
	public function wcbogo_woocommerce_add_cart_item_data_callback( $cart_item_data, $product_id, $variation_id ) {
		// Add a custom key-data to the free product.
		$session_product_id = WC()->session->get( 'wcbogo_product_id' );
		$prod_id            = wcbogo_product_id( $product_id, $variation_id );

		if ( strval( $session_product_id ) === strval( $prod_id ) ) {
			$cart_item_data['is_free_product']    = 'yes';
			$cart_item_data['offer_from_product'] = WC()->session->get( 'wcbogo_offer_product_id' );
		}
		WC()->session->__unset( 'wcbogo_product_id' );

		// Add a custom key-data to the offer product.
		$is_offer_available = wcbogo_is_offer_available( $prod_id );

		if ( true === $is_offer_available ) {
			$cart_item_data['wcbogo_product'] = 1;
		}

		return $cart_item_data;
	}

	/**
	 * Remove the linked free item from the cart if the offer product is removed.
	 *
	 * @param string $cart_item_key Holds the cart item hash.
	 * @param object $cart Holds the cart contents.
	 * @return void
	 */
	public function wcbogo_woocommerce_remove_cart_item_callback( $cart_item_key, $cart ) {

		if ( ! isset( $cart->cart_contents ) || empty( $cart->cart_contents ) ) {
			return;
		}

		$cart_item = ( ! empty( $cart->cart_contents[ $cart_item_key ] ) ) ? $cart->cart_contents[ $cart_item_key ] : false;
		$has_offer = ( false !== $cart_item && ! empty( $cart_item['wcbogo_product'] ) && 1 === $cart_item['wcbogo_product'] ) ? true : false;

		if ( ! $has_offer ) {
			return;
		}

		$prod_id               = wcbogo_product_id( $cart_item['product_id'], $cart_item['variation_id'] );
		$free_product          = (int) get_post_meta( $prod_id, 'wcbogo_free_product', true );
		$free_product_cart_key = wcbogo_get_free_item_cart_key( $free_product );

		if ( false !== $free_product_cart_key ) {
			WC()->cart->remove_cart_item( $free_product_cart_key );
		}
	}

	/**
	 * Disable the usage of coupons if there is a free item in the cart.
	 *
	 * @param boolean $coupons_enabled If the coupons are enabled.
	 * @return boolean
	 */
	public function wcbogo_woocommerce_coupons_enabled_callback( $coupons_enabled ) {
		$disable_coupons_settings = get_option( 'wcbogo_disable_coupons' );

		/**
		 * Check if the coupon disbale settings aren't saved.
		 * Disable the coupons, as the default feature.
		 */
		if ( is_bool( $disable_coupons_settings ) && false !== $disable_coupons_settings ) {
			return $coupons_enabled;
		}

		if ( 'no' === $disable_coupons_settings ) {
			return $coupons_enabled;
		}

		return ( true === wcbogo_is_free_item_in_cart() ) ? false : true;
	}

	/**
	 * Add extra class to the cart item row for the free product.
	 *
	 * @param string $item_class Holds the cart item row class.
	 * @param string $cart_item Holds the cart item.
	 * @return string
	 */
	public function wcbogo_woocommerce_cart_item_class_callback( $item_class, $cart_item ) {

		if ( ! $cart_item ) {
			return $item_class;
		}

		// Check to see if the product is free or not.
		$is_free_product = ( false !== $cart_item && ! empty( $cart_item['is_free_product'] ) && 'yes' === $cart_item['is_free_product'] ) ? true : false;

		if ( $is_free_product ) {
			// Remove the link to remove cart item for free product.
			$item_class .= ' is-free-product';
		}

		return $item_class;
	}

	/**
	 * Remove the cart item removal link for all the free products in cart.
	 *
	 * @param string $link Holds the cart item removal link html.
	 * @param string $cart_item_key Holds the cart item key.
	 * @return string
	 */
	public function wcbogo_woocommerce_cart_item_remove_link_callback( $link, $cart_item_key ) {

		// Get cart.
		$cart = WC()->cart->get_cart();

		if ( empty( $cart ) || ! is_array( $cart ) ) {
			return $link;
		}

		$cart_item = ( ! empty( $cart[ $cart_item_key ] ) ) ? $cart[ $cart_item_key ] : false;

		if ( ! $cart_item ) {
			return $link;
		}

		// Check to see if the product is free or not.
		$is_free_product = ( false !== $cart_item && ! empty( $cart_item['is_free_product'] ) && 'yes' === $cart_item['is_free_product'] ) ? true : false;

		if ( $is_free_product ) {
			// Remove the link to remove cart item for free product.
			$link = '';
		}

		return $link;
	}

	/**
	 * Remove the cart item quantity input for all the free products in cart.
	 *
	 * @param string $quantity_html Holds the cart item quantity input html.
	 * @param string $cart_item_key Holds the cart item key.
	 * @param string $cart_item Holds the cart item.
	 * @return string
	 */
	public function wcbogo_woocommerce_cart_item_quantity_callback( $quantity_html, $cart_item_key, $cart_item ) {

		if ( ! $cart_item ) {
			return $quantity_html;
		}

		// Check to see if the product is free or not.
		$is_free_product = ( false !== $cart_item && ! empty( $cart_item['is_free_product'] ) && 'yes' === $cart_item['is_free_product'] ) ? true : false;

		if ( $is_free_product ) {
			// Remove the link to remove cart item for free product.
			$quantity_html = $cart_item['quantity'];
		}

		return $quantity_html;
	}

	/**
	 * Calculate the cart item subtotal for free product.
	 *
	 * @param array $cart_obj Holds the cart contents.
	 * @return void
	 */
	public function wcbogo_woocommerce_before_calculate_totals_callback( $cart_obj ) {

		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		// Loop in the cart items.
		foreach ( $cart_obj->get_cart() as $value ) {
			if (
				isset( $value['is_free_product'] ) &&
				! empty( $value['is_free_product'] ) &&
				'yes' === $value['is_free_product']
			) {
				$free_product_price = apply_filters( 'wcbogo_free_product_cart_price', 0.00, $value );
				$value['data']->set_price( $free_product_price );
			}
		}
	}

	/**
	 * Add custom data to the cart item data.
	 *
	 * @param array $item_data Holds the item data.
	 * @param array $cart_item_data Holds the cart item data.
	 * @return array
	 */
	public function wcbogo_woocommerce_get_item_data_callback( $item_data, $cart_item_data ) {

		if ( ! isset( $cart_item_data['is_free_product'] ) ) {
			return $item_data;
		}

		if ( empty( $cart_item_data['is_free_product'] ) ) {
			return $item_data;
		}

		$offer_from_product       = $cart_item_data['offer_from_product'];
		$offer_from_product_title = get_the_title( $offer_from_product );

		$item_data[] = array(
			'key'   => __( 'Free Product', 'wcbogo' ),
			/* translators: 1: start anchor tag, 2: end anchor tag, 3: offer product title */
			'value' => sprintf( __( 'This product has been offered free with purchase of %1$s%3$s%2$s.', 'wcbogo' ), '<a href="' . get_permalink( $offer_from_product ) . '" title="' . $offer_from_product_title . '">', '</a>', $offer_from_product_title ),
		);

		return apply_filters( 'wcbogo_free_item_data', $item_data, $cart_item_data );
	}
}

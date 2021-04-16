<?php
/**
 * This file is used for writing all the re-usable custom functions.
 *
 * @since 1.0.0
 * @package Wc_Buy1_Get1_Free
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Get the tab key for adding free product panel.
 *
 * @return string
 */
function wcbogo_free_product_key() {

	return 'wcbogo-free-product';
}

/**
 * Fetch all available products list.
 *
 * @param int $posts_per_page Holds the posts per page count.
 */
function wcbogo_get_final_products_list( $posts_per_page ) {
	$product_variations = array();
	$prods_to_exclude   = array();
	$product_ids        = get_posts(
		array(
			'post_type'      => 'product',
			'posts_per_page' => $posts_per_page,
			'post_status'    => 'publish',
			'fields'         => 'ids',
		)
	);

	if ( ! empty( $product_ids ) ) {
		foreach ( $product_ids as $pid ) {
			// Check for the product type.
			$post_terms  = wp_get_post_terms( $pid, 'product_type' );
			$is_external = ( ! empty( $post_terms[0]->slug ) && 'external' === $post_terms[0]->slug ) ? true : false;
			$is_grouped  = ( ! empty( $post_terms[0]->slug ) && 'grouped' === $post_terms[0]->slug ) ? true : false;

			if ( $is_external || $is_grouped ) {
				// Exclude the external and grouped products.
				$prods_to_exclude[] = $pid;
			}

			// Check for the variable products.
			$if_parent = get_posts(
				array(
					'post_type'       => 'product_variation',
					'posts_per_page'  => -1,
					'post_status'     => 'publish',
					'fields'          => 'ids',
					'post_parent__in' => array( $pid ),
				)
			);

			if ( ! empty( $if_parent ) ) {
				$prods_to_exclude[] = $pid;

				foreach ( $if_parent as $child_id ) {
					$product_variations[] = $child_id;
				}
			}
		}
	}

	return array_values(
		array_diff(
			array_merge(
				$product_ids,
				$product_variations
			),
			$prods_to_exclude
		)
	);

}

/**
 * Check if the offer is available or not.
 *
 * @param int $product_id Holds the product ID.
 */
function wcbogo_is_offer_available( $product_id ) {

	if ( empty( $product_id ) ) {
		return false;
	}

	// If free product is not set, means offer isn't available.
	if ( empty( get_post_meta( $product_id, 'wcbogo_free_product', true ) ) ) {
		return false;
	}

	$start_date = get_post_meta( $product_id, 'wcbogo_offer_start', true );
	$end_date   = get_post_meta( $product_id, 'wcbogo_offer_end', true );

	$current_date = gmdate( 'Y-m-d' );
	$current_date = gmdate( 'Y-m-d', strtotime( $current_date ) );

	// If both the dates are blank, the offer is available.
	if ( empty( $start_date ) && empty( $end_date ) ) {
		return true;
	}

	// If the end date is blank, and current date falls after/on the start date, the offer is available.
	if ( empty( $end_date ) ) {
		$start_date = gmdate( 'Y-m-d', strtotime( $start_date ) );

		if ( $current_date >= $start_date ) {
			return true;
		}
	}

	// If the start date is blank, and the current date falls before/on the end date, the offer is availanle.
	if ( empty( $start_date ) ) {
		$end_date = gmdate( 'Y-m-d', strtotime( $end_date ) );

		if ( $current_date <= $end_date ) {
			return true;
		}
	}

	// If both the dates are available, and current date falls between the two, the offer is available.
	if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
		$start_date = gmdate( 'Y-m-d', strtotime( $start_date ) );
		$end_date   = gmdate( 'Y-m-d', strtotime( $end_date ) );

		if ( $current_date >= $start_date && $current_date <= $end_date ) {
			return true;
		}
	}

	return false;

}

/**
 * Function to decide, which of the product IDs to be considered.
 *
 * @param int $product_id Holds the product ID.
 * @param int $variation_id Holds the variation ID.
 * @return int
 */
function wcbogo_product_id( $product_id, $variation_id ) {

	return ( 0 !== $variation_id ) ? $variation_id : $product_id;
}

/**
 * Return offer data from product ID.
 *
 * @param int $product_id Holds the product ID.
 * @return array
 */
function wcbogo_get_offer( $product_id ) {
	$product_meta = get_post_meta( $product_id );

	return array(
		'quantity_to_buy'       => ( ! empty( $product_meta['wcbogo_quantity_to_buy'][0] ) ) ? $product_meta['wcbogo_quantity_to_buy'][0] : '',
		'quantity_to_give_free' => ( ! empty( $product_meta['wcbogo_quantity_of_free_product_given'][0] ) ) ? $product_meta['wcbogo_quantity_of_free_product_given'][0] : '',
		'free_product'          => ( ! empty( $product_meta['wcbogo_free_product'][0] ) ) ? $product_meta['wcbogo_free_product'][0] : '',
		'free_product_title'    => ( ! empty( $product_meta['wcbogo_free_product'][0] ) ) ? get_the_title( $product_meta['wcbogo_free_product'][0] ) : '',
		'free_product_link'     => ( ! empty( $product_meta['wcbogo_free_product'][0] ) ) ? get_permalink( $product_meta['wcbogo_free_product'][0] ) : '',
		'free_product_message'  => ( ! empty( $product_meta['wcbogo_message_for_free_product'][0] ) ) ? $product_meta['wcbogo_message_for_free_product'][0] : '',
		'offer_start'           => ( ! empty( $product_meta['wcbogo_offer_start'][0] ) ) ? $product_meta['wcbogo_offer_start'][0] : '',
		'offer_end'             => ( ! empty( $product_meta['wcbogo_offer_end'][0] ) ) ? $product_meta['wcbogo_offer_end'][0] : '',
	);
}

/**
 * Check to see if the free product is in cart already.
 *
 * @param int $free_product Holds the free product ID.
 * @return boolean
 */
function wcbogo_is_free_product_in_cart( $free_product ) {

	if ( empty( $free_product ) ) {
		return false;
	}

	// Get cart.
	$cart = WC()->cart->get_cart();

	if ( empty( $cart ) || ! is_array( $cart ) ) {
		return false;
	}

	// Loop in the cart items to check for offer available for each one.
	foreach ( $cart as $cart_key => $cart_item ) {
		$prod_id = wcbogo_product_id( $cart_item['product_id'], $cart_item['variation_id'] );

		// Check to see, if the added product is a free product.
		if ( ! array_key_exists( 'is_free_product', $cart_item ) ) {
			continue;
		}

		if ( strval( $prod_id ) === strval( $free_product ) ) {
			return $cart_key;
		}
	}

	return false;
}

/**
 * Return the free product quantity.
 *
 * @param int $offer_prod_cart_quantity Holds the quantity of the offer product in the cart.
 * @param int $quantity_to_buy Holds the quantity to be purchased.
 * @param int $quantity_to_give_free Holds the quantity to be given free.
 * @return int
 */
function wcbogo_get_free_product_quantity( $offer_prod_cart_quantity, $quantity_to_buy, $quantity_to_give_free ) {
	$quantity_to_buy       = (int) $quantity_to_buy;
	$quantity_to_give_free = (int) $quantity_to_give_free;

	if ( 0 === $quantity_to_buy ) {
		return false;
	}

	if ( 0 === $quantity_to_give_free ) {
		return false;
	}

	// Calculate the free product quantity now.
	$free_quantity_to_be_made_available  = (int) ( $offer_prod_cart_quantity / $quantity_to_buy );
	$free_quantity_to_be_made_available *= $quantity_to_give_free;

	return $free_quantity_to_be_made_available;
}

/**
 * Return the remaining free product quantity.
 *
 * @param int $free_product_cart_key Holds the free product cart item key.
 * @param int $free_product Holds the free product ID.
 * @param int $free_product_quantity Holds the calculated free product quantity.
 * @return int
 */
function wcbogo_remaining_free_product_quantity( $free_product_cart_key, $free_product, $free_product_quantity ) {

	if ( ! $free_product_cart_key ) {
		return false;
	}

	if ( empty( $free_product ) ) {
		return false;
	}

	if ( empty( $free_product_quantity ) ) {
		return false;
	}

	// Get cart.
	$cart = WC()->cart->get_cart();

	if ( empty( $cart ) || ! is_array( $cart ) ) {
		return false;
	}

	$free_product_cart_item = ( ! empty( $cart[ $free_product_cart_key ] ) ) ? $cart[ $free_product_cart_key ] : false;

	if ( ! $free_product_cart_item ) {
		return false;
	}

	$free_product_cart_item_quantity = $free_product_cart_item['quantity'];

	// Subtract the current quantity from the total fre product quantity.
	return ( $free_product_quantity - $free_product_cart_item_quantity );
}

/**
 * Return the cart key of the free product.
 *
 * @param int $free_product Holds the free product ID.
 * @return boolean|string
 */
function wcbogo_get_free_item_cart_key( $free_product ) {

	if ( empty( $free_product ) ) {
		return false;
	}

	// Get cart.
	$cart = WC()->cart->get_cart();

	if ( empty( $cart ) || ! is_array( $cart ) ) {
		return false;
	}

	// Loop in the cart items to know the free item.
	foreach ( $cart as $cart_item_key => $cart_item_data ) {
		$prod_id = wcbogo_product_id( $cart_item_data['product_id'], $cart_item_data['variation_id'] );

		if ( $prod_id === $free_product ) {
			$is_free_product = ( isset( $cart_item_data['is_free_product'] ) && ! empty( $cart_item_data['is_free_product'] ) && 'yes' === $cart_item_data['is_free_product'] ) ? true : false;

			if ( $is_free_product ) {
				return $cart_item_key;
			}
		}
	}

	return false;
}

/**
 * Return the offer HTML by product ID to be shown on the product details page.
 *
 * @param int $product_id Holds the product ID.
 * @return string
 */
function wcbogo_get_product_offer_message_html( $product_id ) {
	$offer_message = get_post_meta( $product_id, 'wcbogo_message_for_free_product', true );

	ob_start();
	if ( ! empty( $offer_message ) ) {
		$quantity_to_buy       = get_post_meta( $product_id, 'wcbogo_quantity_to_buy', true );
		$free_quantity_to_give = get_post_meta( $product_id, 'wcbogo_quantity_of_free_product_given', true );
		$free_product          = get_post_meta( $product_id, 'wcbogo_free_product', true );

		$offer_message = str_replace( '[buy_quantity]', $quantity_to_buy, $offer_message );
		$offer_message = str_replace( '[free_quantity]', $free_quantity_to_give, $offer_message );

		if ( ! empty( $free_product ) ) {
			$free_product_title = get_the_title( $free_product );
			$free_product_title = '<a href="' . get_permalink( $free_product ) . '" title="' . $free_product_title . '">' . $free_product_title . '</a>';
			$offer_message      = str_replace( '[free_name]', $free_product_title, $offer_message );
		}
		?>
		<div class="woocommerce-message wcbogo-offer-message" role="alert">
			<h3>
				<?php
				echo wp_kses_post(
					apply_filters( 'wcbogo_offer_message_heading', esc_html__( 'Offer', 'wcbogo' ) )
				);
				?>
			</h3>
			<p><?php echo wp_kses_post( nl2br( $offer_message ) ); ?></p>
		</div>
		<?php
	}

	return apply_filters( 'wcbogo_product_offer_message_html', ob_get_clean() );
}

/**
 * Get offer scope.
 *
 * @return string
 */
function wcbogo_get_offer_scope() {
	$offer_scope = get_option( 'wcbogo_offer_scope' );
	/**
	 * Offer scope.
	 *
	 * Filter to change the offer scope.
	 *
	 * @param string $offer_scope Holds the offer scope.
	 */
	$offer_scope = apply_filters( 'wcbogo_offer_scope', $offer_scope );

	return $offer_scope;
}

/**
 * Check to see if there is any free product in cart.
 *
 * @return boolean
 */
function wcbogo_is_free_item_in_cart() {

	// Get cart.
	$cart = WC()->cart->get_cart();

	if ( empty( $cart ) || ! is_array( $cart ) ) {
		return false;
	}

	// Loop in the cart items to check for offer available for each one.
	foreach ( $cart as $cart_key => $cart_item ) {

		if ( array_key_exists( 'is_free_product', $cart_item ) ) {
			return true;
		}
	}

	return false;
}

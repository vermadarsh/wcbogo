jQuery( document ).ready( function( $ ) {
	'use strict';

	/* eslint-disable */
	var {ajaxurl, processing_btn_txt, is_cart} = WCBOGO_Public_JS_Obj;
	/* eslint-enable */

	/**
	 * Add free product to cart.
	 */
	$( document ).on( 'click', '.wcbogo-add-free-product-to-cart', function( e ) {
		e.preventDefault();

		var this_btn = $( this );
		var offer_product_id = this_btn.data( 'offerprod' );
		var product_id = this_btn.data( 'product' );
		var quantity = this_btn.data( 'quantity' );

		if ( '' === product_id || '' === quantity ) {
			return;
		}

		this_btn.text( processing_btn_txt );

		// Send AJAX to fetch products.
		var data = {
			action: 'add_free_product_to_cart',
			offer_product_id: offer_product_id,
			product_id: product_id,
			quantity: quantity,
		};
		$.ajax( {
			dataType: 'JSON',
			url: ajaxurl,
			type: 'POST',
			data: data,
			success: function ( response ) {
				if ( 'wcbogo-free-product-added-to-cart' === response.data.code ) {
					// Update the mini cart now.
					wcbogo_update_mini_cart();

					// Update the woocommerce notice.
					var html = '<a href="' + response.data.cart_url + '" class="button wc-forward">' + response.data.view_cart_btn_text + '</a>';
					html += '&nbsp;' + quantity + ' × “' + response.data.free_product_title + '” has been added to the cart.';

					this_btn.parent( '.woocommerce-message' ).html( html );

					// Update the cart page, if the user is on the cart page.
					if ( 'yes' === is_cart ) {
						var wc_cart_update_btn = $( 'table.woocommerce-cart-form__contents td.actions button[name="update_cart"]' );

						if ( wc_cart_update_btn.is( '[disabled]' ) || wc_cart_update_btn.is( '[disabled=disabled]' ) ) {
							wc_cart_update_btn.prop( 'disabled', false );
							wc_cart_update_btn.click();
						}
					}
				}
			},
		} );
	} );

	// Update the mini cart.
	function wcbogo_update_mini_cart() {
		var data = {
			action: 'update_mini_cart',
		};
		$.ajax( {
			url: ajaxurl,
			type: 'POST',
			data: data,
			success: function ( response ) {
				$( '.widget_shopping_cart_content' ).html( response );
			},
		} );
	}

} );

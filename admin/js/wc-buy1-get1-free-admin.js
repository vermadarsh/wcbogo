jQuery( document ).ready( function( $ ) {
	'use strict';

	/* eslint-disable */
	var {ajaxurl, free_product_select_empty_option} = WCBOGO_Admin_JS_Obj;
	/* eslint-enable */

	// Set the dates input to show calendars.
	var date_time_interval = 1000;
	setInterval( function() {
		if ( $( '.wcbogo-input-date' ).length ) {
			$( '.wcbogo-input-date' ).datepicker( {
				dateFormat: 'yy-mm-dd'
			} );
		}
	}, date_time_interval );

	// Free product select box ajax.
	$( document ).on( 'click', '.wcbogo-free-product select', function() {
		var this_select = $( this );
		var min_select_options_count = 2;

		/**
		 * Options count is checked against 2 because the selected option is loaded on page load.
		 * Hence, if the product is selected, the options count would be 1.
		 */
		if ( min_select_options_count < $( '.wcbogo-free-product select > option' ).length ) {
			return false;
		}

		// Send the AJAX only when the options are not prosent.
		$( '.wcbogo-free-product' ).addClass( 'non-clickable' );

		// Send AJAX to fetch products.
		var data = {
			action: 'wcbogo_fetch_products',
			product_id: this_select.data( 'productid' ),
		};

		$.ajax( {
			dataType: 'JSON',
			url: ajaxurl,
			type: 'POST',
			data: data,
			success: function ( response ) {
				if ( 'wcbogo-products-fetched' === response.data.code ) {
					$( '.wcbogo-free-product' ).removeClass( 'non-clickable' );
					var {products, free_product} = response.data;
					var select_options = '<option value="">' + free_product_select_empty_option + '</option>';
					var min_products_count = 0;

					if ( min_products_count < products.length ) {
						/* eslint-disable */
						for ( var i in products ) {
							var selected = is_option_selected( products[i].id, free_product );
							select_options += '<option value="' + products[i].id + '" ' + selected + '>' + products[i].title + '</option>';
						}
						/* eslint-enable */
					}
					$( '.wcbogo-free-product select' ).html( select_options );
				}
			},
		} );

		return false;
	} );

	/**
	 * Return selected attribute.
	 *
	 * @param {number} product_id Holds the product ID.
	 * @param {number} free_product Holds the free product ID.
	 * @returns {number} Selected attribute.
	 */
	function is_option_selected( product_id, free_product ) {
		if ( product_id === free_product ) {
			return 'selected';
		}

		return '';
	}

} );

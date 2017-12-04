<?php

/**
* Jetpack_WooCommerce_Analytics_Universal
*
* @author greenafrican
*/

/**
* Bail if accessed directly
*/
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Jetpack_WooCommerce_Analytics_Universal {
	public function __construct() {
		add_action( 'woocommerce_after_add_to_cart_button', array( $this, 'add_to_cart' ) );
		add_action( 'wp_head', array( $this, 'wp_head' ), 999999 );
		add_action( 'wp_footer', array( $this, 'loop_add_to_cart' ) );
		add_action( 'woocommerce_after_cart', array( $this, 'remove_from_cart' ) );
		add_action( 'woocommerce_after_mini_cart', array( $this, 'remove_from_cart' ) );
		add_filter( 'woocommerce_cart_item_remove_link', array( $this, 'remove_from_cart_attributes' ), 10, 2 );
		add_action( 'woocommerce_after_single_product', array( $this, 'product_detail' ) );
		// add_action( 'woocommerce_after_checkout_form', array( $this, 'checkout_process' ) );
	}

	public function wp_head() {

		// If we're in the admin_area, return without inserting code.
		if ( is_admin() ) {
			return;
		}

		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		$async_code = "<script async src='https://stats.wp.com/s.js'></script>";

		echo "$async_code\r\n";
	}

	public function add_to_cart() {

		if ( ! is_single() ) {
			return;
		}

		$blogid = Jetpack::get_option( 'id' );
		global $product;

		$product_sku_or_id = Jetpack_WooCommerce_Analytics_Utils::get_product_sku_or_id( $product );
		$selector = ".single_add_to_cart_button";

		wc_enqueue_js(
			"window._wca = window._wca || [];
			jQuery( '" . esc_js( $selector ) . "' ).click( function() {
				_wca.track( {
					'_en': 'woocommerce_analytics_add_to_cart',
					'blog_id': " . esc_js( $blogid ) . ",
					'pi': '" . esc_js( $product_sku_or_id ) . "',
					'pn' : '" . esc_js( $product->get_title() ) . "',
					'pq': jQuery( 'input.qty' ).val() ? jQuery( 'input.qty' ).val() : '1',
				} );
			} );"
		);
	}

	public function loop_add_to_cart() {

		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		$minimum_woocommerce_active = class_exists( 'WooCommerce' ) && version_compare( WC_VERSION, '3.0', '>=' );
		if ( ! $minimum_woocommerce_active ) {
			return;
		}
		$blogid = Jetpack::get_option( 'id' );
		$selector = ".add_to_cart_button:not(.product_type_variable, .product_type_grouped)";

		wc_enqueue_js(
			"jQuery( '" . esc_js( $selector ) . "' ).click( function() {
				var productSku = jQuery( this ).data( 'product_sku' );
				var productID = jQuery( this ).data( 'product_id' );
				var productDetails = {
					'id': productSku ? productSku : productID,
					'quantity': jQuery( this ).data( 'quantity' ),
				};
				_wca.track( {
					'_en': 'woocommerce_analytics_product_view',
					'blog_id': '" . esc_js( $blogid ) . "',
					'pi': productDetails.id,
				} );
				_wca.track( {
					'_en': 'woocommerce_analytics_add_to_cart',
					'blog_id': " . esc_js( $blogid ) . ",
					'pi': productDetails.id,
					'pq': productDetails.quantity,
				} );
			} );"
		);
	}

	public function remove_from_cart() {

		// We listen at div.woocommerce because the cart 'form' contents get forcibly
		// updated and subsequent removals from cart would then not have this click
		// handler attached

		$blogid = Jetpack::get_option( 'id' );
		wc_enqueue_js(
			"jQuery( 'div.woocommerce' ).on( 'click', 'a.remove', function() {
				var productSku = jQuery( this ).data( 'product_sku' );
				var productID = jQuery( this ).data( 'product_id' );
				var quantity = jQuery( this ).parent().parent().find( '.qty' ).val()
				var productDetails = {
					'id': productSku ? productSku : productID,
					'quantity': quantity ? quantity : '1',
				};
				_wca.track( {
					'_en': 'woocommerce_analytics_remove_from_cart',
					'blog_id': '" . esc_js( $blogid ) . "',
					'pi': productDetails.id,
					'pq': productDetails.quantity,
				} );
			} );"
		);
	}

	/**
	* Adds the product ID and SKU to the remove product link (for use by remove_from_cart above) if not present
	*/
	public function remove_from_cart_attributes( $url, $key ) {
		if ( false !== strpos( $url, 'data-product_id' ) ) {
			return $url;
		}

		$item = WC()->cart->get_cart_item( $key );
		$product = $item[ 'data' ];

		$new_attributes = sprintf( 'href="%s" data-product_id="%s" data-product_sku="%s"',
			esc_attr( $url ),
			esc_attr( $product->get_id() ),
			esc_attr( $product->get_sku() )
			);
		$url = str_replace( 'href=', $new_attributes );
		return $url;
	}

	public function product_detail() {

		global $product;
		$blogid = Jetpack::get_option( 'id' );
		$product_sku_or_id = Jetpack_WooCommerce_Analytics_Utils::get_product_sku_or_id( $product );

		$item_details = array(
			'id' => $product_sku_or_id,
			'name' => $product->get_title(),
			'category' => Jetpack_WooCommerce_Analytics_Utils::get_product_categories_concatenated( $product ),
			'price' => $product->get_price()
		);
		wc_enqueue_js(
			"_wca.track( {
				'_en': 'woocommerce_analytics_product_view',
				'blog_id': '" . esc_js( $blogid ) . "',
				'pi': '" . esc_js( $item_details['id'] ) . "',
				'pn': '" . esc_js( $item_details['name'] ) . "',
				'pc': '" . esc_js( $item_details['category'] ) . "',
				'pp': '" . esc_js( $item_details['price'] ) . "'
			} );"
		);
	}

	// public function checkout_process() {
  //
	// 	$universal_commands = array();
	// 	$cart = WC()->cart->get_cart();
  //
	// 	foreach ( $cart as $cart_item_key => $cart_item ) {
	// 		/**
	// 		* This filter is already documented in woocommerce/templates/cart/cart.php
	// 		*/
	// 		$product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
	// 		$product_sku_or_id = Jetpack_WooCommerce_Analytics_Utils::get_product_sku_or_id( $product );
  //
	// 		$item_details = array(
	// 			'id' => $product_sku_or_id,
	// 			'name' => $product->get_title(),
	// 			'category' => Jetpack_WooCommerce_Analytics_Utils::get_product_categories_concatenated( $product ),
	// 			'price' => $product->get_price(),
	// 			'quantity' => $cart_item[ 'quantity' ]
	// 		);
  //
	// 		array_push( $universal_commands, "ga( 'ec:addProduct', " . wp_json_encode( $item_details ) . " );" );
	// 	}
  //
	// 	array_push( $universal_commands, "ga( 'ec:setAction','checkout' );" );
  //
	// 	wc_enqueue_js( implode( "\r\n", $universal_commands ) );
	// }

}

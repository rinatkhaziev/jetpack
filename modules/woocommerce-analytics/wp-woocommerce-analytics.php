<?php

/**
 * Jetpack_WooCommerce_Analytics is ported from the Jetpack_Google_Analytics code.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once( plugin_basename( 'classes/wp-woocommerce-analytics-utils.php' ) );
require_once( plugin_basename( 'classes/wp-woocommerce-analytics-universal.php' ) );

class Jetpack_WooCommerce_Analytics {

	/**
	 * @var Jetpack_WooCommerce_Analytics - Static property to hold our singleton instance
	 */
	static $instance = false;

	/**
	 * @var Static property to hold concrete analytics impl that does the work (universal or legacy)
	 */
	static $analytics = false;

	/**
	 * This is our constructor, which is private to force the use of get_instance()
	 *
	 * @return void
	 */
	private function __construct() {
		$analytics = new Jetpack_WooCommerce_Analytics_Universal();
	}

	/**
	 * Function to instantiate our class and make it a singleton
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}
}

global $jetpack_woocommerce_analytics;
$jetpack_woocommerce_analytics = Jetpack_WooCommerce_Analytics::get_instance();

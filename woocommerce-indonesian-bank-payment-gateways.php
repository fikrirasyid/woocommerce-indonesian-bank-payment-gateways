<?php
/*
    Plugin Name: WooCommerce Indonesian Bank Payment Gateways
    Version: 0.1
    Description: Adding Indonesian Banks to WooCommerce's payment gateway option
    Author: Fikri Rasyid
    Author URI: http://fikrirasyid.com
*/
/*
    Copyright 2014 Fikri Rasyid
    Developed by Fikri Rasyid (fikrirasyid@gmail.com)
*/

class WC_Gateway_Indonesian_Banks_Setup{

	function __construct(){
		add_action( 'plugins_loaded', 				array( $this, 'load' ) );
		add_filter( 'woocommerce_payment_gateways', array( $this, 'register') );
	}

	/**
	 * Requiring files
	 * 
	 * @return void
	 */
	function load(){
		require_once( dirname( __FILE__ ) . '/includes/class-wc-gateway-bank.php' );
		require_once( dirname( __FILE__ ) . '/includes/class-wc-gateway-bank-bca.php' );
		require_once( dirname( __FILE__ ) . '/includes/class-wc-gateway-bank-bni.php' );
		require_once( dirname( __FILE__ ) . '/includes/class-wc-gateway-bank-mandiri.php' );
	}

	/**
	 * Register payment gateways
	 * 
	 * @param array
	 * 
	 * @return array
	 */
	function register( $methods ){
		$methods[] = 'WC_Gateway_Bank_BCA';
		$methods[] = 'WC_Gateway_Bank_BNI';
		$methods[] = 'WC_Gateway_Bank_Mandiri';

		return $methods;
	}
}
new WC_Gateway_Indonesian_Banks_Setup;
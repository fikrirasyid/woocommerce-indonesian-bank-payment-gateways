<?php
/**
 * Bank Transfer Payment Gateway
 *
 * Provides a Bank BCA Transfer Payment Gateway.
 *
 * @class 		WC_Gateway_Bank_BCA
 * @extends		WC_Gateway_Bank
 * @version		2.1.0
 * @package		WooCommerce/Classes/Payment
 * @author 		Fikri Rasyid
 */
class WC_Gateway_Bank_BCA extends WC_Gateway_Bank {

    /**
     * Constructor for the gateway.
     */
    public function __construct() {
		$this->id                 = 'bank_bca';
		$this->name 			  = 'Bank BCA';
		
		$this->init(); 	
    }	
}
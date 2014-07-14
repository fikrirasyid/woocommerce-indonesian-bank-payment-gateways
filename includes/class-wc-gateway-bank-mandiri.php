<?php
/**
 * Bank Transfer Payment Gateway
 *
 * Provides a Bank Mandiri Transfer Payment Gateway.
 *
 * @class 		WC_Gateway_Bank_Mandiri
 * @extends		WC_Gateway_Bank
 * @version		2.1.0
 * @package		WooCommerce/Classes/Payment
 * @author 		Fikri Rasyid
 */
class WC_Gateway_Bank_Mandiri extends WC_Gateway_Bank {

    /**
     * Constructor for the gateway.
     */
    public function __construct() {
		$this->id                 = 'bank_mandiri';
		$this->name 			  = 'Bank Mandiri';
		
		$this->init(); 	
    }	
}
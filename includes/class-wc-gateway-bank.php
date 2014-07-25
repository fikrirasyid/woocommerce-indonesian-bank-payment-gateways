<?php
/**
 * Bank Transfer Payment Gateway
 *
 * Provides a Bank Transfer Payment Gateway class template
 *
 * @class 		WC_Gateway_Bank
 * @extends		WC_Payment_Gateway
 * @version		2.1.0
 * @package		WooCommerce/Classes/Payment
 * @author 		Fikri Rasyid
 */
class WC_Gateway_Bank extends WC_Payment_Gateway {

    /**
     * Constructor for the gateway.
     */
    public function __construct() {
		$this->id                 = 'bank';
		$this->name 			  = 'Bank';   	
		
		$this->init();
    }	

    /**
     * Init the class
     */
    function init(){
		$this->icon               = apply_filters( "woocommerce_{$this->id}_icon", '' );
		$this->has_fields         = false;
		$this->method_title       = __( $this->name, 'woocommerce-indonesian-bank-payment-gateways' );
		$this->method_description = __( "Allows payments using direct bank/wire transfer by {$this->name}.", 'woocommerce-indonesian-bank-payment-gateways' );

        // Define user set variables
		$this->title        = $this->get_option( 'title', sprintf( __( 'Direct Transfer %s', 'woocommerce-indonesian-bank-payment-gateways' ), $this->name ) );
		$this->description  = $this->get_option( 'description', $this->method_description );
		$this->instructions = $this->get_option( 'instructions', $this->method_description );

		// Bank BCA account fields shown on the thanks page and in emails
		$this->account_details = get_option( "woocommerce_{$this->id}_accounts",
			array(
				'account_name'   => array( 
					'label' => __( 'Account Name', 'woocommerce-indonesian-bank-payment-gateways' ), 
					'value' => $this->get_option( 'account_name', '-' ) 
				),
				'account_number' => array(
					'label' => __( 'Account Number', 'woocommerce-indonesian-bank-payment-gateways' ),
					'value' => $this->get_option( 'account_number', '-' )
				)
			)
		);

		// Actions
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
    	add_action( "woocommerce_thankyou_{$this->id}", array( $this, 'thankyou_page' ) );

    	// Customer Emails
    	add_action( 'woocommerce_email_after_order_table', array( $this, 'email_instructions' ), 10, 3 );


		// Load the settings.
		$this->init_form_fields();
		$this->init_settings(); 
    }

    /**
     * Initialise Gateway Settings Form Fields
     */
    public function init_form_fields() {
    	$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'woocommerce-indonesian-bank-payment-gateways' ),
				'type'    => 'checkbox',
				'label'   => __( "Enable {$this->name} Transfer", 'woocommerce-indonesian-bank-payment-gateways' ),
				'default' => 'yes'
			),
			'title' => array(
				'title'       => __( 'Title', 'woocommerce-indonesian-bank-payment-gateways' ),
				'type'        => 'text',
				'description' => __( 'This title will be seen by the customer upon checkout process', 'woocommerce-indonesian-bank-payment-gateways' ),
				'default'     => __( "Direct {$this->name} Transfer", 'woocommerce-indonesian-bank-payment-gateways' ),
				'desc_tip'    => true,
			),
			'description' => array(
				'title'       => __( 'Description', 'woocommerce-indonesian-bank-payment-gateways' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description that the customer will see on your checkout.', 'woocommerce-indonesian-bank-payment-gateways' ),
				'default'     => __( 'Make your payment directly into our bank account. Please use your Order ID as the payment reference. Your order won\'t be shipped until the funds have cleared in our account.', 'woocommerce-indonesian-bank-payment-gateways' ),
				'desc_tip'    => true,
			),
			'instructions' => array(
				'title'       => __( 'Instructions', 'woocommerce-indonesian-bank-payment-gateways' ),
				'type'        => 'textarea',
				'description' => __( 'Instructions that will be added to the thank you page and emails.', 'woocommerce-indonesian-bank-payment-gateways' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'account_name' => array(
				'title'       => __( 'Account Name', 'woocommerce-indonesian-bank-payment-gateways' ),
				'type'        => 'text',
				'description' => __( 'This account name is displayed during checkout process and related emails to the customer', 'woocommerce-indonesian-bank-payment-gateways' ),
				'default'     => __( 'John Doe', 'woocommerce-indonesian-bank-payment-gateways' ),
				'desc_tip'    => true,
			),
			'account_number' => array(
				'title'       => __( 'Account Number', 'woocommerce-indonesian-bank-payment-gateways' ),
				'type'        => 'text',
				'description' => __( 'This account number is displayed during checkout process and related emails to the customer', 'woocommerce-indonesian-bank-payment-gateways' ),
				'default'     => __( '0381912123819', 'woocommerce-indonesian-bank-payment-gateways' ),
				'desc_tip'    => true,
			),
		);
    }

    /**
     * Output for the order received page.
     */
    public function thankyou_page( $order_id ) {
		if ( $this->instructions ) {
        	echo wpautop( wptexturize( wp_kses_post( $this->instructions ) ) );
        }
        $this->bank_details( $order_id );
    }    

    /**
     * Add content to the WC emails.
     *
     * @access public
     * @param WC_Order $order
     * @param bool $sent_to_admin
     * @param bool $plain_text
     * @return void
     */
    public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
    	if ( ! $sent_to_admin && $this->id === $order->payment_method && 'on-hold' === $order->status ) {
			if ( $this->instructions ) {
				echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
			}
			$this->bank_details( $order->id );
		}
    }

    /**
     * Get bank details and place into a list format
     */
    private function bank_details( $order_id = '' ) {
    	if ( empty( $this->account_details ) ) {
    		return;
    	}

    	$this->account_information();
    }

    /**
     * Process the payment and return the result
     *
     * @param int $order_id
     * @return array
     */
    public function process_payment( $order_id ) {

		$order = new WC_Order( $order_id );

		// Mark as on-hold (we're awaiting the payment)
		$order->update_status( 'on-hold', __( 'Awaiting payment through', 'woocommerce-indonesian-bank-payment-gateways' ) );

		// Reduce stock levels
		$order->reduce_order_stock();

		// Remove cart
		WC()->cart->empty_cart();

		// Return thankyou redirect
		return array(
			'result' 	=> 'success',
			'redirect'	=> $this->get_return_url( $order )
		);
    }

	/**
	 * If There are no payment fields show the description if set.
	 * Override this in your gateway if you have some.
	 *
	 * @access public
	 * @return void
	 */
	public function payment_fields() {
		if ( $description = $this->get_description() ) {
			echo wpautop( wptexturize( $description ) );
		}

		$this->account_information();
	}

	/**
	 * Display account information
	 * 
	 * @return void
	 */
	function account_information(){
    	echo "<h2 class='order_details_title {$this->id}'>" . sprintf( apply_filters( 'wc_gateway_bank_order_details_title', __( "Our %s Account", 'woocommerce-indonesian-bank-payment-gateways' ) ), $this->name ) . "</h2>" . PHP_EOL;

    	$bank_account = apply_filters( "woocommerce_{$this->id}_accounts", $this->account_details );

		$bank_account = (object) $bank_account;

		echo '<ul class="order_details bank_details">' . PHP_EOL;

		foreach ( $bank_account as $field_key => $field ) {
		    if ( ! empty( $field['value'] ) ) {
		    	echo '<li class="' . esc_attr( $field_key ) . '"><span class="label">' . esc_attr( $field['label'] ) . '</span>: <strong>' . wptexturize( $field['value'] ) . '</strong></li>' . PHP_EOL;
		    }
		}

		echo '</ul>';
	}
}
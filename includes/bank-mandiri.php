<?php
/**
 * Bank Transfer Payment Gateway
 *
 * Provides a Bank Transfer Payment Gateway. Based on WC_Gateway_Bank Mandiri class included on WooCommerce.
 *
 * @class 		WC_Gateway_Bank_Mandiri
 * @extends		WC_Payment_Gateway
 * @version		2.1.0
 * @package		WooCommerce/Classes/Payment
 * @author 		Fikri Rasyid
 */
class WC_Gateway_Bank_Mandiri extends WC_Payment_Gateway {

    /**
     * Constructor for the gateway.
     */
    public function __construct() {
		$this->id                 = 'bank_mandiri';
		$this->icon               = apply_filters('woocommerce_bank_mandiri_icon', '');
		$this->has_fields         = false;
		$this->method_title       = __( 'Bank Mandiri', 'woocommerce-indonesian-bank-payment-gateways' );
		$this->method_description = __( 'Allows payments using direct bank/wire transfer by Bank Mandiri.', 'woocommerce-indonesian-bank-payment-gateways' );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

        // Define user set variables
		$this->title        = $this->get_option( 'title' );
		$this->description  = $this->get_option( 'description' );
		$this->instructions = $this->get_option( 'instructions', $this->description );

		// Bank Mandiri account fields shown on the thanks page and in emails
		$this->account_details = get_option( 'woocommerce_bank_mandiri_accounts',
			array(
				array(
					'account_name'   => $this->get_option( 'account_name' ),
					'account_number' => $this->get_option( 'account_number' ),
					'sort_code'      => $this->get_option( 'sort_code' ),
					'bank_name'      => $this->get_option( 'bank_name' ),
					'iban'           => $this->get_option( 'iban' ),
					'bic'            => $this->get_option( 'bic' )
				)
			)
		);

		// Actions
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'save_account_details' ) );
    	add_action( 'woocommerce_thankyou_bank_mandiri', array( $this, 'thankyou_page' ) );

    	// Customer Emails
    	add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
    }	

    /**
     * Initialise Gateway Settings Form Fields
     */
    public function init_form_fields() {
    	$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'woocommerce-indonesian-bank-payment-gateways' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Bank Mandiri Transfer', 'woocommerce-indonesian-bank-payment-gateways' ),
				'default' => 'yes'
			),
			'title' => array(
				'title'       => __( 'Title', 'woocommerce-indonesian-bank-payment-gateways' ),
				'type'        => 'text',
				'description' => __( 'This title will be seen by the customer upon checkout process', 'woocommerce-indonesian-bank-payment-gateways' ),
				'default'     => __( 'Direct Bank Mandiri Transfer', 'woocommerce-indonesian-bank-payment-gateways' ),
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
    	if ( ! $sent_to_admin && 'bacs' === $order->payment_method && 'on-hold' === $order->status ) {
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

    	echo '<h2>' . __( 'Our Bank Mandiri Account', 'woocommerce-indonesian-bank-payment-gateways' ) . '</h2>' . PHP_EOL;

    	$bank_mandiri_accounts = apply_filters( 'woocommerce_bank_mandiri_accounts', $this->account_details );

    	if ( ! empty( $bank_mandiri_accounts ) ) {
	    	foreach ( $bank_mandiri_accounts as $bank_mandiri_account ) {
	    		echo '<ul class="order_details bank_mandiri_details">' . PHP_EOL;

	    		$bank_mandiri_account = (object) $bank_mandiri_account;

	    		// Bank Mandiri fields shown on the thanks page and in emails
				$account_fields = apply_filters( 'woocommerce_bank_mandiri_account_fields', array(
					'account_name'=> array(
						'label' => __( 'Account Name', 'woocommerce-indonesian-bank-payment-gateways' ),
						'value' => $bank_mandiri_account->account_name
					),
					'account_number'=> array(
						'label' => __( 'Account Number', 'woocommerce-indonesian-bank-payment-gateways' ),
						'value' => $bank_mandiri_account->account_number
					),
				), $order_id );

				if ( $bank_mandiri_account->account_name || $bank_mandiri_account->bank_name ) {
					echo '<h3>' . implode( ' - ', array_filter( array( $bank_mandiri_account->account_name, $bank_mandiri_account->bank_name ) ) ) . '</h3>' . PHP_EOL;
				}

	    		foreach ( $account_fields as $field_key => $field ) {
				    if ( ! empty( $field['value'] ) ) {
				    	echo '<li class="' . esc_attr( $field_key ) . '">' . esc_attr( $field['label'] ) . ': <strong>' . wptexturize( $field['value'] ) . '</strong></li>' . PHP_EOL;
				    }
				}

	    		echo '</ul>';
	    	}
	    }
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
}
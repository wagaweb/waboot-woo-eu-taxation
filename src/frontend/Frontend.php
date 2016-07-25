<?php

namespace WBWooFI\frontend;
use WBF\includes\AssetsManager;
use WBF\includes\Utilities;

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the dashboard-specific stylesheet and JavaScript.
 *
 * @package    WBWooFI
 * @subpackage WBWooFI/public
 */
class Frontend {

	/**
	 * The main plugin class
	 * @var \WBWooFI\includes\Plugin
	 */
	private $plugin;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param null|string $plugin_name @deprecated
	 * @param null|string $version @deprecated
	 * @param null $core The plugin main object
	 */
	public function __construct( $plugin_name = null, $version = null, $core = null ) {
		if(isset($core)) $this->plugin = $core;
	}

	public function styles(){
		wp_enqueue_style('wb-woo-fi-style', $this->plugin->get_uri() . '/assets/dist/css/wb-woo-fi.min.css');
	}

	public function scripts(){
		$scripts = [
			"wb-woo-fi" => [
				'uri' => $this->plugin->is_debug() ? $this->plugin->get_uri() . 'assets/dist/js/bundle.js' : $this->plugin->get_uri() . 'assets/dist/js/wb-woo-fi.min.js',
				'path' => $this->plugin->is_debug() ? $this->plugin->get_dir() . 'assets/dist/js/bundle.js' : $this->plugin->get_dir() . 'assets/dist/js/wb-woo-fi.min.js',
				'deps' => ['jquery','backbone','underscore'],
				'i10n' => [
					'name' => 'wbFIData',
					'params' => [
						'ajax_url' => admin_url('admin-ajax.php'),
						'blogurl' => get_bloginfo("wpurl"),
						'isAdmin' => is_admin()
					]
				],
				'type' => 'js',
				'in_footer' => false,
				'enqueue' => true
			]
		];
		$am = new AssetsManager($scripts);
		$am->enqueue();
	}

	/*
	 * Performs action on update order review
	 *
	 * @param string $post_data (the query string for post_data)
	 *
	 * @hooked 'woocommerce_checkout_update_order_review'
	 */
	public function on_update_order_review($post_data){
		$post_data = explode("&",$post_data);
		$data_values = [];
		foreach($post_data as $data_string){
			preg_match("/billing_wb_woo_fi_customer_type=([a-zA-Z0-9]+)/",$data_string,$matches);
			if(is_array($matches)){

			}
			preg_match("/billing_wb_woo_fi_fiscal_code=([a-zA-Z0-9]+)/",$data_string,$matches);
			if(is_array($matches)){

			}
			preg_match("/billing_wb_woo_fi_vat=([a-zA-Z0-9]+)/",$data_string,$matches);
			if(is_array($matches)){

			}
		}
		if(isset($data_values['billing_wb_woo_fi_customer_type'])){
			$this->add_customer_type_to_customer_data($data_values['billing_wb_woo_fi_customer_type']);
		}
		if(isset($data_values['billing_wb_woo_fi_fiscal_code'])){
			$this->add_fiscal_code_to_customer_data($data_values['billing_wb_woo_fi_fiscal_code']);
		}
		if(isset($data_values['billing_wb_woo_fi_vat'])){
			$this->add_vat_to_customer_data($data_values['billing_wb_woo_fi_vat']);
		}
	}

	/**
	 * Calculate tax amount for prices exclusive of taxes
	 *
	 * @see WC_TAX::calc_exclusive_tax
	 *
	 * @hooked 'woocommerce_price_ex_tax_amount'
	 */
	public function on_calculate_ex_tax_amount($tax_amount, $key, $rate, $price){
		$customer = WC()->customer;
		$cart = WC()->cart;
		$custom_rates = $this->plugin->get_custom_tax_rate_settings();
		return $tax_amount;
	}

	/**
	 * Calculate tax amount for prices inclusive of taxes
	 *
	 * @see WC_TAX::calc_exclusive_tax
	 *
	 * @hooked 'woocommerce_price_ex_tax_amount'
	 */
	public function on_calculate_inc_tax_amount($tax_amount, $key, $rate, $price){
		$customer = WC()->customer;
		$cart = WC()->cart;
		$custom_rates = $this->plugin->get_custom_tax_rate_settings();
		return $tax_amount;
	}
	
	/**
	 * Adds our fields to billing ones
	 *
	 * @hooked 'woocommerce_billing_fields'
	 *
	 * @param $address_fields
	 * @param $country
	 *
	 * @return array
	 */
	public function add_billing_fields($address_fields, $country){
		$customer_type = [
			"billing_wb_woo_fi_customer_type" => [
				'label' => _x("Are you an individual or a company?", "WC Field", $this->plugin->get_textdomain()),
				'type' => 'radio',
				'options' => [
					'individual' => _x("Individual","WC Field",$this->plugin->get_textdomain()),
					'company' => _x("Company","WC Field",$this->plugin->get_textdomain())
				],
				'default' => '',
				'required' => true
			]
		];
		$fiscal_code = [
			"billing_wb_woo_fi_fiscal_code" => [
				'label' => _x("Fiscal code", "WC Field", $this->plugin->get_textdomain()),
				'type' => 'text',
				'validate' => ['fiscal-code'],
				'class' => ['hidden']
			]
		];
		$vat = [
			"billing_wb_woo_fi_vat" => [
				'label' => _x("VAT", "WC Field", $this->plugin->get_textdomain()),
				'type' => 'text',
				'validate' => ['vat'],
				'class' => ['hidden'],
				'custom_attributes' => [
					'country' => $country
				]
			]
		];
		$address_fields = Utilities::associative_array_add_element_after($customer_type,"billing_last_name",$address_fields);
		//if($country == "IT"){
			$address_fields = Utilities::associative_array_add_element_after($fiscal_code,"billing_wb_woo_fi_customer_type",$address_fields);
			$address_fields = Utilities::associative_array_add_element_after($vat,"billing_wb_woo_fi_fiscal_code",$address_fields);
		//}else{
		//	$address_fields = Utilities::associative_array_add_element_after($vat,"billing_wb_woo_fi_customer_type",$address_fields);
		//}
		return $address_fields;
	}

	/**
	 * Performs validation on fiscal code
	 *
	 * @credit Umberto Salsi <salsi@icosaedro.it>
	 *
	 * @hooked 'woocommerce_process_checkout_field_*'
	 *
	 * @param $fiscal_code
	 *
	 * @return mixed/**
	 */
	function validate_fiscal_code_on_checkout($fiscal_code){
		$result = call_user_func( function($fiscal_code) {

			$fiscal_code = str_replace(' ', '', $fiscal_code);

			$result = [
				'is_valid' => false,
				'err_message' => ''
			];

			if( $fiscal_code === '' ) {
				$result['err_message'] = _x("The fiscal code is mandatory","WC Field",$this->plugin->get_textdomain());
				return $result;
			}
			if( strlen($fiscal_code) != 16 ) {
				$result['err_message'] = _x( "La lunghezza del codice fiscale non &egrave;\n"
				                             . "corretta: il codice fiscale dovrebbe essere lungo\n"
				                             . "esattamente 16 caratteri.", "WC Field", $this->plugin->get_textdomain() );

				return $result;
			}
			$fiscal_code = strtoupper($fiscal_code);
			if( preg_match("/^[A-Z0-9]+\$/", $fiscal_code) != 1 ){
				$result['err_message'] = _x( "Il codice fiscale contiene dei caratteri non validi:\n"
				                             ."i soli caratteri validi sono le lettere e le cifre.", "WC Field", $this->plugin->get_textdomain() );
				return $result;
			}
			$s = 0;
			for( $i = 1; $i <= 13; $i += 2 ){
				$c = $fiscal_code[$i];
				if( strcmp($c, "0") >= 0 and strcmp($c, "9") <= 0 )
					$s += ord($c) - ord('0');
				else
					$s += ord($c) - ord('A');
			}
			for( $i = 0; $i <= 14; $i += 2 ){
				$c = $fiscal_code[$i];
				switch( $c ){
					case '0':  $s += 1;  break;
					case '1':  $s += 0;  break;
					case '2':  $s += 5;  break;
					case '3':  $s += 7;  break;
					case '4':  $s += 9;  break;
					case '5':  $s += 13;  break;
					case '6':  $s += 15;  break;
					case '7':  $s += 17;  break;
					case '8':  $s += 19;  break;
					case '9':  $s += 21;  break;
					case 'A':  $s += 1;  break;
					case 'B':  $s += 0;  break;
					case 'C':  $s += 5;  break;
					case 'D':  $s += 7;  break;
					case 'E':  $s += 9;  break;
					case 'F':  $s += 13;  break;
					case 'G':  $s += 15;  break;
					case 'H':  $s += 17;  break;
					case 'I':  $s += 19;  break;
					case 'J':  $s += 21;  break;
					case 'K':  $s += 2;  break;
					case 'L':  $s += 4;  break;
					case 'M':  $s += 18;  break;
					case 'N':  $s += 20;  break;
					case 'O':  $s += 11;  break;
					case 'P':  $s += 3;  break;
					case 'Q':  $s += 6;  break;
					case 'R':  $s += 8;  break;
					case 'S':  $s += 12;  break;
					case 'T':  $s += 14;  break;
					case 'U':  $s += 16;  break;
					case 'V':  $s += 10;  break;
					case 'W':  $s += 22;  break;
					case 'X':  $s += 25;  break;
					case 'Y':  $s += 24;  break;
					case 'Z':  $s += 23;  break;
				}
			}
			if( chr($s%26 + ord('A')) != $fiscal_code[15] ) {
				$result['err_message'] = _x( "Il codice fiscale non &egrave; corretto:\n"
				                             ."il codice di controllo non corrisponde.", "WC Field", $this->plugin->get_textdomain() );
				return $result;
			}
			if (empty($result['err_message'])) {
				$result['is_valid'] = true;
				return $result;
			}

		}, $fiscal_code);

		if(!$result['is_valid']){
			wc_add_notice( apply_filters( 'wb_woo_fi/invalid_fiscal_code_field_notice', sprintf( $result['err_message'], '<strong>Fiscal code</strong>' ) ), 'error' );
		}
		return $fiscal_code;
	}

	/**
	 * Performs validation on VAT
	 *
	 * @hooked 'woocommerce_process_checkout_field_*'
	 *
	 * @param $vat
	 *
	 * @return mixed
	 */
	function validate_vat_on_checkout($vat){

		$countries = new \WC_Countries();
		$cc = substr($vat, 0, 2);
		$vn = substr($vat, 2);

		$eu_countries = $countries->get_european_union_countries();

		if (in_array($cc, $eu_countries)) {
			$params = [
				'countryCode' => $cc,
				'vatNumber' => $vn
			];

			$client = new \SoapClient('http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl');
			$response = $client->__soapCall("checkVat", array($params) );

			if(!$response->valid){
				wc_add_notice( apply_filters( 'wb_woo_fi/invalid_vat_field_notice', sprintf( _x( '%s is not a valid.', 'WC Validation Message', $this->plugin->get_textdomain() ), '<strong>VAT</strong>' ) ), 'error' );
			}
		}

		return $vat;
	}

	function add_vat_to_customer_data($vat){
		if(isset($vat)){
			WC()->customer->billing_wb_woo_fi_vat = $vat;
		}
		return $vat;
	}

	function add_fiscal_code_to_customer_data($fiscal_code){
		if(isset($fiscal_code)){
			WC()->customer->billing_wb_woo_fi_fiscal_code = $fiscal_code;
		}
		return $fiscal_code;
	}

	function add_customer_type_to_customer_data($customer_type){
		if(isset($customer_type)){
			WC()->customer->billing_wb_woo_fi_customer_type = $customer_type;
		}
		return $customer_type;
	}
}
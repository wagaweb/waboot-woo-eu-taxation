<?php

namespace WBWooFI\frontend;
use WBF\includes\AssetsManager;
use WBF\includes\Utilities;
use WBWooFI\includes\Plugin;

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
		//wp_enqueue_style('wb-woo-fi-style', $this->plugin->get_uri() . '/assets/dist/css/wb-woo-fi.min.css');
		//For now we have only this style to enqueue, an entire file is not necessary.
		if(function_exists("is_checkout") && is_checkout()){
			?>
			<style>
				.wbfi-hidden {
					display: none !important;
				}
			</style>
			<?php
		}
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
						'isAdmin' => is_admin(),
						'fields_id' => [
							'customer_type' => Plugin::FIELD_CUSTOMER_TYPE,
							'fiscal_code' => Plugin::FIELD_FISCAL_CODE,
							'vat' => Plugin::FIELD_VAT,
							'vies_valid_check' => Plugin::FIELD_VIES_VALID_CHECK
						],
						'eu_vat_countries' => WC()->countries->get_european_union_countries('eu_vat')
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
		//Detect and save the customer type into WC Customer instance
		$data_values = [];
		foreach($post_data as $data_string){
			preg_match("/".Plugin::FIELD_CUSTOMER_TYPE."=([a-zA-Z0-9]+)/",$data_string,$matches);
			if(is_array($matches) && isset($matches[1])){
				$data_values[Plugin::FIELD_CUSTOMER_TYPE] = $matches[1];
				continue;
			}
			preg_match("/".Plugin::FIELD_VIES_VALID_CHECK."=([a-zA-Z0-9]+)/",$data_string,$matches);
			if(is_array($matches) && isset($matches[1])){
				$data_values[Plugin::FIELD_VIES_VALID_CHECK] = true;
				continue;
			}
		}
		if(!isset($data_values[Plugin::FIELD_VIES_VALID_CHECK])){
			$data_values[Plugin::FIELD_VIES_VALID_CHECK] = false;
		}
		if(!empty($data_values)){
			$this->inject_customer_data($data_values);
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
		if($this->plugin->can_exclude_taxes($key)){
			$tax_amount = 0; //WC does a sum of all applicable taxes. So by putting the "invalid" ones to 0, WC does not count them.
		}
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
			Plugin::FIELD_CUSTOMER_TYPE => [
				'label' => _x("Customer type", "WC Field", $this->plugin->get_textdomain()),
				'type' => 'radio',
				'options' => [
					'individual' => _x("Private individual","WC Field",$this->plugin->get_textdomain()),
					'company' => _x("Company","WC Field",$this->plugin->get_textdomain())
				],
				'default' => '',
				'required' => true
			]
		];
		$fiscal_code = [
			Plugin::FIELD_FISCAL_CODE => [
				'label' => _x("Fiscal code", "WC Field", $this->plugin->get_textdomain()),
				'type' => 'text',
				'validate' => ['fiscal-code'],
				'class' => ['wbfi-hidden']
			]
		];
		$vat = [
			Plugin::FIELD_VAT => [
				'label' => _x("VAT", "WC Field", $this->plugin->get_textdomain()),
				'type' => 'text',
				'validate' => ['vat'],
				'class' => ['wbfi-hidden'],
				'custom_attributes' => [
					'country' => $country
				]
			]
		];
		$vies_valid_check = [
			Plugin::FIELD_VIES_VALID_CHECK => [
				'label' => _x("My VAT is VIES Valid", "WC Field", $this->plugin->get_textdomain()),
				'type' => 'checkbox',
				'class' => ['wbfi-hidden'],
			]
		];
		$address_fields = Utilities::associative_array_add_element_after($customer_type,"billing_last_name",$address_fields);
		//if($country == "IT"){
			$address_fields = Utilities::associative_array_add_element_after($fiscal_code,Plugin::FIELD_CUSTOMER_TYPE,$address_fields);
			$address_fields = Utilities::associative_array_add_element_after($vat,Plugin::FIELD_FISCAL_CODE,$address_fields);
			$address_fields = Utilities::associative_array_add_element_after($vies_valid_check,Plugin::FIELD_VAT,$address_fields);
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
		if(!isset($_POST[Plugin::FIELD_CUSTOMER_TYPE]) || $_POST['billing_country'] != "IT") return $fiscal_code;
		$result = $this->plugin->validate_fiscal_code($fiscal_code);
		if(!$result['is_valid']){
			wc_add_notice( apply_filters( 'wb_woo_fi/invalid_fiscal_code_field_notice', sprintf( $result['err_message'], '<strong>'.__("Codice fiscale", $this->plugin->get_textdomain()).'</strong>' ) ), 'error' );
		}
		return $fiscal_code;
	}

	/**
	 * Ajax callback to validate a fiscal code
	 */
	public function ajax_validate_fiscal_code(){
		if(!defined("DOING_AJAX") || !DOING_AJAX) return;
		$fiscal_code = isset($_POST['fiscal_code']) ? $_POST['fiscal_code'] : false;
		if(!$fiscal_code){
			echo json_encode([
				'valid' => false,
				'error' => __("Non è stato fornito un codice fiscale valido", $this->plugin->get_textdomain())
			]);
			die();
		}
		$result = $this->plugin->validate_fiscal_code($fiscal_code);
		echo json_encode([
			'valid' => $result['is_valid'],
			'error' => $result['err_message']
		]);
		die();
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
		if(!isset($_POST[Plugin::FIELD_CUSTOMER_TYPE]) || $_POST[Plugin::FIELD_CUSTOMER_TYPE] == "individual") return $vat;
		if(isset($_POST[Plugin::FIELD_VIES_VALID_CHECK])){
			//Advanced validation
			$vies_validation_flag = true;
		}else{
			//Simple validation
			$vies_validation_flag = false;
		}
		if(!$this->plugin->validate_eu_vat($vat,$vies_validation_flag)){
			wc_add_notice( apply_filters( 'wb_woo_fi/invalid_vat_field_notice',
				sprintf(
					_x( '%s is not a valid.', 'WC Validation Message', $this->plugin->get_textdomain() ),
					'<strong>'.__("VAT Number", $this->plugin->get_textdomain()).'</strong>'
				)
			), 'error' );
		}
		return $vat;
	}

	/**
	 * Ajax callback to validate an EU VAT
	 */
	public function ajax_validate_eu_vat(){
		if(!defined("DOING_AJAX") || !DOING_AJAX) return;
		$vat = isset($_POST['vat']) ? $_POST['vat'] : false;
		$view_check = isset($_POST['view_check']) ? (bool) $_POST['view_check'] : false;
		if(!$vat){
			echo json_encode([
				'valid' => false,
				'error' => __("No valid VAT provided", $this->plugin->get_textdomain())
			]);
			die();
		}
		$result = $this->plugin->validate_eu_vat($vat,$view_check);
		echo json_encode([
			'valid' => $result,
			'error' => !$result ? __("No valid VAT provided", $this->plugin->get_textdomain()) : ""
		]);
		die();
	}

	/**
	 * Adds customer type to WC Customer object
	 *
	 * @hooked 'woocommerce_process_checkout_field_*'
	 *
	 * @param $customer_type
	 *
	 * @return mixed
	 */
	function add_customer_type_to_customer_data($customer_type){
		if(isset($customer_type)){
			$field_name = Plugin::FIELD_CUSTOMER_TYPE;
			WC()->customer->$field_name = $customer_type;
		}
		return $customer_type;
	}

	/**
	 * Adds customer type to WC Customer object
	 *
	 * @hooked 'woocommerce_process_checkout_field_*'
	 *
	 * @param $vat
	 *
	 * @return mixed
	 */
	function add_vat_to_customer_data($vat){
		if(isset($customer_type)){
			$field_name = Plugin::FIELD_VAT;
			WC()->customer->$field_name = $vat;
		}
		return $vat;
	}

	/**
	 * Adds custom data to WC Customer object
	 *
	 * @param $data
	 */
	function inject_customer_data($data){
		foreach($data as $key => $value){
			WC()->customer->$key = $value;
		}
	}
}
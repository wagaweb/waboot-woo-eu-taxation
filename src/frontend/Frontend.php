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
		//Detect and save the customer type into WC Customer instance
		$data_values = [];
		foreach($post_data as $data_string){
			preg_match("/billing_wb_woo_fi_customer_type=([a-zA-Z0-9]+)/",$data_string,$matches);
			if(is_array($matches) && isset($matches[1])){
				$data_values['billing_wb_woo_fi_customer_type'] = $matches[1];
			}
		}
		if(isset($data_values['billing_wb_woo_fi_customer_type'])){
			$this->add_customer_type_to_customer_data($data_values['billing_wb_woo_fi_customer_type']);
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
		if(!$this->plugin->can_apply_custom_tax_rate($key)){
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
		if(!$this->plugin->validate_eu_vat($vat)){
			wc_add_notice( apply_filters( 'wb_woo_fi/invalid_vat_field_notice', sprintf( _x( '%s is not a valid.', 'WC Validation Message', $this->plugin->get_textdomain() ), '<strong>'.__("Partita IVA", $this->plugin->get_textdomain()).'</strong>' ) ), 'error' );
		}
		return $vat;
	}

	/**
	 * Ajax callback to validate an EU VAT
	 */
	public function ajax_validate_eu_vat(){
		if(!defined("DOING_AJAX") || !DOING_AJAX) return;
		$vat = isset($_POST['vat']) ? $_POST['vat'] : false;
		if(!$vat){
			echo json_encode([
				'valid' => false,
				'error' => __("Non è stata fornita una partita IVA valida", $this->plugin->get_textdomain())
			]);
			die();
		}
		$result = $this->plugin->validate_eu_vat($vat);
		echo json_encode([
			'valid' => $result,
			'error' => !$result ? __("Non è stata fornita una partita IVA valida", $this->plugin->get_textdomain()) : ""
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
			WC()->customer->billing_wb_woo_fi_customer_type = $customer_type;
		}
		return $customer_type;
	}
}
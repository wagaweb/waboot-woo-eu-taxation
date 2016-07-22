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
			"wb_woo_fi_customer_type" => [
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
			"wb_woo_fi_fiscal_code" => [
				'label' => _x("Fiscal code", "WC Field", $this->plugin->get_textdomain()),
				'type' => 'text',
				'validate' => ['fiscal-code'],
				'class' => ['hidden']
			]
		];
		$vat = [
			"wb_woo_fi_vat" => [
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
			$address_fields = Utilities::associative_array_add_element_after($fiscal_code,"wb_woo_fi_customer_type",$address_fields);
			$address_fields = Utilities::associative_array_add_element_after($vat,"wb_woo_fi_fiscal_code",$address_fields);
		//}else{
		//	$address_fields = Utilities::associative_array_add_element_after($vat,"wb_woo_fi_customer_type",$address_fields);
		//}
		return $address_fields;
	}

	/**
	 * Performs validation on fiscal code
	 *
	 * @hooked 'woocommerce_process_checkout_field_*'
	 *
	 * @param $fiscal_code
	 *
	 * @return mixed
	 */
	function validate_fiscal_code_on_checkout($fiscal_code){
		$is_valid = false;
		if(!$is_valid){
			wc_add_notice( apply_filters( 'wb_woo_fi/invalid_fiscal_code_field_notice', sprintf( _x( '%s is not a valid.', 'WC Validation Message', $this->plugin->get_textdomain() ), '<strong>Fiscal code</strong>' ) ), 'error' );
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
	function validate_vat_during_on_checkout($vat){
		$is_valid = false;
		if(!$is_valid){
			wc_add_notice( apply_filters( 'wb_woo_fi/invalid_vat_field_notice', sprintf( _x( '%s is not a valid.', 'WC Validation Message', $this->plugin->get_textdomain() ), '<strong>VAT</strong>' ) ), 'error' );
		}
		return $vat;
	}
}
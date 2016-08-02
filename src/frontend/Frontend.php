<?php

namespace WBWooFI\frontend;
use WBF\components\assets\AssetsManager;
use WBF\components\utils\Utilities;
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
							'request_invoice' => Plugin::FIELD_REQUEST_INVOICE,
							'customer_type' => Plugin::FIELD_CUSTOMER_TYPE,
							'fiscal_code' => Plugin::FIELD_FISCAL_CODE,
							'vat' => Plugin::FIELD_VAT,
							'vies_valid_check' => Plugin::FIELD_VIES_VALID_CHECK
						],
						'eu_vat_countries' => WC()->countries->get_european_union_countries('eu_vat'),
						'invoice_required' => get_option(Plugin::FIELD_ADMIN_REQUEST_INVOICE_CHECK,"no"),
						'shop_billing_country' => $this->plugin->get_shop_billing_country()
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
	 * Adds correct checkbox values to WC()->customer before checkout validation.
	 * For some reason "woocommerce_checkout_update_order_review" recognize as always present the checkboxes selected at least one time.
	 */
	public function add_checkboxes_options_to_customer_on_checkout(){
		$post_data = $_POST;
		$data_values = [];
		if(!isset($post_data[Plugin::FIELD_REQUEST_INVOICE])){
			$data_values[Plugin::FIELD_REQUEST_INVOICE] = false;
		}
		if(!isset($data_values[Plugin::FIELD_VIES_VALID_CHECK])){
			$data_values[Plugin::FIELD_VIES_VALID_CHECK] = false;
		}
		if(!empty($data_values)){
			$this->inject_customer_data($data_values);
		}
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
			preg_match("/".Plugin::FIELD_REQUEST_INVOICE."=([a-zA-Z0-9]+)/",$data_string,$matches);
			if(is_array($matches) && isset($matches[1])){
				$data_values[Plugin::FIELD_REQUEST_INVOICE] = true;
				continue;
			}
		}
		if(!isset($data_values[Plugin::FIELD_VIES_VALID_CHECK])){
			$data_values[Plugin::FIELD_VIES_VALID_CHECK] = false; //add_checkboxes_options_to_customer_on_checkout() should take care of this case, but you never know.
		}
		if(!isset($data_values[Plugin::FIELD_REQUEST_INVOICE])){
			$data_values[Plugin::FIELD_REQUEST_INVOICE] = false; //add_checkboxes_options_to_customer_on_checkout() should take care of this case, but you never know.s
		}
		if(!empty($data_values)){
			$this->inject_customer_data($data_values);
		}
	}

	/**
	 * If there is no tax rate for customer billing country, this will search for the base IVA for shop billing country.
	 * The shop owner must provide a tax rate called "%IVA%" with the country selected as billing country.
	 * 
	 * @see \WC_Tax::get_matched_tax_rates()
	 *
	 * @hooked 'woocommerce_matched_tax_rates'
	 */
	public function maybe_add_shop_billing_country_tax_to_item_taxes($matched_tax_rates, $country, $state, $postcode, $city, $tax_class){
		global $wpdb;
		//Check if there already a rate for $country
		$country_rate = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_country = '$country'");
		//If not, search if there is for the country set by user in out options
		if(empty($country_rate)){
			$shop_billing_country = $this->plugin->get_shop_billing_country();
			$shop_country_rate = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_country = '$shop_billing_country' AND tax_rate_name LIKE('%IVA%') AND tax_rate_class = '$tax_class'");
			if(!empty($shop_country_rate)){
				$new_matched_rate = $shop_country_rate[0];
				$matched_tax_rates[intval($new_matched_rate->tax_rate_id)] = [
					'rate' => $new_matched_rate->tax_rate,
					'label' => $new_matched_rate->tax_rate_name,
					'shipping' => $new_matched_rate->tax_rate_shipping == "1" ? "yes" : "no",
					'compound' => $new_matched_rate->tax_rate_compound == "1" ? "yes" : "no"
				];
			}
		}
		return $matched_tax_rates;
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
		$invoice_required = get_option(Plugin::FIELD_ADMIN_REQUEST_INVOICE_CHECK,"no");

		$request_billing = [
			Plugin::FIELD_REQUEST_INVOICE => [
				'label' => _x("Request invoice", "WC Field", $this->plugin->get_textdomain()),
				'type' => 'checkbox'
			]
		];
		$customer_type = [
			Plugin::FIELD_CUSTOMER_TYPE => [
				'label' => _x("Customer type", "WC Field", $this->plugin->get_textdomain()),
				'type' => 'select',
				'options' => [
					'individual' => _x("Private individual","WC Field",$this->plugin->get_textdomain()),
					'company' => _x("Company","WC Field",$this->plugin->get_textdomain())
				],
				'default' => 'individual',
				'required' => $invoice_required == "yes",
				'class' => $invoice_required != "yes" ? ['wbfi-hidden'] : []
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

		if($invoice_required == "yes"){
			$address_fields = array_merge($address_fields,$customer_type,$fiscal_code,$vat,$vies_valid_check);
		}else{
			$address_fields = array_merge($address_fields,$request_billing,$customer_type,$fiscal_code,$vat,$vies_valid_check);
		}


		return $address_fields;
	}

	/**
	 * Performs validation on $customer_type
	 *
	 * @hooked 'woocommerce_process_checkout_field_*'
	 *
	 * @param $customer_type
	 *
	 * @return string
	 */
	function validate_customer_type_on_checkout($customer_type){
		if($this->plugin->is_invoice_data_required()){
			if($customer_type == ""){
				wc_add_notice( apply_filters( 'wb_woo_fi/invalid_customer_type_field_notice',
					sprintf(
						_x( '%s is required.', 'WC Validation Message', $this->plugin->get_textdomain() ),
						'<strong>'.__("Customer type", $this->plugin->get_textdomain()).'</strong>' )
				), 'error' );
			}
		}
		return $customer_type;
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
	 * @return mixed
	 */
	function validate_fiscal_code_on_checkout($fiscal_code){
		if(!isset($_POST[Plugin::FIELD_CUSTOMER_TYPE]) || $_POST['billing_country'] != "IT" || !$this->plugin->is_invoice_data_required()) return $fiscal_code;
		$result = $this->plugin->validate_fiscal_code($fiscal_code);
		if(!$result['is_valid']){
			wc_add_notice( apply_filters( 'wb_woo_fi/invalid_fiscal_code_notice',
				sprintf(
					_x( '%s is not valid.', 'WC Validation Message', $this->plugin->get_textdomain() ),
					'<strong>'.__("Fiscal Code", $this->plugin->get_textdomain()).'</strong>'
				)
			), 'error' );
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
				'error' => __("Fiscal code is not valid", $this->plugin->get_textdomain())
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
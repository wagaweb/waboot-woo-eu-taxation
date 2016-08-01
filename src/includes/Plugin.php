<?php

namespace WBWooFI\includes;
use WBF\includes\pluginsframework\TemplatePlugin;
use WBF\includes\Utilities;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, dashboard-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    WBWooFI
 * @subpackage WBWooFI/includes
 */
class Plugin extends TemplatePlugin {

	const FIELD_CUSTOMER_TYPE = "billing_wb_woo_fi_customer_type";
	const FIELD_FISCAL_CODE = "billing_wb_woo_fi_fiscal_code";
	const FIELD_VAT = "billing_wb_woo_fi_vat";
	const FIELD_VIES_VALID_CHECK = "billing_wb_woo_fi_vies_valid";

	const FIELD_ADMIN_SHOP_BILLING_COUNTRY = "wb_woo_fi_shop_billing_country";
	const FIELD_ADMIN_MANDATORY_CHECK = "wb_woo_fi_mandatory_check";

	/**
	 * Define the core functionality of the plugin.
	 */
	public function __construct() {
		parent::__construct( "wb-woo-fiscalita-italiana", plugin_dir_path( dirname( dirname( __FILE__ ) ) ) );
		if(in_array("woocommerce/woocommerce.php",get_option("active_plugins",[]))){
			$this->define_public_hooks();
			$this->define_admin_hooks();
		}else{
			add_action("admin_init", function(){
				Utilities::add_admin_notice("wb-woo-fi-require-wc",__("WB Fiscalità Italiana for WooCommerce requires WooCommerce to work"),"nag",["category" => '_flash_']);
			});
		}
	}

	/**
	 * Register all of the hooks related to the public-facing functionality of the plugin.
	 */
	private function define_public_hooks() {
		$plugin_public = $this->loader->public_plugin;
		
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'scripts' );

		//Tax management
		$this->loader->add_action("woocommerce_checkout_update_order_review", $plugin_public, "on_update_order_review");
		$this->loader->add_filter("woocommerce_price_ex_tax_amount", $plugin_public, "on_calculate_ex_tax_amount", 10, 4);
		$this->loader->add_filter("woocommerce_price_inc_tax_amount", $plugin_public, "on_calculate_inc_tax_amount", 10, 4);

		//Checkout and account fields
		$this->loader->add_filter( 'woocommerce_' . "billing_" . 'fields', $plugin_public, 'add_billing_fields', 10, 2 );

		//Fields management
		$this->loader->add_filter("woocommerce_process_checkout_field_".self::FIELD_CUSTOMER_TYPE, $plugin_public, "add_customer_type_to_customer_data", 10, 1);
		$this->loader->add_filter("woocommerce_process_checkout_field_".self::FIELD_VAT, $plugin_public, "add_vat_to_customer_data", 10, 1);

		//Fields backend validation
		$this->loader->add_filter("woocommerce_process_checkout_field_"."billing_wb_woo_fi_fiscal_code", $plugin_public, "validate_fiscal_code_on_checkout", 11, 1);
		$this->loader->add_filter("woocommerce_process_checkout_field_"."billing_wb_woo_fi_vat", $plugin_public, "validate_vat_on_checkout", 11, 1);

		//Ajax
		$this->loader->add_action( 'wp_ajax_validate_fiscal_code', $plugin_public, "ajax_validate_fiscal_code" );
		$this->loader->add_action( 'wp_ajax_nopriv_validate_fiscal_code', $plugin_public, "ajax_validate_fiscal_code" );
		$this->loader->add_action( 'wp_ajax_validate_vat', $plugin_public, "ajax_validate_eu_vat" );
		$this->loader->add_action( 'wp_ajax_nopriv_validate_vat', $plugin_public, "ajax_validate_eu_vat" );
	}

	/**
	 * Register all of the hooks related to the admin-facing functionality of the plugin.
	 */
	private function define_admin_hooks(){
		$plugin_admin = $this->loader->admin_plugin;

		$this->loader->add_action('admin_init', $plugin_admin, 'save_custom_tax_rate_settings');

		$this->loader->add_filter('woocommerce_customer_meta_fields', $plugin_admin, 'add_woocommerce_customer_meta_fields');

		$this->loader->add_filter('woocommerce_tax_settings', $plugin_admin, 'add_tax_settings');

		$this->loader->add_filter("woocommerce_get_sections_"."tax", $plugin_admin, "alter_tax_sections", 10, 1);
		$this->loader->add_filter("woocommerce_get_settings_"."tax", $plugin_admin, "display_tax_settings", 10, 1);
	}

	public function get_tax_rates(){
		//Get the already set tax rates
		$tax_classes[] = ""; //For some odd reason, the "standard" tax rate is identified by an empty string.
		$tax_classes = array_merge($tax_classes,\WC_Tax::get_tax_classes());
		$rates = [];
		foreach ($tax_classes as $tax_class){
			$rates[$tax_class] = \WC_Tax::get_rates_for_tax_class($tax_class);
		}
		return $rates;
	}
	
	/**
	 * Get the custom tax rate settings
	 */
	public function get_custom_tax_rate_settings(){
		$rates = $this->get_tax_rates();
		$default = [
			'apply_to_customer_type' => [],
			'add_to_tax_exclusion' => call_user_func(function() use($rates){
				$r = [];
				foreach($rates as $r_name => $r_values){
					foreach ($r_values as $r_key => $rate){
						$r[$r_key] = true;
					}
				}
				return $r;
			})
		];
		$opt = get_option($this->get_plugin_name()."_custom_rates_settings",$default);
		$opt = wp_parse_args($opt,$default);
		return $opt;
	}

	/**
	 * Set the custom tax rate settings
	 */
	public function set_custom_tax_rate_settings($rates){
		return update_option($this->get_plugin_name()."_custom_rates_settings",$rates);
	}

	/**
	 * Checks if $rate_id can be applied to the $customer_type
	 * 
	 * @param $rate_id
	 * @param bool $customer_type
	 *
	 * @return bool
	 */
	public function can_apply_custom_tax_rate($rate_id,$customer_type = false){
		$custom_rates = $this->get_custom_tax_rate_settings();

		//Get the current custom rate appliance rule:
		$current_custom_rate_group = array_key_exists($rate_id,$custom_rates) ? $custom_rates[$rate_id] : "both";

		//Get the current user customer type
		if(!$customer_type){
			$customer_type = "individual";
			if(isset(WC()->customer->billing_wb_woo_fi_customer_type)){
				$customer_type = WC()->customer->billing_wb_woo_fi_customer_type;
			}else{
				$current_user = wp_get_current_user();
				if($current_user instanceof \WP_User){
					$ct = get_user_meta($current_user->ID,"billing_wb_woo_fi_customer_type",true);
					if($ct && !empty($ct)){
						$customer_type = $ct;
					}
				}
			}	
		}
		
		return $current_custom_rate_group == "both" || $current_custom_rate_group == $customer_type;
	}

	/**
	 * Check if the tax exclusion rule can be applied to $rate_id
	 *
	 * @param $rate_id
	 * @param bool $customer_type
	 *
	 * @return bool
	 */
	public function can_exclude_taxes($rate_id){
		$vies_valid_check_field_name = Plugin::FIELD_VIES_VALID_CHECK;
		if(!isset(WC()->customer->$vies_valid_check_field_name) || !WC()->customer->$vies_valid_check_field_name) return false;
		$custom_rates = $this->get_custom_tax_rate_settings();
		return array_key_exists($rate_id,$custom_rates['add_to_tax_exclusion']) && $custom_rates['add_to_tax_exclusion'][$rate_id];
	}

	/**
	 * Validate a fiscal code
	 *
	 * @credit Umberto Salsi <salsi@icosaedro.it>
	 *
	 * @param string $fiscal_code
	 *
	 * @return array with 'is_valid' and 'err_message' keys.
	 */
	public function validate_fiscal_code($fiscal_code, $required = true){
		$fiscal_code = str_replace(' ', '', $fiscal_code);

		$result = [
			'is_valid' => false,
			'err_message' => ''
		];

		if( $fiscal_code === '' && $required ) {
			$result['err_message'] = sprintf(
				_x("%s is required","WC Field Validation", $this->get_textdomain()),
				"<strong>".__("Fiscal code",$this->get_textdomain())."<strong>"
			);
			return $result;
		}
		if( strlen($fiscal_code) != 16 ) {
			$result['err_message'] = sprintf(
				_x("%s. Must have 16 character.","WC Field Validation",$this->get_textdomain()),
				"<strong>"._x("Incorrect fiscal code length","WC Field Validation",$this->get_textdomain())."<strong>"
			);
			return $result;
		}
		$fiscal_code = strtoupper($fiscal_code);
		if( preg_match("/^[A-Z0-9]+\$/", $fiscal_code) != 1 ){
			$result['err_message'] = sprintf(
				_x("%s. Only letters and numbers are valid.","WC Field Validation",$this->get_textdomain()),
				"<strong>"._x("Invalid fiscal code","WC Field Validation",$this->get_textdomain())."<strong>"
			);
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
			$result['err_message'] = sprintf(
				_x("%s. Wrong control code detected.","WC Field Validation",$this->get_textdomain()),
				"<strong>"._x("Invalid fiscal code","WC Field Validation",$this->get_textdomain())."<strong>"
			);
			return $result;
		}
		if (empty($result['err_message'])) {
			$result['is_valid'] = true;
			return $result;
		}else{
			$result['err_message'] = sprintf(
				_x("%s. Unexpected error occurred. Please contact the administration.","WC Field Validation",$this->get_textdomain()),
				"<strong>"._x("Invalid fiscal code","WC Field Validation",$this->get_textdomain())."<strong>"
			);
			return $result;
		}
	}

	/**
	 * Validate an EU VAT number.
	 *
	 * @param $vat
	 * @param bool $vies_vat
	 *
	 * @return bool
	 */
	public function validate_eu_vat($vat, $vies_vat = false){
		if($vies_vat){
			return $this->validate_eu_vies_vat($vat);
		}else{
			return $this->validate_eu_simple_vat($vat);
		}
	}

	/**
	 * A simple VAT Validation
	 *
	 * @param $vat
	 *
	 * @return bool
	 */
	public function validate_eu_simple_vat($vat){
		if($vat == "" || !is_string($vat)){
			return false;
		}
		return true; //todo: implement this
	}

	/**
	 * Validate an EU VIES VAT number. Uses public EU API.
	 * 
	 * @param $vat
	 *
	 * @return bool
	 */
	public function validate_eu_vies_vat($vat){
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

			if(isset($response->valid) && $response->valid){
				return true;
			}
		}
		
		return false;
	}

	/**
	 * Load the required dependencies for this plugin (called into parent::_construct())
	 */
	protected function load_dependencies() {
		parent::load_dependencies();
	}
}

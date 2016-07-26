<?php
namespace WBWooFI\admin;
use WBF\includes\mvc\HTMLView;
use WBWooFI\includes\WCFI_Settings_Tax;

use WBF\includes\Utilities;
/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the dashboard-specific stylesheet and JavaScript.
 *
 * @package    WBWooFI
 * @subpackage WBWooFI/admin
 */
class Admin {

	/**
	 * The main plugin class
	 * @var \WBWooFI\includes\Plugin
	 *
	 * [IT] E' possibile utilizzare $this->plugin->public_plugin per riferirsi alla classe in class-admin.php
	 */
	private $plugin;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @var      string    $plugin_name       The name of the plugin.
	 * @var      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name = null, $version = null, $core = null ) {
		if(isset($core)) $this->plugin = $core;
	}


	public function add_woocommerce_customer_meta_fields($fields_array) {

		$fields = $fields_array['billing']['fields'];
		$billing_wb_woo_fi_customer_type = [
			"billing_wb_woo_fi_customer_type" => [
				'label' => __('Customer Type', "WC Field", $this->plugin->get_textdomain()),
				'description' => "",
				'type' => 'select',
				'options' => [
					'' => __('Select a Customer Category', "WC Field", $this->plugin->get_textdomain()),
					'individual' => __('Individual', "WC Field", $this->plugin->get_textdomain()),
					'company' => __('Company', "WC Field", $this->plugin->get_textdomain())
				]
			]
		];
		$billing_wb_woo_fi_fiscal_code = [
			"billing_wb_woo_fi_fiscal_code" => [
				'label' => __('Fiscal Code', "WC Field", $this->plugin->get_textdomain()),
				'description' => ""
				]
		];
		$billing_wb_woo_fi_vat = [
			"billing_wb_woo_fi_vat" => [
				'label' => __('VAT', "WC Field", $this->plugin->get_textdomain()),
				'description' => ""
			]
		];
		$new_fields = Utilities::associative_array_add_element_after($billing_wb_woo_fi_customer_type,"billing_company",$fields);
		$new_fields = Utilities::associative_array_add_element_after($billing_wb_woo_fi_fiscal_code,"billing_wb_woo_fi_customer_type",$new_fields);
		$new_fields = Utilities::associative_array_add_element_after($billing_wb_woo_fi_vat,"billing_wb_woo_fi_fiscal_code",$new_fields);

		$fields_array['billing']['fields'] = $new_fields;
		return $fields_array;

	}

	/**
	 * Handle the save operation for the custom tax rates settings
	 *
	 * @hooked 'admin_init'
	 */
	public function save_custom_tax_rate_settings(){
		if(isset($_POST['_wp_http_referer']) && preg_match("/private_and_company_taxes/",$_POST['_wp_http_referer'])){
			$validated = [
				'apply_to_customer_type' => [],
				'add_to_tax_exclusion' => []
			];
			if(isset($_POST['apply_to_customer_type'])){
				$validated['apply_to_customer_type'] = $_POST['apply_to_customer_type'];
			}
			$rates = $this->plugin->get_tax_rates();
			if(isset($_POST['add_to_tax_exclusion'])){
				foreach($_POST['add_to_tax_exclusion'] as $rate_key => $value){
					$validated['add_to_tax_exclusion'][$rate_key] = true;
				}
			}
			foreach($rates as $r_name => $r_values){
				foreach($r_values as $r_id => $rate){
					if(!array_key_exists($r_id,$validated['add_to_tax_exclusion'])){
						$validated['add_to_tax_exclusion'][$r_id] = false;
					}
				}
			}
			$r = $this->plugin->set_custom_tax_rate_settings($validated);
			if($r){
				Utilities::add_admin_notice("rate_settings_updated",__("Custom rates settings updated successfully."),"updated");
			}
		}
	}

	/**
	 * Inject our setting tab
	 *
	 * @hooked 'woocommerce_get_sections_tax'
	 *
	 * @param $settings
	 *
	 * @return mixed
	 */
	public function alter_tax_sections($sections){
		$sections['private_and_company_taxes'] = __( 'Private and Company Rates', $this->plugin->get_textdomain() );
		return $sections;
	}

	/**
	 * Inject our settings page.
	 *
	 * WARNING: This is insane.
	 * Dear WooCommerce, why in the name of your god of choice you had hardcoded the tax rate tables columns and made so difficult to add more tabs that are neither a series of settings or a rate table?
	 *
	 * @param $settings
	 *
	 * @return array
	 */
	public function display_tax_settings($settings){
		global $current_section;
		if($current_section == "private_and_company_taxes"){
			$v = new HTMLView("src/views/admin/html-settings-tax.php","wb-woo-fiscalita-italiana");

			//Get the already set tax rates
			$rates = $this->plugin->get_tax_rates();

			$v->clean()->display([
				'rates' => $rates,
				'textdomain' => $this->plugin->get_textdomain(),
				'settings' => $this->plugin->get_custom_tax_rate_settings(),
				'select_options' => [
					'both' => _x("Both", "Admin table", $this->plugin->get_textdomain()),
					'individual' => _x("Individual", "Admin table", $this->plugin->get_textdomain()),
					'company' => _x("Company", "Admin table", $this->plugin->get_textdomain()),
				],
				'checkbox' => [
					'value' => '1',
					'label' => _x("Escludi dalle imposte", "Admin table", $this->plugin->get_textdomain())
				]
			]);
			return [];
		}
		return $settings;
	}
}
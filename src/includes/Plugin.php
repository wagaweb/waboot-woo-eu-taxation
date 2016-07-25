<?php

namespace WBWooFI\includes;
use WBF\includes\pluginsframework\TemplatePlugin;

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
	/**
	 * Define the core functionality of the plugin.
	 */
	public function __construct() {
		parent::__construct( "wb-woo-fiscalita-italiana", plugin_dir_path( dirname( dirname( __FILE__ ) ) ) );
		$this->define_public_hooks();
		$this->define_admin_hooks();
	}

	/**
	 * Register all of the hooks related to the public-facing functionality of the plugin.
	 */
	private function define_public_hooks() {
		$plugin_public = $this->loader->public_plugin;
		
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'scripts' );

		//Tax management
		//$this->loader->add_action("woocommerce_checkout_update_order_review", $plugin_public, "on_update_order_review");
		//$this->loader->add_filter("woocommerce_price_ex_tax_amount", $plugin_public, "on_calculate_ex_tax_amount", 10, 4);
		//$this->loader->add_filter("woocommerce_price_inc_tax_amount", $plugin_public, "on_calculate_inc_tax_amount", 10, 4);

		//Checkout and account fields
		$this->loader->add_filter( 'woocommerce_' . "billing_" . 'fields', $plugin_public, 'add_billing_fields', 10, 2 );

		//Fields management
		//$this->loader->add_filter("woocommerce_process_checkout_field_"."billing_wb_woo_fi_customer_type", $plugin_public, "add_customer_type_to_customer_data", 10, 1);
		//$this->loader->add_filter("woocommerce_process_checkout_field_"."billing_wb_woo_fi_vat", $plugin_public, "add_vat_to_customer_data", 10, 1);
		//$this->loader->add_filter("woocommerce_process_checkout_field_"."billing_wb_woo_fi_fiscal_code", $plugin_public, "add_fiscal_code_to_customer_data", 10, 1);

		//Fields backend validation
		$this->loader->add_filter("woocommerce_process_checkout_field_"."billing_wb_woo_fi_fiscal_code", $plugin_public, "validate_fiscal_code_on_checkout", 11, 1);
		$this->loader->add_filter("woocommerce_process_checkout_field_"."billing_wb_woo_fi_vat", $plugin_public, "validate_vat_on_checkout", 11, 1);
	}

	/**
	 * Register all of the hooks related to the admin-facing functionality of the plugin.
	 */
	private function define_admin_hooks(){
		$plugin_admin = $this->loader->admin_plugin;

		$this->loader->add_action('admin_init', $plugin_admin, 'save_custom_tax_rate_settings');

		$this->loader->add_filter('woocommerce_customer_meta_fields', $plugin_admin, 'add_woocommerce_customer_meta_fields');

		$this->loader->add_filter("woocommerce_get_sections_"."tax", $plugin_admin, "alter_tax_sections", 10, 1);
		$this->loader->add_filter("woocommerce_get_settings_"."tax", $plugin_admin, "display_tax_settings", 10, 1);
	}

	/**
	 * Get the custom tax rate settings
	 */
	public function get_custom_tax_rate_settings(){
		return get_option($this->get_plugin_name()."_custom_rates_settings",[]);
	}

	/**
	 * Set the custom tax rate settings
	 */
	public function set_custom_tax_rate_settings($rates){
		return update_option($this->get_plugin_name()."_custom_rates_settings",$rates);
	}

	/**
	 * Load the required dependencies for this plugin (called into parent::_construct())
	 */
	protected function load_dependencies() {
		parent::load_dependencies();
	}
}

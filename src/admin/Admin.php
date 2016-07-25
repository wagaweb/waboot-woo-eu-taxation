<?php
namespace WBWooFI\admin;

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
}
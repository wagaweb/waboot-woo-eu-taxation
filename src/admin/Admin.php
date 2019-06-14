<?php
namespace WBWooEUT\admin;

use WBF\components\mvc\HTMLView;
use WBWooEUT\includes\Plugin;
use WBF\components\utils\Utilities;

/**
 * The public-facing functionality of the plugin.
 *
 * @package WBWooEUT
 */
class Admin {

	/**
	 * The main plugin class
	 * @var \WBWooEUT\includes\Plugin
	 */
	private $plugin;

	const MENU_SLUG = "custom_eu_settings";

	/**
	 * Initialize the class and set its properties.
	 *
	 * @var string $plugin_name The name of the plugin.
	 * @var string $version The version of this plugin.
	 */
	public function __construct( $plugin_name = null, $version = null, $core = null ) {
		if(isset($core)) $this->plugin = $core;
	}

	/**
	 * Display admin notice if required
	 *
	 * @hooked 'admin_init'
	 */
	public function display_admin_notice(){
		global $wpdb;
		$shop_billing_country = $this->plugin->get_shop_billing_country();
		$shop_country_rate = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_country = '$shop_billing_country'");
		if(empty($shop_country_rate)){
			Utilities::add_admin_notice("wb-woo-eut-required-tax",
				sprintf(
					__("WB Woo EU Taxation requires a tax rate with the following settings: <br/><br/> <strong>Country:</strong> %s <br/><br/> You can change shop billing country in WooCommerce tax settings. ", $this->plugin->get_textdomain()),
					$shop_billing_country,$shop_billing_country
				),
				"nag",
				["category" => "_flash_"]
			);
		}
	}

	/**
	 * Adds custom fields to customer administration in dashboard
	 *
	 * @hooked 'woocommerce_customer_meta_fields'
	 *
	 * @param $fields_array
	 *
	 * @return mixed
	 */
	public function add_woocommerce_customer_meta_fields($fields_array) {
        $req = $this->plugin->is_invoice_data_required();
		$fields = $fields_array['billing']['fields'];
		$billing_wb_woo_fi_customer_type = [
			Plugin::FIELD_CUSTOMER_TYPE => [
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
			Plugin::FIELD_FISCAL_CODE => [
				'label' => __('Fiscal Code', "WC Field", $this->plugin->get_textdomain()),
				'description' => ""
				]
		];
		$billing_wb_woo_fi_vat = [
			Plugin::FIELD_VAT => [
				'label' => __('VAT', "WC Field", $this->plugin->get_textdomain()),
				'description' => ""
			]
		];
		$new_fields = Utilities::associative_array_add_element_after($billing_wb_woo_fi_customer_type,"billing_company",$fields);
		$new_fields = Utilities::associative_array_add_element_after($billing_wb_woo_fi_fiscal_code,Plugin::FIELD_CUSTOMER_TYPE,$new_fields);
		$new_fields = Utilities::associative_array_add_element_after($billing_wb_woo_fi_vat,Plugin::FIELD_FISCAL_CODE,$new_fields);

		$fields_array['billing']['fields'] = $new_fields;
		return $fields_array;

	}

	/**
	 * Adds our custom settings to Tax Settings page
	 *
	 * @param $settings
	 *
	 * @return mixed
	 */
	public function add_tax_settings($settings){
		$custom_settings = [
			[
				'title'   => __( 'Shop billing country', $this->plugin->get_textdomain() ),
				'desc'    => __( 'Select the country your shop billing from', $this->plugin->get_textdomain() ),
				'id'      => Plugin::FIELD_ADMIN_SHOP_BILLING_COUNTRY,
				'type' => 'select',
				'class'   => 'wc-enhanced-select',
				'default' => apply_filters("wb-woo-eut/default_shop_billing_country","IT"),
				'options' => call_user_func(function(){
					$output = [];
					$countries = WC()->countries->get_countries();
					$eu_countries = WC()->countries->get_european_union_countries();
					foreach($eu_countries as $cc){
						if(array_key_exists($cc,$countries)){
							$output[$cc] = $countries[$cc];
						}
					}
					return $output;
				}),
			],
			[
				'title'   => __( 'Shop billing country tax rate as default for EU countries', $this->plugin->get_textdomain() ),
				'desc'    => __( 'The tax rate associated with shop billing country will be applied when there is no rates for customer billing country', $this->plugin->get_textdomain() ),
				'id'      => Plugin::FIELD_ADMIN_SHOP_BILLING_COUNTRY_RATE_AS_DEFAULT,
				'default' => 'yes',
				'type'    => 'checkbox',
			],
			[
				'title'   => __( 'Invoice data are required', $this->plugin->get_textdomain() ),
				'desc'    => __( 'Customer type, fiscal code and VAT number will be required', $this->plugin->get_textdomain() ),
				'id'      => Plugin::FIELD_ADMIN_REQUEST_INVOICE_CHECK,
				'default' => 'no',
				'type'    => 'checkbox',
			]
		];

		$top_elements = array_slice($settings, 0, count($settings)-1);
		$last_element = array_slice($settings, -1, 1);
		$new_settings = array_merge($top_elements,$custom_settings,$last_element);

		return $new_settings;
	}

	/**
	 * Handle the save operation for the custom tax rates settings
	 *
	 * @hooked 'admin_init'
	 */
	public function save_custom_tax_rate_settings(){
		if(isset($_POST['_wp_http_referer']) && preg_match("/".self::MENU_SLUG."/",$_POST['_wp_http_referer'])){
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
	 * @param $sections
	 *
	 * @return mixed
	 */
	public function alter_tax_sections($sections){
		$sections[self::MENU_SLUG] = __( 'EU Settings', $this->plugin->get_textdomain() );
		return $sections;
	}

	/**
	 * Inject our settings page.
	 *
	 * @param $settings
	 *
	 * @return array
	 */
	public function display_tax_settings($settings){
		global $current_section;
		if($current_section == self::MENU_SLUG){
			$v = new HTMLView("src/views/admin/html-settings-tax.php","waboot-woo-eu-taxation");

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
					'label' => _x("Exclude this tax when VAT is VIES Valid", "Admin table", $this->plugin->get_textdomain())
				]
			]);
			return [];
		}
		return $settings;
	}

	/**
	 * Display plugin custom meta in order details
	 *
	 * @hooked 'woocommerce_admin_order_data_after_order_details'
	 *
	 * @param \WC_Order $order
	 */
	public function display_custom_meta_on_order($order){
		$order_id = call_user_func(function() use($order){
			if(!$this->plugin->is_woocommerce_3()){
				return $order->id;
			}
			return $order->get_id();
		});

		$custom_meta = Plugin::get_custom_meta_from_order($order_id);

		$v = new HTMLView($this->plugin->get_src_dir()."/views/admin/order-custom-meta.php",$this->plugin,false);

		//Order data:
		if($this->plugin->is_woocommerce_3()){
			$billing_company = $order->get_billing_company();
		}else{
			$billing_company = $order->billing_company;
		}

		$v->display([
			'company_name' => isset($billing_company) ? $billing_company : "",
			'fiscal_code' => isset($custom_meta[Plugin::FIELD_FISCAL_CODE]) ? $custom_meta[Plugin::FIELD_FISCAL_CODE] : "",
			'vat' => isset($custom_meta[Plugin::FIELD_VAT]) ? $custom_meta[Plugin::FIELD_VAT] : "",
			'customer_type' => isset($custom_meta[Plugin::FIELD_CUSTOMER_TYPE]) ? Plugin::get_customer_type_label($custom_meta[Plugin::FIELD_CUSTOMER_TYPE]) : "",
			'unique_code' => isset($custom_meta[Plugin::FIELD_UNIQUE_CODE]) ? $custom_meta[Plugin::FIELD_UNIQUE_CODE] : "",
			'pec' => isset($custom_meta[Plugin::FIELD_PEC]) ? $custom_meta[Plugin::FIELD_PEC] : "",
			'textdomain' => $this->plugin->get_textdomain()
		]);
	}

	/**
	 * @param $column
	 */
	public function display_custom_meta_on_order_listing($column){
		global $post, $woocommerce, $the_order;

		$order_id = call_user_func(function() use($the_order){
			if(empty($the_order)) return 0;
			if(!$this->plugin->is_woocommerce_3()){
				return $the_order->id;
			}
			return $the_order->get_id();
		});

		if ( empty( $the_order ) || $order_id != $post->ID ) {
			$the_order = wc_get_order( $post->ID );
		}

		if(!$the_order instanceof \WC_Order) return;

		switch($column){
			case 'billing_address':
			case 'shipping_address':
				$custom_meta = Plugin::get_custom_meta_from_order($order_id);
				if(isset($custom_meta[Plugin::FIELD_REQUEST_INVOICE]) && $custom_meta[Plugin::FIELD_REQUEST_INVOICE]){
					$v = new HTMLView($this->plugin->get_src_dir()."/views/admin/order-custom-meta.php",$this->plugin,false);

					//Order data:
					if($this->plugin->is_woocommerce_3()){
						$billing_company = $the_order->get_billing_company();
					}else{
						$billing_company = $the_order->billing_company;
					}

					$v->display([
						'company_name' => isset($billing_company) ? $billing_company : "",
						'fiscal_code' => isset($custom_meta[Plugin::FIELD_FISCAL_CODE]) ? $custom_meta[Plugin::FIELD_FISCAL_CODE] : "",
						'vat' => isset($custom_meta[Plugin::FIELD_VAT]) ? $custom_meta[Plugin::FIELD_VAT] : "",
						'customer_type' => isset($custom_meta[Plugin::FIELD_CUSTOMER_TYPE]) ? Plugin::get_customer_type_label($custom_meta[Plugin::FIELD_CUSTOMER_TYPE]) : "",
						'textdomain' => $this->plugin->get_textdomain()
					]);
				}
				break;
		}
	}

	/**
	 * Adds the custom fields to admin new order email
	 *
	 * @param array $fields
	 * @param bool $sent_to_admin
	 * @param \WC_Order $order
	 *
	 * @hooked 'woocommerce_email_customer_details_fields'
	 *
	 * @return mixed
	 */
	public function add_custom_meta_fields_on_new_order_email($fields, $sent_to_admin, $order){
		if($sent_to_admin){
			$order_id = call_user_func(function() use($order){
				if(!$this->plugin->is_woocommerce_3()){
					return $order->id;
				}
				return $order->get_id();
			});
			$custom_meta = Plugin::get_custom_meta_from_order($order_id);
			if(isset($custom_meta[Plugin::FIELD_REQUEST_INVOICE]) && $custom_meta[Plugin::FIELD_REQUEST_INVOICE]){
				$fields['customer_type'] = [
					'label' => __('Customer Type',$this->plugin->get_textdomain()),
					'value' => $custom_meta[Plugin::FIELD_CUSTOMER_TYPE]
				];

				if(isset($custom_meta[Plugin::FIELD_CUSTOMER_TYPE]) && $custom_meta[Plugin::FIELD_CUSTOMER_TYPE] === "company"){
					$fields['company_name'] = [
						'label' => __('Company name',$this->plugin->get_textdomain()),
						'value' => $this->plugin->is_woocommerce_3() ? $order->get_billing_company() : $order->billing_company
					];
				}

				$fields['vat'] = [
					'label' => __('VAT',$this->plugin->get_textdomain()),
					'value' => $custom_meta[Plugin::FIELD_VAT]
				];

				$fields['fiscal_code'] = [
					'label' => __('Fiscal code',$this->plugin->get_textdomain()),
					'value' => $custom_meta[Plugin::FIELD_FISCAL_CODE]
				];
			}
		}
		return $fields;
	}
}
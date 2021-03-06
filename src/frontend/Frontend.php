<?php

namespace WBWooEUT\frontend;
use WBWooEUT\core\assets\AssetsManager;
use WBWooEUT\core\utils\Utilities;
use WBWooEUT\includes\Plugin;

/**
 * The public-facing functionality of the plugin.
 *
 * @package WBWooEUT
 */
class Frontend {

	/**
	 * The main plugin class
	 * @var \WBWooEUT\includes\Plugin
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
		//wp_enqueue_style('wb-woo-eut-style', $this->plugin->get_uri() . '/assets/dist/css/wb-woo-eut.min.css');
		//For now we have only this style to enqueue, an entire file is not necessary.
		if(function_exists("is_checkout") && is_checkout()){
			?>
			<style>
				.wbeut-hidden {
					display: none !important;
				}
			</style>
			<?php
		}
	}

	public function scripts(){
		$scripts = [
			"wb-woo-eut" => [
				'uri' => $this->plugin->is_debug() ? $this->plugin->get_uri() . 'assets/dist/js/bundle.js' : $this->plugin->get_uri() . 'assets/dist/js/wb-woo-eut.min.js',
				'path' => $this->plugin->is_debug() ? $this->plugin->get_dir() . 'assets/dist/js/bundle.js' : $this->plugin->get_dir() . 'assets/dist/js/wb-woo-eut.min.js',
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
							'vies_valid_check' => Plugin::FIELD_VIES_VALID_CHECK,
                            'unique_code' => Plugin::FIELD_UNIQUE_CODE,
                            'pec' => Plugin::FIELD_PEC
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

		if(!in_array($country,WC()->countries->get_european_union_countries())) return $matched_tax_rates;
		if(!$this->plugin->can_apply_shop_billing_country_as_default_tax_rate()) return $matched_tax_rates;

		//Check if there already a rate for $country
		$country_rate = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_country = '$country'");
		//If not, search if there is for the country set by user in out options
		if(empty($country_rate)){
			$shop_billing_country = $this->plugin->get_shop_billing_country();
			$shop_country_rate = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_country = '$shop_billing_country' AND tax_rate_class = '$tax_class'");
			if(!empty($shop_country_rate)){
				if(count($shop_country_rate) > 1){
					$new_matched_rate = call_user_func(function() use($shop_country_rate){
						$current_priority = 0;
						$new_matched_rate = [];
						foreach($shop_country_rate as $r){
							if(intval($r->tax_rate_priority) > $current_priority){
								$current_priority = intval($r->tax_rate_priority);
								$new_matched_rate = $r;
							}
						}
						return $new_matched_rate;
					});
				}else{
					$new_matched_rate = $shop_country_rate[0];
				}
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
		if($this->plugin->can_exclude_taxes($key)){
			$tax_amount = 0; //WC does a sum of all applicable taxes. So by putting the "invalid" ones to 0, WC does not count them.
		}
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
		//$req = $this->plugin->is_invoice_data_required() ? ' <abbr class="required" title="'.__("required", $this->plugin->get_textdomain()).'">*</abbr> ' : '';
		$req = '';
		//$invoice_required = get_option(Plugin::FIELD_ADMIN_REQUEST_INVOICE_CHECK,"no") === 'yes';
		$invoice_required = $this->plugin->is_invoice_data_required();

		/*
		 * We can't make them all required, because we need to differentiate between required fields when individual
		 * and required fields when company
		 */

		$request_billing = [
			Plugin::FIELD_REQUEST_INVOICE => [
				'label' => _x("Request invoice", "WC Field", $this->plugin->get_textdomain()),
				'type' => 'checkbox',
                'priority' => 120,
                //'class' => ['form-row-wide']
			]
		];
		$customer_type = [
			Plugin::FIELD_CUSTOMER_TYPE => [
				'label' => _x("Customer type", "WC Field", $this->plugin->get_textdomain()),
				'type' => 'select',
				'options' => [
                    'individual' => Plugin::get_customer_type_label('individual'),
					'company' => Plugin::get_customer_type_label('company'),
				],
				'default' => 'individual',
				'required' => $invoice_required,
				'class' => ['wbeut-hidden'],
				'priority' => 121
			]
		];
        $vat = [
            Plugin::FIELD_VAT => [
                'label' => _x("VAT", "WC Field", $this->plugin->get_textdomain()).$req,
                'type' => 'text',
                'validate' => ['vat'],
                'class' => ['form-row-wide wbeut-hidden'],
                'custom_attributes' => [
                    'country' => $country
                ],
                'priority' => 123
            ]
        ];
		$fiscal_code = [
			Plugin::FIELD_FISCAL_CODE => [
				'label' => _x("Fiscal code", "WC Field", $this->plugin->get_textdomain()).$req,
				'type' => 'text',
				'validate' => ['fiscal-code'],
				'class' => ['form-row-wide wbeut-hidden'],
				'priority' => 124
			]
		];
		$code = [
		    Plugin::FIELD_UNIQUE_CODE => [
                'label' => _x("Codice destinatario", "WC Field", $this->plugin->get_textdomain()).$req, //todo: trovare traduzione inglese
                'type' => 'text',
                'class' => ['wbeut-hidden'],
                'priority' => 126
            ]
        ];
        $pec = [
            Plugin::FIELD_PEC => [
                'label' => _x("PEC", "WC Field", $this->plugin->get_textdomain()).$req,
                'type' => 'text',
                'class' => ['wbeut-hidden'],
                'priority' => 127
            ]
        ];
		$vies_valid_check = [
			Plugin::FIELD_VIES_VALID_CHECK => [
				'label' => _x("My VAT is VIES Valid", "WC Field", $this->plugin->get_textdomain()),
				'type' => 'checkbox',
				'class' => ['wbeut-hidden'],
				'priority' => 128
			]
		];

		if($invoice_required){
			$address_fields = array_merge($address_fields,$customer_type,$vat,$vies_valid_check,$fiscal_code,$code,$pec);
		}else{
			$address_fields = array_merge($address_fields,$request_billing,$customer_type,$vat,$vies_valid_check,$fiscal_code,$code,$pec);
		}


		return $address_fields;
	}

    /**
     * Place "*" at our required fields label.
     * We can't make them all defaults, because we need to differentiate between required fields when individual and required fields when company
     *
     * @hooked 'woocommerce_form_field_args'
     */
	public function alter_woocommerce_form_field_args($args, $key, $value){
        $invoice_required = $this->plugin->is_invoice_data_required();
        if(!$invoice_required){
            return $args;
        }
        if(!$this->plugin->is_fillable_wbwooeut_field($key)){
            return $args;
        }
        $args['required'] = true; //By putting true here, WooCommerce place the "*" symbol at the field label
        return $args;
    }

	/**
	 * Move company billing field in another position
	 *
     * @hooked 'woocommerce_billing_fields', 11
     *
	 * @param array $billing_fields
	 * @param string $country
     *
     * @return array
	 */
	public function move_company_field($billing_fields,$country){
	    if(isset($billing_fields['billing_company'])){
		    $company_field = ["billing_company" => $billing_fields['billing_company']];
		    unset($billing_fields['billing_company']);
		    $billing_fields = Utilities::associative_array_add_element_after($company_field,'billing_wb_woo_fi_customer_type',$billing_fields);
            $billing_fields['billing_company']['priority'] = 125;
            $billing_fields['billing_company']['class'] = ['wbeut-hidden'];
	    }
        return $billing_fields;
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
	public function validate_customer_type_on_checkout($customer_type){
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
	public function validate_fiscal_code_on_checkout($fiscal_code){
        $has_to_validate_fiscal_code = call_user_func(function(){
	        if(!isset($_POST[Plugin::FIELD_REQUEST_INVOICE]) && !$this->plugin->is_invoice_data_required()) return false;
	        if(!isset($_POST[Plugin::FIELD_CUSTOMER_TYPE]) || $_POST['billing_country'] !== 'IT' || !$this->plugin->is_invoice_data_required()) return false;
	        if($_POST[Plugin::FIELD_CUSTOMER_TYPE] === "company") return false; //v2.1.6 - Do not verify fiscal code for companies (many companies use vat as fiscal code)
            return true;
        });

        $has_to_validate_fiscal_code = apply_filters('wb_woo_fi/checkout/must_validate_fiscal_code',$has_to_validate_fiscal_code);

        if(!$has_to_validate_fiscal_code) return $fiscal_code;

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
	public function validate_vat_on_checkout($vat){
		if(!isset($_POST[Plugin::FIELD_REQUEST_INVOICE]) && !$this->plugin->is_invoice_data_required()) return $vat;
	    if(!isset($_POST[Plugin::FIELD_CUSTOMER_TYPE]) || $_POST[Plugin::FIELD_CUSTOMER_TYPE] == "individual") return $vat;

	    if($vat === ''){
            wc_add_notice( sprintf( __( '%s is a required field.', 'woocommerce' ), '<strong>'.__("VAT Number", $this->plugin->get_textdomain()).'</strong>' ), 'error', array( 'id' => Plugin::FIELD_VAT ) );
            return $vat;
	    }

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
		$vies_check = isset($_POST['vies_check']) ? (bool) $_POST['vies_check'] : false;
		if(!$vat){
			echo json_encode([
				'valid' => false,
				'error' => __("No valid VAT provided", $this->plugin->get_textdomain())
			]);
			die();
		}
		$result = $this->plugin->validate_eu_vat($vat,$vies_check);
		echo json_encode([
			'valid' => $result,
			'error' => !$result ? __("No valid VAT provided", $this->plugin->get_textdomain()) : ""
		]);
		die();
	}

	/**
	 * Adds new order meta on checkout
     *
     * @hooked 'woocommerce_admin_order_data_after_billing_address'
	 */
	public function update_order_meta_on_checkout($order_id, $posted){
		$form_vars = $_POST;

		$invoice_required = $this->plugin->is_invoice_data_required();

		if( (isset($posted[Plugin::FIELD_REQUEST_INVOICE]) && $posted[Plugin::FIELD_REQUEST_INVOICE] == 1) || $invoice_required ){
			$new_meta = [
				Plugin::FIELD_CUSTOMER_TYPE => isset($form_vars[Plugin::FIELD_CUSTOMER_TYPE]) ? sanitize_text_field($form_vars[Plugin::FIELD_CUSTOMER_TYPE]) : false,
				Plugin::FIELD_VAT => isset($form_vars[Plugin::FIELD_VAT]) ? sanitize_text_field($form_vars[Plugin::FIELD_VAT]) : false,
				Plugin::FIELD_FISCAL_CODE => isset($form_vars[Plugin::FIELD_FISCAL_CODE]) ? sanitize_text_field($form_vars[Plugin::FIELD_FISCAL_CODE]) : false,
				Plugin::FIELD_PEC => isset($form_vars[Plugin::FIELD_PEC]) ? sanitize_text_field($form_vars[Plugin::FIELD_PEC]) : false,
				Plugin::FIELD_UNIQUE_CODE => isset($form_vars[Plugin::FIELD_UNIQUE_CODE]) ? sanitize_text_field($form_vars[Plugin::FIELD_UNIQUE_CODE]) : false,
				Plugin::FIELD_REQUEST_INVOICE => true,
			];
        }else{
			$new_meta = [
				Plugin::FIELD_REQUEST_INVOICE => false,
			];
        }

		$new_meta = array_filter($new_meta); //remove FALSE values

        foreach ($new_meta as $k => $v){
	        update_post_meta($order_id,$k,$v);
        }
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
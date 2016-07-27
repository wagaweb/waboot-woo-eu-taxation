import * as Backbone from 'backbone';
import $ from 'jquery';

export default class extends Backbone.Model{
    initialize() {
        "use strict";
        let fields_ids = wbFIData.fields_id,
            $checkout_form = $( 'form.checkout' ),
            $fiscal_code = $(fields_ids.fiscal_code+"_field"),
            $vat = $(fields_ids.vat+"_field");
        if($checkout_form.length > 0){
            $checkout_form.on("blur change", ".input-text, select, input:checkbox", this.validate_fields);
            $checkout_form.on("change", ".input-radio[name='billing_wb_woo_fi_customer_type']", this, this.toggle_fields);
            $checkout_form.on("change", "#billing_country", this, this.toggle_fields);
            //$(document).on("update_checkout", "body", this, this.toggle_fields);
        }
        if($fiscal_code.is(".hidden")){
            $fiscal_code.removeClass("validate-fiscal-code");
        }
        if($vat.is(".hidden")){
            $vat.removeClass("validate-vat");
        }
    }

    /**
     * Toggle visibility to fiscal code and vat number.
     *
     * @param event
     */
    toggle_fields(event){
        "use strict";
        let current_customer_type = $(".input-radio[name='billing_wb_woo_fi_customer_type']:checked").val(),
            current_country = $("#billing_country").val();

        if(current_customer_type === undefined) return;

        if(current_country == "IT"){
            this.show_fiscal_code();
        }else{
            this.hide_fiscal_code();
        }
        switch(current_customer_type){
            case 'individual':
                this.hide_vat();
                this.hide_vies_check();
                break;
            case 'company':
                this.show_vat();
                if(current_country != "IT" && $.inArray(current_country,wbFIData.eu_vat_countries)){
                    this.show_vies_check();
                }
                break;
        }
        $(document.body).trigger( 'update_checkout');
    }

    /**
     * Validate field callback.
     *
     * This mirror the format of validate_fields() in WooCommerce checkout.js
     */
    validate_fields(event){
        "use strict";
        let $el = $( this ),
            $parent = $el.closest( '.form-row' );

        var do_validation = function($el,$parent){
            let $order_review = $('.woocommerce-checkout-payment, .woocommerce-checkout-review-order-table'),
                validated = true;

            $order_review.block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });

            $.ajax(wbFIData.ajax_url,{
                data: {
                    action: "validate_fiscal_code",
                    fiscal_code: $el.val()
                },
                dataType: "json",
                method: "POST"
            }).done(function(data, textStatus, jqXHR){
                if(typeof data === "object"){
                    if(!data.valid){
                        validated = false;
                    }
                }
                if(validated){
                    $parent.removeClass( 'woocommerce-invalid validate-required woocommerce-invalid-required-field' ).addClass( 'woocommerce-validated' );
                }else{
                    $parent.removeClass( 'woocommerce-validated' ).addClass( 'validate-required woocommerce-invalid woocommerce-invalid-required-field' );
                }
                $order_review.unblock();
            }).fail(function(jqXHR, textStatus, errorThrown){
                $order_review.unblock();
            });
        };

        if( $parent.is( '.woocommerce-invalid' ) ){
            if ( $parent.is( '.validate-fiscal-code' ) ) {
                do_validation($el,$parent);
            }

            if ( $parent.is( '.validate-vat' ) ) {
                do_validation($el,$parent);
            }
        }
    }

    /**
     * Shows fiscal code
     * @param hide
     */
    show_fiscal_code(hide = false){
        let $fiscal_code = $(fields_ids.fiscal_code+"_field");
        if(hide){
            $fiscal_code.addClass("hidden").removeClass('validate-required validate-fiscal-code woocommerce-invalid-required-field woocommerce-invalid');
        }else{
            $fiscal_code.removeClass("hidden woocommerce-validated").addClass('validate-required validate-fiscal-code woocommerce-invalid-required-field woocommerce-invalid');
        }
    }

    /**
     * Hides fiscal codes
     */
    hide_fiscal_code(){
        this.show_fiscal_code(true);
    }

    /**
     * Shows VAT
     * @param hide
     */
    show_vat(hide = false){
        let $vat = $(fields_ids.vat+"_field");
        if(hide){
            $vat.addClass("hidden").removeClass('validate-required validate-vat woocommerce-invalid-required-field woocommerce-invalid');
        }else{
            $vat.removeClass("hidden woocommerce-validated").addClass('validate-required validate-vat woocommerce-invalid-required-field woocommerce-invalid');
        }
    }

    /**
     * Hides VAT
     */
    hide_vat(){
        this.show_vat(true)
    }

    /**
     * Shows VIES Check
     * @param hide
     */
    show_vies_check(hide = false){
        $vies_check = $(fields_ids.vies_valid_check+"_field");
        if(hide){
            $vies_check.addClass("hidden");
        }else{
            $vies_check.removeClass("hidden");
        }
    }

    /**
     * Hides VIES Check
     */
    hide_vies_check(){
        this.show_fiscal_code(true);
    }
}
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
            $checkout_form.on("change", ".input-radio[name='"+fields_ids.customer_type+"']", this, this.toggle_fields);
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
        let current_customer_type = $(".input-radio[name='"+wbFIData.fields_id.customer_type+"']:checked").val(),
            current_country = $("#billing_country").val();

        if(current_customer_type === undefined) return;

        if(current_country == "IT"){
            event.data.show_fiscal_code();
        }else{
            event.data.hide_fiscal_code();
        }
        switch(current_customer_type){
            case 'individual':
                event.data.hide_vat();
                event.data.hide_vies_check();
                break;
            case 'company':
                event.data.show_vat();
                if(current_country != "IT" && $.inArray(current_country,wbFIData.eu_vat_countries)){
                    event.data.show_vies_check();
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
     * @param show
     * @param mandatory
     */
    show_fiscal_code(show = true, mandatory = true){
        let $fiscal_code = $("#"+wbFIData.fields_id.fiscal_code+"_field");
        if(show){
            $fiscal_code.removeClass("hidden woocommerce-validated");
            if(mandatory){
                $fiscal_code.addClass('validate-required validate-fiscal-code woocommerce-invalid-required-field woocommerce-invalid');
            }
        }else{
            $fiscal_code.addClass("hidden");
            if(mandatory){
                $fiscal_code.removeClass('validate-required validate-fiscal-code woocommerce-invalid-required-field woocommerce-invalid');
            }
        }
    }

    /**
     * Hides fiscal codes
     */
    hide_fiscal_code(){
        this.show_fiscal_code(false);
    }

    /**
     * Shows VAT
     * @param show
     */
    show_vat(show = true){
        let $vat = $("#"+wbFIData.fields_id.vat+"_field");
        if(show){
            $vat.removeClass("hidden woocommerce-validated").addClass('validate-required validate-vat woocommerce-invalid-required-field woocommerce-invalid');
        }else{
            $vat.addClass("hidden").removeClass('validate-required validate-vat woocommerce-invalid-required-field woocommerce-invalid');
        }
    }

    /**
     * Hides VAT
     */
    hide_vat(){
        this.show_vat(false)
    }

    /**
     * Shows VIES Check
     * @param show
     */
    show_vies_check(show = true){
        let $vies_check = $("#"+wbFIData.fields_id.vies_valid_check+"_field");
        if(show){
            $vies_check.removeClass("hidden");
        }else{
            $vies_check.addClass("hidden");
        }
    }

    /**
     * Hides VIES Check
     */
    hide_vies_check(){
        this.show_vies_check(false);
    }
}
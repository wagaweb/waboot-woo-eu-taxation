import * as Backbone from 'backbone';
import $ from 'jquery';

export default class extends Backbone.Model{
    initialize() {
        "use strict";
        let fields_ids = wbFIData.fields_id,
            $checkout_form = $( 'form.checkout' ),
            $fiscal_code = $("#"+fields_ids.fiscal_code+"_field"),
            $vat = $("#"+fields_ids.vat+"_field");
        if($checkout_form.length > 0){
            //On request invoice change:
            $checkout_form.on("change", ".input-checkbox[name='"+fields_ids.request_invoice+"']", this, this.toggle_fields);
            //On form fields change:
            $checkout_form.on("blur change", ".input-text, select, input:checkbox", this, this.validate_fields);
            //On Customer type change:
            $checkout_form.on("change", ".select[name='"+fields_ids.customer_type+"']", this, this.toggle_fields);
            //On VIES check change:
            $checkout_form.on("change", ".input-checkbox[name='"+fields_ids.vies_valid_check+"']", this, function(event){
                //Do VAT validation
                let $vat = $(".input-text[name='"+fields_ids.vat+"']"),
                    $vat_parent = $vat.closest( '.form-row' ),
                    $vies_check = $(this);
                event.data.do_validation($vat,$vat_parent,{
                    action: "validate_vat",
                    vat: $vat.val(),
                    view_check: $vies_check.is(":checked") ? 1 : 0
                });
                //Trigger checkout update
                $(document.body).trigger( 'update_checkout');
            });
            //On Billing country change:
            $checkout_form.on("change", "#billing_country", this, this.toggle_fields);
            //$(document).on("update_checkout", "body", this, this.toggle_fields);
        }
        if($fiscal_code.is(".wbfi-hidden")){
            $fiscal_code.removeClass("validate-fiscal-code");
        }
        if($vat.is(".wbfi-hidden")){
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
        let $customer_type = $("#"+wbFIData.fields_id.customer_type+"_field"),
            current_customer_type = $(".select[name='"+wbFIData.fields_id.customer_type+"'] option:selected").val(),
            current_country = $("#billing_country").val(),
            $request_invoice_check = $(".input-checkbox[name='"+wbFIData.fields_id.request_invoice+"']");

        if($request_invoice_check.length > 0 && !$request_invoice_check.is(":checked") ){
            event.data.hide_all();
            return;
        }

        if(current_customer_type === undefined) return;

        if($customer_type.is(".wbfi-hidden")){
            event.data.show_customer_type();
        }

        //This is a jQuery event callback, so "this" is not a reference to the class. We sent the reference into the event.data propriety

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
                if(current_country != "IT" && $.inArray(current_country,wbFIData.eu_vat_countries) != -1){
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

        //This is a jQuery event callback, so "this" is not a reference to the class. We sent the reference into the event.data propriety

        if( $parent.is( '.woocommerce-invalid' ) ){
            if ( $parent.is( '.validate-fiscal-code' ) ) {
                event.data.do_validation($el,$parent,{
                    action: "validate_fiscal_code",
                    fiscal_code: $el.val()
                });
            }

            if ( $parent.is( '.validate-vat' ) ) {
                let $vies_check = $("#"+wbFIData.fields_id.vies_valid_check);
                event.data.do_validation($el,$parent,{
                    action: "validate_vat",
                    vat: $el.val(),
                    view_check: $vies_check.is(":checked") ? 1 : 0
                });
            }
        }
    }

    /**
     * Perform custom field validation
     *
     * @param $el
     * @param $parent
     * @param data
     */
    do_validation($el,$parent,data){
        let $order_review = $('.woocommerce-checkout-payment, .woocommerce-checkout-review-order-table'),
            validated = true;

        $order_review.block({
            message: null,
            overlayCSS: {
                background: '#fff',
                opacity: 0.6
            }
        });

        $parent.block({
            message: null,
            overlayCSS: {
                background: '#fff',
                opacity: 0.6
            }
        });

        $.ajax(wbFIData.ajax_url,{
            data: data,
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
            $parent.unblock();
        }).fail(function(jqXHR, textStatus, errorThrown){
            $parent.unblock();
        });
    }

    /**
     * Shows customer type
     * @param show
     * @param mandatory
     */
    show_customer_type(show = true, mandatory = true){
        let $customer_type = $("#"+wbFIData.fields_id.customer_type+"_field");
        if(show){
            $customer_type.removeClass("wbfi-hidden woocommerce-validated");
            if(mandatory){
                $customer_type.addClass('validate-required woocommerce-invalid-required-field woocommerce-invalid');
            }
        }else{
            $customer_type.addClass("wbfi-hidden");
            if(mandatory){
                $customer_type.removeClass('validate-required woocommerce-invalid-required-field woocommerce-invalid');
            }
        }
    }

    /**
     * Hides the customer type
     */
    hide_customer_type(){
        this.show_customer_type(false);
    }

    /**
     * Shows fiscal code
     * @param show
     * @param mandatory
     */
    show_fiscal_code(show = true, mandatory = true){
        let $fiscal_code = $("#"+wbFIData.fields_id.fiscal_code+"_field");
        if(show){
            $fiscal_code.removeClass("wbfi-hidden woocommerce-validated");
            if(mandatory){
                $fiscal_code.addClass('validate-required validate-fiscal-code woocommerce-invalid-required-field woocommerce-invalid');
            }
        }else{
            $fiscal_code.addClass("wbfi-hidden");
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
            $vat.removeClass("wbfi-hidden woocommerce-validated").addClass('validate-required validate-vat woocommerce-invalid-required-field woocommerce-invalid');
        }else{
            $vat.addClass("wbfi-hidden").removeClass('validate-required validate-vat woocommerce-invalid-required-field woocommerce-invalid');
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
            $vies_check.removeClass("wbfi-hidden");
        }else{
            $vies_check.addClass("wbfi-hidden").attr("checked",false);
        }
    }

    /**
     * Hides VIES Check
     */
    hide_vies_check(){
        this.show_vies_check(false);
    }

    /**
     * Hides all fields
     */
    hide_all(){
        this.hide_customer_type();
        this.hide_fiscal_code();
        this.hide_vat();
        this.hide_vies_check();
    }
}
import * as Backbone from 'backbone';
import $ from 'jquery';

export default class extends Backbone.Model{
    initialize() {
        "use strict";
        let $checkout_form = $( 'form.checkout' );
        if($checkout_form.length > 0){
            $checkout_form.on("blur change", ".input-text, select, input:checkbox", this.validate_fields);
            $checkout_form.on("change", ".input-radio[name='billing_wb_woo_fi_customer_type']", this, this.toggle_fields);
            $checkout_form.on("change", "#billing_country", this, this.toggle_fields);
            //$(document).on("update_checkout", "body", this, this.toggle_fields);
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
            current_country = $("#billing_country").val(),
            $fiscal_code = $("#billing_wb_woo_fi_fiscal_code_field"),
            $vat = $("#billing_wb_woo_fi_vat_field");

        if(current_customer_type === undefined) return;

        if(current_country == "IT"){
            $fiscal_code.removeClass("hidden");
        }else{
            $fiscal_code.addClass("hidden");
        }
        switch(current_customer_type){
            case 'individual':
                $vat.addClass("hidden");
                break;
            case 'company':
                $vat.removeClass("hidden");
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
            $parent = $el.closest( '.form-row' ),
            validated = true;

        if ( $parent.is( '.validate-fiscal-code' ) ) {
            //todo: validate the fiscal code
            validated = true;
        }

        if ( $parent.is( '.validate-vat' ) ) {
            //todo: validate the vat
            validated = true;
        }

        if ( validated ) {
            $parent.removeClass( 'woocommerce-invalid woocommerce-invalid-required-field' ).addClass( 'woocommerce-validated' );
        }
    }
}
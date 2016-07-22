import * as Backbone from 'backbone';
import $ from 'jquery';

export default class extends Backbone.Model{
    initialize() {
        "use strict";
        let $checkout_form = $( 'form.checkout' );
        if($checkout_form.length > 0){
            $checkout_form.on("blur change", ".input-text, select, input:checkbox", this.validate_fields);
        }
    }

    /**
     * Validate field callback.
     *
     * This mirror the format of validate_fields() in WooCommerce checkout.js
     */
    validate_fields(event){
        "use strict";
        var $el = $( this ),
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
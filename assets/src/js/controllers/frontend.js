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
     * Validate field callback
     */
    validate_fields(e){
        "use strict";
        console.log(e);
        console.log($(this));
    }
}
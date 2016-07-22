import * as Backbone from 'backbone';
import $ from 'jquery';
import CheckoutView from '../views/checkout.js';

export default class extends Backbone.Model{
    initialize() {
        "use strict";
        let $checkout_form = $( 'form.checkout' );
        if($checkout_form.length > 0){
            new CheckoutView();
        }
    }
}
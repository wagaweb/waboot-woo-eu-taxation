# Waboot EU Taxation for WooCommerce

This plugin adds the fields for fiscal code and VAT number among the WooCommerce billing fields.

During the checkout an additional field allows the customer to specify if he\she is buying as private individuals or in behalf of a company and whether the VAT number (not required in the first case) is [VIES](http://ec.europa.eu/taxation_customs/vies/vieshome.do) valid, then the system will calculate the taxation accordingly.

The system will also validate VAT numbers and fiscal codes.

## Usage

- Activate the plugin

- Activate the taxation management in WooCommerce

- Go to "Standard Rate" tab and add your country VAT tax. For example, for Italy, add a tax like that:

    Country code: IT  
    State code: *  
    Postcode/ZIP: *  
    City: *  
    Rate %: 222  
    Tax name: IVA  
    Priority: 1  
    Compound: NOT checked  
    Shipping: checked  

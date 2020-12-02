<?php

namespace WBWooEUT;

/**
 * The plugin bootstrap file
 *
 * @package WBWooEUT
 *
 * @wordpress-plugin
 * Plugin Name:       Waboot EU Taxation for WooCommerce
 * Plugin URI:        https://www.waboot.io/
 * Description:       Adds fiscal code and VAT fields (with validation and tax management) in checkout.
 * Version:           3.0.0
 * Author:            WAGA
 * Author URI:        https://www.waga.it/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       waboot-woo-eu-taxation
 * Domain Path:       /languages
 */

use WBWooEUT\includes\Activator;
use WBWooEUT\includes\Deactivator;
use WBWooEUT\includes\Plugin;

if ( ! defined( 'WPINC' ) ) {
	die; //If this file is called directly, abort.
}

require_once "vendor/autoload.php";

register_activation_hook( __FILE__, function(){
    Activator::activate();
});

register_deactivation_hook( __FILE__, function(){
    Deactivator::deactivate();
});

$plugin = new Plugin();
$plugin->run();
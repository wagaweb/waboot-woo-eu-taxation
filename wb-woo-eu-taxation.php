<?php

namespace WBWooEUT;

/**
 * The plugin bootstrap file
 *
 * @package           WBWooEUT
 *
 * @wordpress-plugin
 * Plugin Name:       WB EU Taxation for WooCommerce
 * Plugin URI:        http://www.waga.it/
 * Description:       EU Taxation management for WooCommerce
 * Version:           1.0.0
 * Author:            WAGA
 * Author URI:        http://www.waga.it/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wb-woo-eu-taxation
 * Domain Path:       /languages
 */

use WBWooEUT\includes\Activator;
use WBWooEUT\includes\Deactivator;
use WBWooEUT\includes\Plugin;

if ( ! defined( 'WPINC' ) ) {
	die; //If this file is called directly, abort.
}

require_once "vendor/autoload.php";

/********************************************************/
/****************** PLUGIN BEGIN ************************
/********************************************************/

// Custom plugin autoloader function
spl_autoload_register( function($class){
	$prefix = "WBWooEUT\\";
	$plugin_path = plugin_dir_path( __FILE__ );
	$base_dir = $plugin_path."src/";
	// does the class use the namespace prefix?
	$len = strlen($prefix);
	if (strncmp($prefix, $class, $len) !== 0) {
		// no, move to the next registered autoloader
		return;
	}
	// get the relative class name
	$relative_class = substr($class, $len);
	// replace the namespace prefix with the base directory, replace namespace
	// separators with directory separators in the relative class name, append
	// with .php
	$file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
	// if the file exists, require it
	if (file_exists($file)) {
		require_once $file;
	}else{
		return;
	}
});

register_activation_hook( __FILE__, function(){ Activator::activate(); } );
register_deactivation_hook( __FILE__, function(){ Deactivator::deactivate(); } );

require_once 'src/includes/Plugin.php';
$plugin = new Plugin();
$plugin->run();
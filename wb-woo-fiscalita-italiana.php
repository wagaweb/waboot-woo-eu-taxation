<?php

namespace WBWooFI;

/**
 * The plugin bootstrap file
 *
 * @link              http://www.waboot.com
 * @package           WBWooFI
 *
 * @wordpress-plugin
 * Plugin Name:       WB Fiscalità Italiana per WooCommerce
 * Plugin URI:        http://www.waboot.com/
 * Description:       Un plugin per gestire la fiscalità italiana
 * Version:           1.0.0
 * Author:            WAGA
 * Author URI:        http://www.waga.it/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wb-woo-fiscalita-italiana
 * Domain Path:       /languages
 */

use WBWooFI\includes\Activator;
use WBWooFI\includes\Deactivator;
use WBWooFI\includes\Plugin;

if ( ! defined( 'WPINC' ) ) {
	die; //If this file is called directly, abort.
}

require_once "vendor/autoload.php";

/********************************************************/
/****************** PLUGIN BEGIN ************************
/********************************************************/

// Custom plugin autoloader function
spl_autoload_register( function($class){
	$prefix = "WBWooFI\\";
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

//Backward compatibility autoloader for pub\Pub
spl_autoload_register(function($class){
	$prefix = "WBWooFI\\";
	$plugin_path = plugin_dir_path( __FILE__ );
	$base_dir = $plugin_path."src/";
	if($class == $prefix."pub\\Pub"){
		require_once $base_dir."public/Public.php";
	}
});

register_activation_hook( __FILE__, function(){ Activator::activate(); } );
register_deactivation_hook( __FILE__, function(){ Deactivator::deactivate(); } );

require_once 'src/includes/Plugin.php';
$plugin = new Plugin();
$plugin->run();
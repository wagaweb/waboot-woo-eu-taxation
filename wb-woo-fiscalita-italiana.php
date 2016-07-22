<?php

namespace WBWooFI;

/**
 * The plugin bootstrap file
 *
 * @link              http://www.waboot.com
 * @since             0.0.1
 * @package           WBWooFI
 *
 * @wordpress-plugin
 * Plugin Name:       Wb Sample
 * Plugin URI:        http://www.waboot.com/
 * Description:       Sample Plugin for WBF
 * Version:           0.0.1
 * Author:            WAGA
 * Author URI:        http://www.waga.it/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wb-woo-fi
 * Domain Path:       /languages
 *
 *
 * [IT] Lo scheletro del plugin e composto dai file wb-woo-fi.php, admin/class-admin.php, public/class-public.php e includes/class-plugin.php (che connette admin e public e inizializza la plugin)
 */

use WBWooFI\includes\Activator;
use WBWooFI\includes\Deactivator;
use WBWooFI\includes\Plugin;

if ( ! defined( 'WPINC' ) ) {
	die; //If this file is called directly, abort.
}

require_once plugin_dir_path( __FILE__ ) . 'src/includes/utils.php';
try{
	$wbf_autoloader = includes\get_autoloader();
	require_once $wbf_autoloader;
}catch(\Exception $e){
	includes\maybe_disable_plugin("wb-woo-fi/wb-woo-fi.php"); // /!\ /!\ /!\ HEY, LOOK! EDIT THIS ALSO!! /!\ /!\ /!\
}

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

if(\WBWooFI\includes\pluginsframework_is_present()): // Starts the plugin only if WBF Plugin Framework is present
	require_once plugin_dir_path( __FILE__ ) . 'src/includes/Plugin.php';
	/**
	 * Begins execution of the plugin.
	 *
	 * @since    1.0.0
	 */
	function run() {
		$plugin = new Plugin();
		/*
		 * [IT] Il metodo run si trova in: wbf/includes/pluginsframework/Plugin.php .
		 * Al momento non fa altro che chiamare il metodo omonimo del Loader, che a sua volta registra azioni e filtri dentro WP.
		 */
		$plugin->run();
	}
	run();
endif;
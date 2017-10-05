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
 * Version:           2.1.9
 * Author:            WAGA
 * Author URI:        https://www.waboot.io/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       waboot-woo-eu-taxation
 * Domain Path:       /languages
 */

use WBWooEUT\includes\Activator;
use WBWooEUT\includes\Deactivator;
use function WBWooEUT\includes\get_wbf_admin_download_link;
use function WBWooEUT\includes\install_wbf_wp_update_hooks;
use WBWooEUT\includes\Plugin;
use WBWooEUT\includes\WBFInstaller;

if ( ! defined( 'WPINC' ) ) {
	die; //If this file is called directly, abort.
}

if(file_exists("vendor/autoload")){
    require_once "vendor/autoload.php";
}

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

require_once 'src/includes/wbf-utils.php';
includes\include_wbf_autoloader();

if(class_exists("\\WBF\\components\\pluginsframework\\BasePlugin")){
	register_activation_hook( __FILE__, function(){ Activator::activate(); } );
	register_deactivation_hook( __FILE__, function(){ Deactivator::deactivate(); } );
	$plugin = new Plugin();
	$plugin->run();
}else {
	if(is_admin()){
	    add_action( 'admin_init' , function(){
	        install_wbf_wp_update_hooks();
        });
		add_action( 'admin_notices', function(){
			?>
			<div class="error">
				<p>
                    <?php echo includes\get_wbf_download_button('Waboot EU Taxation for WooCommerce'); ?>
                </p>
			</div>
			<?php
		});
	}
}
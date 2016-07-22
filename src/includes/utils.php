<?php

namespace WBWooFI\includes;

/**
 * Get WBF Path
 * @return mixed|void
 * @throws \Exception
 */
function get_wbf_path(){
	$wbf_path = get_option( "wbf_path" );
	if(!$wbf_path) throw new \Exception("WBF Not Found");
	return $wbf_path;
}

/**
 * Check if WBF is installed as plugin
 * 
 * @return mixed
 */
function wbf_is_installed(){
	return get_option("wbf_installed");
}

/**
 * Checks if WBF is present
 * 
 * @return bool
 */
function wbf_is_present(){
	return function_exists("WBF");
}

/**
 * Checks if Plugins Framework is present
 * 
 * @return mixed
 */
function pluginsframework_is_present(){
	return class_exists("\\WBF\\includes\\pluginsframework\\Plugin");
}

/**
 * Get the WBF Plugin Autoloader
 * @return string
 * @throws \Exception
 */
function get_autoloader(){
	try{
		$wbf_path = get_wbf_path();
	}catch(\Exception $e){
		$wbf_path = ABSPATH."wp-content/plugins/wbf";
	}
	$wbf_autoloader = $wbf_path."/includes/pluginsframework/autoloader.php";
	if(!file_exists($wbf_autoloader)){
		$wbf_autoloader = ABSPATH."wp-content/plugins/wbf"."/includes/pluginsframework/autoloader.php";
		if(!file_exists($wbf_autoloader)){
			throw new \Exception("WBF Directory Not Found");
		}
	}
	return $wbf_autoloader;
}

/**
 * Disable specified plugin is it is active
 * @param $plugin
 */
function maybe_disable_plugin($plugin){
	if(!function_exists("is_plugin_active")){
		include_once(ABSPATH.'wp-admin/includes/plugin.php');
	}
	if(is_plugin_active( $plugin ) ) {
		deactivate_plugins( $plugin );
		if(is_admin()){
			add_action( 'admin_notices', function(){
				?>
				<div class="error">
					<p><?php _e( __FILE__. ' was disabled due it requires Waboot Framework' ); ?></p>
				</div>
			<?php
			});
		}
	}
}
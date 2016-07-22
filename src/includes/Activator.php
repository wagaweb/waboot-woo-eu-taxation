<?php

namespace WBWooFI\includes;

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    WBWooFI
 * @subpackage WBWooFI/includes
 */
class Activator {
	public static function activate($options = []) {
		$options = wp_parse_args([
			'check_wbf_install' => false
		]);
		try{
			if($options['check_wbf_install']){
				$wbf_path = \WBWooFI\includes\get_wbf_path();
			}else{
				if(!\WBWooFI\includes\pluginsframework_is_present()){
					throw new \Exception("WBF Plugins Framework id not present");
				}
			}
		}catch(\Exception $e){
			self::trigger_error($e->getMessage());
		}
	}

	public static function trigger_error($message, $errno = 0) {
		if(isset($_GET['action']) /*&& $_GET['action'] == 'error_scrape'*/) {
			echo '<strong>' . $message . '</strong>';
			exit;
		} else {
			trigger_error($message, $errno);
		}
	}
}

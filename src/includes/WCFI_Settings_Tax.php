<?php

namespace WBWooFI\includes;

if(!class_exists("\\WC_Settings_Tax")){
	$filepath = plugin_dir_path("woocommerce")."/includes/admin/settings/class-wc-settings-tax.php";
	if(is_file($filepath))
		include_once $filepath;
	else
		return;
}

class WCFI_Settings_Tax extends \WC_Settings_Tax{

}
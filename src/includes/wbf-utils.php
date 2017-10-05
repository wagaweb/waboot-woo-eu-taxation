<?php

namespace WBWooEUT\includes;

/**
 * Get the WBF path
 *
 * @return string|boolean
 */
function get_wbf_path(){
	return get_option('wbf_path',false);
}

/**
 * Get the WBF Plugin Autoloader
 */
function include_wbf_autoloader(){
	$wbf_path = get_wbf_path();

	if(!is_dir($wbf_path)){
		$wbf_path = ABSPATH."wp-content/plugins/wbf";
	}

	//Require the base autoloader
	$wbf_base_autoloader = $wbf_path."/wbf-autoloader.php";
	if(is_file($wbf_base_autoloader)){
		require_once $wbf_base_autoloader;
	}
}

/**
 * Builds the link to download WBF via dashboard.
 *
 * @return string
 */
function get_wbf_admin_download_link(){
	$url = self_admin_url('update.php?action=install-plugin&amp;plugin=wbf');
	$url = wp_nonce_url($url, 'install-plugin_wbf');
	return $url;
}

/**
 * Get the WBF download button
 *
 * @param $plugin_name
 *
 * @return string
 */
function get_wbf_download_button($plugin_name){
	$button = sprintf(
		__( '<strong>'.$plugin_name.'</strong> requires Waboot Framework. <span class="wbf-install-now"><a class="wbf-install-btn button" href="%s">%s</a></span>'),
		get_wbf_admin_download_link(),
		__( 'Install Now' )
    );
	return $button;
}

/**
 * Mod WordPress update system to install WBF from an external source.
 */
function install_wbf_wp_update_hooks(){
	add_filter('plugins_api_args', function(\stdClass $args){
		if(isset($args->slug) && $args->slug === 'wbf'){
			$args->fields['short_description'] = 'WordPress Extension Framework';
			$args->fields['homepage'] = 'https://www.waboot.io';
		}
		return $args;
	});
	add_filter('plugins_api', function($res, $action, $args){
		if(isset($args->slug) && $args->slug === 'wbf'){
			$info_url = "http://update.waboot.org/resource/info/plugin/wbf";
			$info_request = wp_remote_get($info_url);
			if(isset($info_request['response']) && $info_request['response']['code'] === 200){
				$info = json_decode($info_request['body']);
				$res = new \stdClass();
				$res->name = $info->name;
				$res->slug = $info->slug;
				$res->version = $info->version;
				$res->download_link = $info->download_url;
			}
		}
		return $res;
	},10,3);
	add_action('admin_head', function(){
	    $activate_label = __('Activate');
	    $installing_label = __('Installing...');
		?>
		<!-- WBF Custom Installer: Begin -->
		<script type="text/javascript">
            if(typeof wbf_install_script_flag === 'undefined'){
                jQuery( document ).ready(function(){
                    var $wbf_install_buttons_wrapper = jQuery('.wbf-install-now'),
                        $wbf_install_buttons = $wbf_install_buttons_wrapper.find('a.wbf-install-btn');
                    $wbf_install_buttons.on('click', function(e){
                        e.preventDefault();
                        var $my_parent_wrapper = jQuery(this).parents('.wbf-install-now');
                        $wbf_install_buttons_wrapper.not($my_parent_wrapper).html('');
                        jQuery(this).addClass('updating-message').html('<?php echo $installing_label; ?>');
                        var req = wp.updates.installPlugin( {
                            slug: 'wbf'
                        } );
                        req.then(function(){
                            $my_parent_wrapper.find('a.wbf-install-btn').removeClass('updating-message').addClass('button-primary').html('<?php echo $activate_label; ?>');
                        },function(){
                            $my_parent_wrapper.find('a.wbf-install-btn').removeClass('updating-message').addClass('button-primary').html('');
                        });
                    });
                });
                var wbf_install_script_flag = true;
            }
		</script>
		<!-- WBF Custom Installer: End -->
		<?php
	},99);
}
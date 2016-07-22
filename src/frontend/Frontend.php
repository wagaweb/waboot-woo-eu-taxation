<?php

namespace WBWooFI\frontend;

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the dashboard-specific stylesheet and JavaScript.
 *
 * @package    WBWooFI
 * @subpackage WBWooFI/public
 */
class Frontend {

	/**
	 * The main plugin class
	 * @var \WBWooFI\includes\Plugin
	 *
	 * [IT] E' possibile utilizzare $this->plugin->admin_plugin per riferirsi alla classe in class-admin.php
	 */
	private $plugin;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param null|string $plugin_name @deprecated
	 * @param null|string $version @deprecated
	 * @param null $core The plugin main object
	 */
	public function __construct( $plugin_name = null, $version = null, $core = null ) {
		if(isset($core)) $this->plugin = $core;
	}

	public function styles(){
		/*
         * UNCOMMENT AND EDIT THESE LINES
         */

		//wp_enqueue_style('wb-woo-fi-style', $this->plugin->get_uri() . 'public/assets/dist/css/wb-woo-fi.min.css');
	}

	public function scripts(){
		/*
		 * UNCOMMENT AND EDIT THESE LINES
		 */

		/*if($this->plugin->is_debug()){
			wp_register_script('wb-woo-fi', $this->plugin->get_uri() . 'public/assets/src/js/bundle.js', array('jquery','backbone','underscore'), false, true);
		}else{
			wp_register_script('wb-woo-fi', $this->plugin->get_uri() . 'public/assets/dist/js/wb-woo-fi.min.js', array('jquery','backbone','underscore'), false, true);
		}

		$localize_args = array(
			'ajax_url' => admin_url('admin-ajax.php'),
			'blogurl' => get_bloginfo("wpurl"),
			'isAdmin' => is_admin()
		);

		wp_localize_script('wb-woo-fi','wbData',$localize_args);
		wp_enqueue_script('wb-woo-fi');*/
	}

	public function register_post_type(){}

	public function rewrite_tags(){}

	public function rewrite_rules(){}

	/**
	 * FOR WBF <= 0.11.0 ONLY
	 *
	 * This functions is hooked to "wbf/get_template_part/path:/templates/parts/content" filter and makes calls to wbf_get_template_part("/templates/parts/content","<slug>") works.
	 * @param $templates
	 * @param array $tpl the first element is the first argument of wbf_get_template_part, the second is the template slug
	 *
	 * @return mixed
	 */
	/*public function get_template_part_override($templates, $tpl){
		if($tpl[1] == "slug"){
			$templates['sources'][] = $this->plugin->get_dir()."public/".ltrim($tpl[0],"/")."-".$tpl[1].".php";
		}
		return $templates;
	}*/

	/**
	 * WIDGETS
	 */

	public function widgets(){
		//...
	}

    /**
     * SHORTCODES
     */

    public function shortcodes(){
	    //...
    }
}
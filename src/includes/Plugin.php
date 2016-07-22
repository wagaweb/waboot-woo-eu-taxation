<?php

namespace WBWooFI\includes;
use WBF\includes\pluginsframework\TemplatePlugin;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, dashboard-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    WBWooFI
 * @subpackage WBWooFI/includes
 */
class Plugin extends TemplatePlugin {
	/**
	 * Define the core functionality of the plugin.
	 *
	 * [IT] Grazie al plugin framework, i plugin possono parlare tra di loro! Quando viene inizializzato un nuovo plugin,
	 * l'istanza viene memorizzata nella variabile globale $GLOBALS['wbf_loaded_plugins'] che è un array associativo indicizzato con i nomi delle plugin.
	 */
	public function __construct() {
		parent::__construct( "wb-woo-fi", plugin_dir_path( dirname( dirname( __FILE__ ) ) ) );

		//Setting the update server:
		//$this->set_update_server("http://update.waboot.org/?action=get_metadata&slug={$this->plugin_name}&type=plugin");

		$this->define_public_hooks();
		$this->define_admin_hooks();

		//Adding a template selectable from the dashboard:
		/*
		 * [IT] Le template indicate qui saranno selezionabili come template di post\pagine e sovrascrivibili dal tema
		 */
		//$this->add_template( "sample.php", __( "Sample", $this->plugin_name ), $this->plugin_dir . "/public/templates/sample.php" );

		//Adding a template injected into Wordpress template system:
		/*
		 * [IT] Le template indicate qui verranno inserite nel template system di wordpress. Se non sovrascritte dal tema verranno utilizzate quelle indicate qui.
		 */
		//$this->add_cpt_template( "single-sample.php", $this->plugin_dir . "/public/templates/single-sample.php" );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality of the plugin.
	 */
	private function define_public_hooks() {
		$plugin_public = $this->loader->public_plugin;

		/*
		 * [IT] Il loader è definito in: wbf/pluginsframework/Loader.php, e incluso come instanza nella classe Plugin.php.
		 * Mette a disposizione le funzioni add_action() e add_filter(). Usare queste funzioni, al posto dei classici add_action() e add_filter()
		 * ha il vantaggio che l'oggetto Plugin tiene traccia delle azioni e dei filtri collegati, facilitando il debug.
		 *
		 * Il loader contiene anche le istanze admin_plugin e public_plugin che sono rispettivamente gli oggetti delle classi in class-admin.php e class-public.php.
		 */

		$this->loader->add_action( 'widgets_init', $plugin_public, 'widgets' );
		$this->loader->add_action( 'init', $plugin_public, 'register_post_type' );
		$this->loader->add_action( 'init', $plugin_public, 'shortcodes' );
		$this->loader->add_action( 'init', $plugin_public, 'rewrite_tags' );
		$this->loader->add_action( 'init', $plugin_public, 'rewrite_rules' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'scripts' );
	}

	/**
	 * Register all of the hooks related to the admin-facing functionality of the plugin.
	 */
	private function define_admin_hooks(){
		$plugin_admin = $this->loader->admin_plugin;
	}

	/**
	 * Load the required dependencies for this plugin (called into parent::_construct())
	 *
	 * [IT] E' possibile utilizzare questa funzione per dei require di eventuali vendors
	 */
	protected function load_dependencies() {
		parent::load_dependencies();
	}
}

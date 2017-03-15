<?php

namespace WBWooEUT\includes\migrations;

use WBF\components\assets\AssetsManager;

class Migration {

	/**
	 * The main plugin class
	 * @var \WBWooEUT\includes\Plugin
	 */
	private $plugin;

	public function __construct($plugin) {
		$this->plugin = $plugin;

		add_action( 'admin_head', [$this, 'enqueue_scripts']);
		add_action( 'admin_notices', [$this, 'admin_notices']);
		add_action( 'wp_ajax_'.$this->plugin->get_plugin_name().'_update_db_fields', [$this, 'update_db_fields']);
	}

	/**
	 *  Adds Eu tax fields
	 */
	public function update_db_fields( ) {
		global $wpdb;

		// retrieve old fields informations
		$fields = $wpdb->get_results( $wpdb->prepare(
			"
				SELECT *
				FROM $wpdb->postmeta 
				WHERE meta_key = %s
			",
			'_billing_cf'
		) );

		// runs through the array populate the rows to add in the DB
		if ( is_array($fields) ) {

			$rows = []; // to hold all new fields
			$to_delete = [];
			$results = [
				'found'             => count($fields),
				'insertions'        => 0,
				'cf'                => 0,
				'piva'              => 0,
				'errors'            => 0,
				'empty_strings'     => 0,
				'unknown_strings'   => 0,
				'removed'           => 0
			];

			foreach ( $fields as $field ) {

				$to_delete[] = ['meta_id' => $field->meta_id];
				$length = strlen($field->meta_value); // 11 piva, 16 cf, 0 empty

				switch ($length){

					case 11: // P.IVA
						$rows[] = [
							'post_id' => $field->post_id,
							'meta_key' => '_billing_wb_woo_fi_customer_type',
							'meta_value' => 'company'
						];
						$rows[] = [
							'post_id' => $field->post_id,
							'meta_key' => '_billing_wb_woo_fi_fiscal_code',
							'meta_value' => $field->meta_value
						];
						$rows[] = [
							'post_id' => $field->post_id,
							'meta_key' => '_billing_wb_woo_fi_vat',
							'meta_value' => $field->meta_value
						];

						$results['piva']++;
						break;

					case 16: // CF

						$rows[] = [
							'post_id' => $field->post_id,
							'meta_key' => '_billing_wb_woo_fi_customer_type',
							'meta_value' => 'individual'
						];
						$rows[] = [
							'post_id' => $field->post_id,
							'meta_key' => '_billing_wb_woo_fi_fiscal_code',
							'meta_value' => $field->meta_value
						];

						$results['cf']++;
						break;

					case 0:
						$results['empty_strings']++;
						break;

					default:
						$results['unknown']++;
				}
			}

			// adds new rows
			if ( !empty($rows) ) {
				foreach ( $rows as $row ) {
					$result = $wpdb->insert( $wpdb->postmeta, $row );
					($result) ? $results['insertions']++ : $results['errors']++;
				}
			}

			if ( !empty($to_delete) ){
				foreach ($to_delete as $meta) {
					$wpdb->delete( $wpdb->postmeta, $meta );
					$results['removed']++;
				}
			}

			echo json_encode($results);
			die();
		}

	}

	/**
	 * Enqueue the update script
	 *
	 * @hooked enqueue_admin_script
	 */
	public function enqueue_scripts() {
		$assets = [
			'eutax-migration' => [
				'uri'   => $this->plugin->get_uri() . 'assets/dist/js/migrations.js',
				'path'  => $this->plugin->get_dir() . 'assets/dist/js/migrations.js',
				'type'  => "js",
				'deps'  => ['jquery'],
				'i10n'  => [
					'name' => 'WBWooEUTMigrationData',
					'params' => [
						'ajax_url' => admin_url('admin-ajax.php'),
						'isAdmin' => is_admin(),
						'blogurl' => get_bloginfo("url"),
						'pluginName'    => $this->plugin->get_plugin_name(),
						'button_id'     => '#'.$this->plugin->get_plugin_name() . '_migrate_billing_cf'
					]
				]
			]
		];

		$am = new AssetsManager($assets);
		$am->enqueue();
	}


	public function admin_notices() {
		global $wpdb;

		$option = $wpdb->get_var( $wpdb->prepare(
			"
				SELECT meta_value
				FROM $wpdb->postmeta 
				WHERE meta_key = %s
			",
			'_billing_cf'
		) );

if ( $option ) {
			$class   = 'notice notice-error';
			$message = 'Migration to new field. <a href="#" id="' . $this->plugin->get_plugin_name() . '_migrate_billing_cf" class="btn btn-default">Click here</a> to update your database and remove old fields: BACKUP your Database! <br> Remember to uninstall the old plugin if everything is ok';
			printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );
		}

	}
}
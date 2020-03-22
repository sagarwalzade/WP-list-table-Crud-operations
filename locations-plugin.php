<?php

/*
  Plugin Name: Locations plugin
  description: A plugin to add ip addresses to custom database tables.
  Version: 1.0.0
  Author: Sagar Walzade
  Author URI: https://author.example.com/
  Text Domain: swlp
 */


/*
 * Define Constants 
 */

define('SWLP_FILE', __FILE__);
define('SWLP_PATH', plugin_dir_path(__FILE__));
define('SWLP_URI', plugin_dir_url(__FILE__));
define('SWLP_PLUGIN_NAME', plugin_basename(__FILE__));

/**
 * do some default settings when plugin activate
 * create 1 custom sql table to store ip addresses.
 */

if (!function_exists("swlp_default_settings_callback")) {

	register_activation_hook(SWLP_FILE, 'swlp_default_settings_callback');

	function swlp_default_settings_callback() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// table 1
		$table_name1 = $wpdb->prefix . 'swlp_ip_address';

		$table_sql1 = "CREATE TABLE IF NOT EXISTS $table_name1 (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		ip_address text DEFAULT '' NOT NULL,
		note text DEFAULT '' NOT NULL,
		created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
		UNIQUE KEY id (id) ) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta($table_sql1);
	}

}

if (!function_exists("swlp_enqueue_scripts_callback")) {

	function swlp_enqueue_scripts_callback() {
		wp_enqueue_style('swlp_css', plugins_url('assets/swlp_style.css', __FILE__));
		wp_register_script( "swlp_ajax", plugins_url('assets/swlp_script.js', __FILE__), array('jquery') );
		wp_localize_script( 'swlp_ajax', 'myAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));        

		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'swlp_ajax' );
	}

	add_action('admin_enqueue_scripts', 'swlp_enqueue_scripts_callback', 501);

}

if (!class_exists('WP_List_Table')) {
	require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

/*
 * Include Files 
 */

include 'admin/ip_address.php';
include 'admin/sourcebox.php';
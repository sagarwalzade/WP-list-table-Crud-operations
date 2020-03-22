<?php

/**
 * Uninstalling database tables created by plugin.
 */

global $wpdb;
$table_name1 = $wpdb->prefix . 'swlp_ip_address';
$wpdb->query('DROP TABLE IF EXISTS ' . $table_name1);
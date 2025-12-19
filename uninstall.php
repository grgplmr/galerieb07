<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;
$table = $wpdb->prefix . 'cg_ratings';
$wpdb->query( "DROP TABLE IF EXISTS {$table}" );

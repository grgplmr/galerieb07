<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CG_Activator {
    public static function activate() {
        global $wpdb;
        $table_name      = $wpdb->prefix . 'cg_ratings';
        $charset_collate = $wpdb->get_charset_collate();
        $sql             = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            gallery_id bigint(20) unsigned NOT NULL,
            email_hash char(64) NOT NULL,
            attachment_id bigint(20) unsigned NOT NULL,
            rating tinyint(3) NOT NULL DEFAULT 0,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY cg_rating_unique (gallery_id, email_hash, attachment_id),
            KEY cg_gallery_email (gallery_id, email_hash)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }
}

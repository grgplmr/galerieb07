<?php
/**
 * Activation handler.
 */

if (! defined('ABSPATH')) {
    exit;
}

class CG_Activator
{
    public static function activate(): void
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $table_name = $wpdb->prefix . 'cg_selections';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE {$table_name} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            gallery_id BIGINT UNSIGNED NOT NULL,
            email_hash CHAR(64) NOT NULL,
            email_raw VARCHAR(190) NOT NULL,
            image_id BIGINT UNSIGNED NOT NULL,
            rating TINYINT UNSIGNED NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY gallery_email (gallery_id, email_hash),
            KEY image_lookup (gallery_id, image_id)
        ) {$charset_collate};";

        dbDelta($sql);

        $options = get_option('cg_settings', []);
        $defaults = [
            'stars_max'         => 5,
            'columns'           => 3,
            'admin_email_target'=> get_option('admin_email'),
        ];
        update_option('cg_settings', array_merge($defaults, $options));
    }
}

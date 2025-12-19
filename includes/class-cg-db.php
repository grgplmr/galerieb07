<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CG_DB {
    private static $table;

    public static function table() {
        global $wpdb;
        if ( ! self::$table ) {
            self::$table = $wpdb->prefix . 'cg_ratings';
        }
        return self::$table;
    }

    public static function normalize_email( $email ) {
        $email = trim( strtolower( $email ) );
        return sanitize_email( $email );
    }

    public static function email_hash( $email ) {
        $normalized = self::normalize_email( $email );
        return hash( 'sha256', $normalized );
    }

    public static function set_rating( $gallery_id, $email, $attachment_id, $rating ) {
        global $wpdb;
        $gallery_id    = absint( $gallery_id );
        $attachment_id = absint( $attachment_id );
        $rating        = cg_sanitize_rating( $rating );
        $email_hash    = self::email_hash( $email );

        $data = array(
            'gallery_id'    => $gallery_id,
            'email_hash'    => $email_hash,
            'attachment_id' => $attachment_id,
            'rating'        => $rating,
            'updated_at'    => current_time( 'mysql' ),
        );

        $table = self::table();
        $wpdb->replace( $table, $data, array( '%d', '%s', '%d', '%d', '%s' ) );
        return true;
    }

    public static function get_ratings_for_email_gallery( $gallery_id, $email ) {
        global $wpdb;
        $gallery_id = absint( $gallery_id );
        $email_hash = self::email_hash( $email );
        $table      = self::table();
        $query      = $wpdb->prepare( "SELECT attachment_id, rating FROM {$table} WHERE gallery_id = %d AND email_hash = %s", $gallery_id, $email_hash );
        $rows       = $wpdb->get_results( $query, ARRAY_A );
        $ratings    = array();
        foreach ( $rows as $row ) {
            $ratings[ absint( $row['attachment_id'] ) ] = intval( $row['rating'] );
        }
        return $ratings;
    }

    public static function list_emails_for_gallery( $gallery_id ) {
        global $wpdb;
        $gallery_id = absint( $gallery_id );
        $table      = self::table();
        $query      = $wpdb->prepare( "SELECT DISTINCT email_hash FROM {$table} WHERE gallery_id = %d", $gallery_id );
        return $wpdb->get_col( $query );
    }
}

// Validation: Step 2 base de données prête

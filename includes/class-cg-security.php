<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CG_Security {
    const META_TOKEN = '_cg_gallery_token';

    public static function get_token( $post_id ) {
        $token = get_post_meta( $post_id, self::META_TOKEN, true );
        if ( ! $token ) {
            $token = wp_generate_uuid4();
            update_post_meta( $post_id, self::META_TOKEN, $token );
        }
        return $token;
    }

    public static function validate_token( $post_id, $token ) {
        $saved = get_post_meta( $post_id, self::META_TOKEN, true );
        return hash_equals( (string) $saved, (string) $token );
    }
}

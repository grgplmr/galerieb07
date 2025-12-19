<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CG_Media {
    public static function resize_attachment( $attachment_id, $max = 1024 ) {
        $file = get_attached_file( $attachment_id );
        if ( ! $file || ! file_exists( $file ) ) {
            return new WP_Error( 'missing_file', __( 'File not found for resizing.', 'client-galleries' ) );
        }

        $editor = wp_get_image_editor( $file );
        if ( is_wp_error( $editor ) ) {
            return $editor;
        }

        $size = $editor->get_size();
        if ( $size['width'] <= $max && $size['height'] <= $max ) {
            return true;
        }

        $editor->resize( $max, $max, false );
        $saved = $editor->save( $file );
        if ( is_wp_error( $saved ) ) {
            return $saved;
        }

        wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $file ) );
        return true;
    }
}

// Validation: Step 6 redimensionnement prêt

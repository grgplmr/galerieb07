<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CG_Ajax_Admin {
    public static function upload_single() {
        $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $post  = isset( $_POST['gallery_id'] ) ? absint( $_POST['gallery_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing

        if ( ! wp_verify_nonce( $nonce, 'cg_admin_upload' ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid nonce.', 'client-galleries' ) ), 403 );
        }

        if ( ! current_user_can( 'edit_post', $post ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'client-galleries' ) ), 403 );
        }

        if ( empty( $_FILES['file'] ) || ! isset( $_FILES['file']['tmp_name'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            wp_send_json_error( array( 'message' => __( 'No file received.', 'client-galleries' ) ), 400 );
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $attachment_id = media_handle_upload( 'file', $post );

        if ( is_wp_error( $attachment_id ) ) {
            wp_send_json_error( array( 'message' => $attachment_id->get_error_message() ), 500 );
        }

        $resize = CG_Media::resize_attachment( $attachment_id );
        if ( is_wp_error( $resize ) ) {
            wp_send_json_error( array( 'message' => $resize->get_error_message() ), 500 );
        }

        $thumb = wp_get_attachment_image_src( $attachment_id, 'thumbnail' );
        wp_send_json_success(
            array(
                'attachment_id' => $attachment_id,
                'thumb_url'     => $thumb ? $thumb[0] : '',
                'filename'      => get_the_title( $attachment_id ),
            )
        );
    }
}

// Validation: Step 6 upload admin séquentiel prêt

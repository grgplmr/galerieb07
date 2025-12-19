<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CG_Email {
    public static function send_submission( $gallery_id, $email, $ratings ) {
        $admin_email = get_option( 'admin_email' );
        $gallery     = get_post( $gallery_id );
        if ( ! $gallery ) {
            return new WP_Error( 'invalid_gallery', __( 'Gallery not found for email.', 'client-galleries' ) );
        }

        $subject = sprintf( __( 'Selection received for %s', 'client-galleries' ), $gallery->post_title );

        $attachments_data = array();
        foreach ( $ratings as $attachment_id => $rating ) {
            $title = get_the_title( $attachment_id );
            $url   = wp_get_attachment_url( $attachment_id );
            $attachments_data[] = sprintf( "%s (ID %d): %d - %s", $title, $attachment_id, intval( $rating ), $url );
        }

        $body_lines = array(
            sprintf( __( 'Gallery: %s (ID %d)', 'client-galleries' ), $gallery->post_title, $gallery_id ),
            sprintf( __( 'Client email: %s', 'client-galleries' ), $email ),
            __( 'Ratings:', 'client-galleries' ),
            implode( "\n", $attachments_data ),
        );

        $body = implode( "\n\n", $body_lines );

        $headers = array( 'Content-Type: text/plain; charset=UTF-8' );

        $sent = wp_mail( $admin_email, $subject, $body, $headers );
        if ( ! $sent ) {
            return new WP_Error( 'email_failed', __( 'Unable to send admin email.', 'client-galleries' ) );
        }
        return true;
    }
}

// Validation: Step 10 email admin prêt

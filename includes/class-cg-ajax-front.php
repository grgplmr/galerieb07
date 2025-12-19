<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CG_Ajax_Front {
    private static function validate_request( $gallery_id, $nonce, $token ) {
        if ( $nonce && wp_verify_nonce( $nonce, 'cg_front_nonce_' . $gallery_id ) ) {
            return true;
        }
        if ( $token && CG_Security::validate_token( $gallery_id, $token ) ) {
            return true;
        }
        return false;
    }

    public static function get_ratings() {
        $gallery_id = isset( $_REQUEST['gallery_id'] ) ? absint( $_REQUEST['gallery_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $email      = isset( $_REQUEST['email'] ) ? sanitize_email( wp_unslash( $_REQUEST['email'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $nonce      = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $token      = isset( $_REQUEST['token'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['token'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

        if ( ! self::validate_request( $gallery_id, $nonce, $token ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid security token.', 'client-galleries' ) ), 403 );
        }

        if ( ! $email ) {
            wp_send_json_success( array( 'ratings' => array() ) );
        }

        $ratings = CG_DB::get_ratings_for_email_gallery( $gallery_id, $email );
        wp_send_json_success( array( 'ratings' => $ratings ) );
    }

    public static function set_rating() {
        $gallery_id    = isset( $_POST['gallery_id'] ) ? absint( $_POST['gallery_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $attachment_id = isset( $_POST['attachment_id'] ) ? absint( $_POST['attachment_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $rating        = isset( $_POST['rating'] ) ? cg_sanitize_rating( $_POST['rating'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $email         = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $nonce         = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $token         = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

        if ( ! self::validate_request( $gallery_id, $nonce, $token ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid security token.', 'client-galleries' ) ), 403 );
        }

        if ( ! $gallery_id || ! $attachment_id || ! $email ) {
            wp_send_json_error( array( 'message' => __( 'Missing data for rating.', 'client-galleries' ) ), 400 );
        }

        CG_DB::set_rating( $gallery_id, $email, $attachment_id, $rating );
        wp_send_json_success( array( 'rating' => $rating ) );
    }

    public static function submit_selection() {
        $gallery_id = isset( $_POST['gallery_id'] ) ? absint( $_POST['gallery_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $email      = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $ratings    = isset( $_POST['ratings'] ) ? (array) $_POST['ratings'] : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $nonce      = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $token      = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

        if ( ! self::validate_request( $gallery_id, $nonce, $token ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid security token.', 'client-galleries' ) ), 403 );
        }

        if ( ! $gallery_id || ! $email ) {
            wp_send_json_error( array( 'message' => __( 'Missing email or gallery.', 'client-galleries' ) ), 400 );
        }

        foreach ( $ratings as $attachment_id => $rating ) {
            CG_DB::set_rating( $gallery_id, $email, absint( $attachment_id ), cg_sanitize_rating( $rating ) );
        }

        $email_sent = CG_Email::send_submission( $gallery_id, $email, $ratings );

        if ( is_wp_error( $email_sent ) ) {
            wp_send_json_error( array( 'message' => $email_sent->get_error_message() ), 500 );
        }

        wp_send_json_success( array( 'message' => __( 'Submission sent.', 'client-galleries' ) ) );
    }
}

// Validation: Step 8 ajax front prêt

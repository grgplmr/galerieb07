<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CG_Shortcodes {
    public static function register() {
        add_shortcode( 'client_gallery', array( __CLASS__, 'render' ) );
        // Validation: Step 4 shortcodes prêts
    }

    public static function render( $atts ) {
        $atts = shortcode_atts(
            array(
                'id'    => 0,
                'token' => '',
            ),
            $atts
        );

        $gallery_id = absint( $atts['id'] );
        if ( ! $gallery_id ) {
            return '';
        }

        $token = $atts['token'] ? $atts['token'] : filter_input( INPUT_GET, 'cg_token', FILTER_SANITIZE_STRING );
        if ( $token ) {
            if ( ! CG_Security::validate_token( $gallery_id, $token ) ) {
                return '<div class="cg-error">' . esc_html__( 'Invalid token.', 'client-galleries' ) . '</div>';
            }
        }

        wp_enqueue_style( 'cg-front', CG_PLUGIN_URL . 'assets/front/front.css', array(), CG_VERSION );
        wp_enqueue_script( 'cg-front', CG_PLUGIN_URL . 'assets/front/front.js', array( 'jquery' ), CG_VERSION, true );

        $data = self::get_gallery_payload( $gallery_id );
        wp_localize_script( 'cg-front', 'cgGalleryData_' . $gallery_id, $data );

        ob_start();
        include CG_PLUGIN_DIR . 'templates/front/gallery.php';
        return ob_get_clean();
    }

    public static function maybe_render_direct( $content ) {
        $gallery_id = isset( $_GET['cg_gallery'] ) ? absint( $_GET['cg_gallery'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $token      = isset( $_GET['cg_token'] ) ? sanitize_text_field( wp_unslash( $_GET['cg_token'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( $gallery_id && $token && CG_Security::validate_token( $gallery_id, $token ) ) {
            return self::render(
                array(
                    'id'    => $gallery_id,
                    'token' => $token,
                )
            );
        }
        return $content;
    }

    private static function get_gallery_payload( $gallery_id ) {
        $images = array();
        $query  = new WP_Query(
            array(
                'post_type'      => 'attachment',
                'post_status'    => 'inherit',
                'post_parent'    => $gallery_id,
                'posts_per_page' => -1,
                'orderby'        => 'menu_order ID',
                'order'          => 'ASC',
            )
        );

        foreach ( $query->posts as $attachment ) {
            $thumb = wp_get_attachment_image_src( $attachment->ID, 'thumbnail' );
            $full  = wp_get_attachment_image_src( $attachment->ID, 'large' );
            $images[] = array(
                'id'       => $attachment->ID,
                'title'    => get_the_title( $attachment ),
                'thumb'    => $thumb ? $thumb[0] : '',
                'full'     => $full ? $full[0] : '',
                'caption'  => wp_get_attachment_caption( $attachment->ID ),
            );
        }

        return array(
            'galleryId' => $gallery_id,
            'token'     => CG_Security::get_token( $gallery_id ),
            'images'    => $images,
            'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
            'nonce'     => wp_create_nonce( 'cg_front_nonce_' . $gallery_id ),
            'strings'   => array(
                'uploadError'   => __( 'Upload error', 'client-galleries' ),
                'ratingSaved'   => __( 'Rating saved', 'client-galleries' ),
                'submitSuccess' => __( 'Selection sent', 'client-galleries' ),
            ),
        );
    }
}

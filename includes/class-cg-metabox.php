<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CG_Metabox {
    const META_TOKEN = '_cg_gallery_token';

    public static function register() {
        add_meta_box(
            'cg_gallery_box',
            __( 'Client Gallery', 'client-galleries' ),
            array( __CLASS__, 'render' ),
            CG_CPT::POST_TYPE,
            'normal',
            'high'
        );
        // Validation: Step 5 metabox UI affichée
    }

    public static function cleanup_metaboxes() {
        $screen    = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        $post_type = $screen ? $screen->post_type : '';

        if ( empty( $post_type ) && isset( $_GET['post_type'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $post_type = sanitize_key( wp_unslash( $_GET['post_type'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        }

        if ( CG_CPT::POST_TYPE !== $post_type ) {
            return;
        }

        remove_meta_box( 'slugdiv', CG_CPT::POST_TYPE, 'normal' );
        remove_meta_box( 'slugdiv', CG_CPT::POST_TYPE, 'side' );
        remove_meta_box( 'commentstatusdiv', CG_CPT::POST_TYPE, 'normal' );
        remove_meta_box( 'commentsdiv', CG_CPT::POST_TYPE, 'normal' );
    }

    public static function render( $post ) {
        wp_nonce_field( 'cg_metabox_nonce', 'cg_metabox_nonce_field' );
        $is_new = empty( $post->ID ) || 'auto-draft' === $post->post_status;
        $token  = $is_new ? '' : CG_Security::get_token( $post->ID );
        include CG_PLUGIN_DIR . 'templates/admin/metabox-gallery.php';
    }

    public static function save( $post_id ) {
        if ( ! isset( $_POST['cg_metabox_nonce_field'] ) || ! wp_verify_nonce( wp_unslash( $_POST['cg_metabox_nonce_field'] ), 'cg_metabox_nonce' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        CG_Security::get_token( $post_id );
    }

    public static function enqueue( $hook ) {
        global $post;
        if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
            return;
        }
        if ( ! $post || CG_CPT::POST_TYPE !== $post->post_type ) {
            return;
        }
        $is_new = empty( $post->ID ) || 'auto-draft' === $post->post_status;
        $token  = $is_new ? '' : CG_Security::get_token( $post->ID );

        wp_enqueue_style( 'cg-admin', CG_PLUGIN_URL . 'assets/admin/admin.css', array(), CG_VERSION );
        wp_enqueue_script( 'cg-admin', CG_PLUGIN_URL . 'assets/admin/admin.js', array( 'jquery' ), CG_VERSION, true );
        wp_localize_script(
            'cg-admin',
            'cgAdminData',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'cg_admin_upload' ),
                'gallery' => array(
                    'id'    => $post->ID,
                    'token' => $token,
                ),
                'existing' => self::get_existing_attachments( $post->ID ),
                'strings' => array(
                    'ready'          => __( 'files ready', 'client-galleries' ),
                    'startUpload'    => __( 'Start upload first.', 'client-galleries' ),
                    'uploadError'    => __( 'Upload error', 'client-galleries' ),
                    'invalidFile'    => __( 'Invalid file', 'client-galleries' ),
                    'progressPrefix' => __( 'Uploading', 'client-galleries' ),
                ),
            )
        );
    }

    private static function get_existing_attachments( $gallery_id ) {
        $query = new WP_Query(
            array(
                'post_type'      => 'attachment',
                'post_status'    => 'inherit',
                'post_parent'    => $gallery_id,
                'posts_per_page' => -1,
            )
        );

        $items = array();
        foreach ( $query->posts as $attachment ) {
            $thumb    = wp_get_attachment_image_src( $attachment->ID, 'thumbnail' );
            $items [] = array(
                'id'    => $attachment->ID,
                'thumb' => $thumb ? $thumb[0] : '',
                'name'  => get_the_title( $attachment->ID ),
            );
        }
        return $items;
    }
}

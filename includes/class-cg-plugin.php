<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CG_Plugin {
    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'init', array( 'CG_CPT', 'register' ) );
        add_action( 'add_meta_boxes', array( 'CG_Metabox', 'register' ) );
        add_action( 'save_post_client_gallery', array( 'CG_Metabox', 'save' ) );
        add_action( 'admin_enqueue_scripts', array( 'CG_Metabox', 'enqueue' ) );
        add_action( 'admin_head-post.php', array( 'CG_Metabox', 'cleanup_metaboxes' ) );
        add_action( 'admin_head-post-new.php', array( 'CG_Metabox', 'cleanup_metaboxes' ) );

        add_action( 'wp_ajax_cg_admin_upload_single', array( 'CG_Ajax_Admin', 'upload_single' ) );

        add_action( 'wp_ajax_cg_front_get_ratings', array( 'CG_Ajax_Front', 'get_ratings' ) );
        add_action( 'wp_ajax_nopriv_cg_front_get_ratings', array( 'CG_Ajax_Front', 'get_ratings' ) );
        add_action( 'wp_ajax_cg_front_set_rating', array( 'CG_Ajax_Front', 'set_rating' ) );
        add_action( 'wp_ajax_nopriv_cg_front_set_rating', array( 'CG_Ajax_Front', 'set_rating' ) );
        add_action( 'wp_ajax_cg_front_submit_selection', array( 'CG_Ajax_Front', 'submit_selection' ) );
        add_action( 'wp_ajax_nopriv_cg_front_submit_selection', array( 'CG_Ajax_Front', 'submit_selection' ) );

        add_action( 'init', array( 'CG_Shortcodes', 'register' ) );

        add_filter( 'the_content', array( 'CG_Shortcodes', 'maybe_render_direct' ) );
    }
}

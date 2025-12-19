<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CG_CPT {
    const POST_TYPE = 'client_gallery';

    public static function register() {
        $labels = array(
            'name'               => __( 'Client Galleries', 'client-galleries' ),
            'singular_name'      => __( 'Client Gallery', 'client-galleries' ),
            'add_new_item'       => __( 'Add New Client Gallery', 'client-galleries' ),
            'edit_item'          => __( 'Edit Client Gallery', 'client-galleries' ),
            'new_item'           => __( 'New Client Gallery', 'client-galleries' ),
            'view_item'          => __( 'View Client Gallery', 'client-galleries' ),
            'search_items'       => __( 'Search Client Galleries', 'client-galleries' ),
            'not_found'          => __( 'No client galleries found', 'client-galleries' ),
            'not_found_in_trash' => __( 'No client galleries found in Trash', 'client-galleries' ),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'capability_type'    => 'post',
            'hierarchical'       => false,
            'supports'           => array( 'title', 'editor' ),
            'menu_position'      => 20,
            'menu_icon'          => 'dashicons-format-gallery',
            'rewrite'            => false,
        );

        register_post_type( self::POST_TYPE, $args );
        // Validation: Step 3 CPT enregistré
    }

    public static function columns( $columns ) {
        $columns['cg_shortcode'] = __( 'Shortcode', 'client-galleries' );
        $columns['cg_link']      = __( 'Share Link', 'client-galleries' );
        return $columns;
    }

    public static function column_content( $column, $post_id ) {
        if ( 'cg_shortcode' === $column ) {
            echo esc_html( '[client_gallery id="' . $post_id . '"]' );
        }
        if ( 'cg_link' === $column ) {
            $token = CG_Security::get_token( $post_id );
            $link  = add_query_arg(
                array(
                    'cg_gallery' => $post_id,
                    'cg_token'   => $token,
                ),
                home_url( '/' )
            );
            echo '<a href="' . esc_url( $link ) . '" target="_blank" rel="noopener">' . esc_html__( 'Open', 'client-galleries' ) . '</a>';
        }
    }
}

add_filter( 'manage_' . CG_CPT::POST_TYPE . '_posts_columns', array( 'CG_CPT', 'columns' ) );
add_action( 'manage_' . CG_CPT::POST_TYPE . '_posts_custom_column', array( 'CG_CPT', 'column_content' ), 10, 2 );

<?php
/**
 * Custom post type registration and admin UI.
 */

if (! defined('ABSPATH')) {
    exit;
}

class CG_CPT_Gallery
{
    private static $instance = null;

    public static function instance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('init', [$this, 'register_cpt']);
        add_action('add_meta_boxes', [$this, 'register_metaboxes']);
        add_action('save_post_client_gallery', [$this, 'save_gallery'], 10, 2);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin']);
    }

    public function register_cpt(): void
    {
        $labels = [
            'name'               => __('Client Galleries', 'client-galleries'),
            'singular_name'      => __('Client Gallery', 'client-galleries'),
            'add_new'            => __('Add New', 'client-galleries'),
            'add_new_item'       => __('Add New Gallery', 'client-galleries'),
            'edit_item'          => __('Edit Gallery', 'client-galleries'),
            'new_item'           => __('New Gallery', 'client-galleries'),
            'view_item'          => __('View Gallery', 'client-galleries'),
            'search_items'       => __('Search Galleries', 'client-galleries'),
            'not_found'          => __('No galleries found', 'client-galleries'),
            'not_found_in_trash' => __('No galleries found in Trash', 'client-galleries'),
            'menu_name'          => __('Client Galleries', 'client-galleries'),
        ];

        $args = [
            'label'               => __('Client Galleries', 'client-galleries'),
            'labels'              => $labels,
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'menu_position'       => 26,
            'supports'            => ['title', 'thumbnail'],
            'capability_type'     => 'post',
            'map_meta_cap'        => true,
        ];

        register_post_type('client_gallery', $args);
    }

    public function register_metaboxes(): void
    {
        add_meta_box(
            'cg-gallery-details',
            __('Gallery Details', 'client-galleries'),
            [$this, 'render_metabox'],
            'client_gallery',
            'normal',
            'high'
        );
    }

    public function enqueue_admin(string $hook): void
    {
        global $post;
        if ('post.php' === $hook || 'post-new.php' === $hook) {
            if ($post && 'client_gallery' === $post->post_type) {
                wp_enqueue_style('cg-admin');
                wp_enqueue_script('cg-admin');
                wp_localize_script('cg-admin', 'cgAdmin', [
                    'ajax_url'    => admin_url('admin-ajax.php'),
                    'nonce_upload'=> wp_create_nonce('cg_admin_upload_single'),
                    'gallery_id'  => (int) $post->ID,
                    'strings'     => [
                        'pending'     => __('Pending', 'client-galleries'),
                        'uploading'   => __('Uploading', 'client-galleries'),
                        'completed'   => __('Completed', 'client-galleries'),
                        'error'       => __('Error', 'client-galleries'),
                        'networkError'=> __('Network error', 'client-galleries'),
                        'serverError' => __('Server error', 'client-galleries'),
                        'uploadError' => __('Upload failed', 'client-galleries'),
                        'summary'     => __('Uploads:', 'client-galleries'),
                        'done'        => __('completed', 'client-galleries'),
                        'failed'      => __('failed', 'client-galleries'),
                        'start'       => __('Start upload', 'client-galleries'),
                        'queued'      => __('Queued', 'client-galleries'),
                    ],
                ]);
            }
        }
    }

    public function render_metabox($post): void
    {
        if (! current_user_can('edit_post', $post->ID)) {
            return;
        }

        cg_get_gallery_token($post->ID);
        $shortcode = '[client_gallery id="' . (int) $post->ID . '"]';
        $link = esc_url(cg_get_gallery_link($post->ID));
        wp_nonce_field('cg_gallery_save', 'cg_gallery_nonce');

        $data = [
            'shortcode' => $shortcode,
            'link'      => $link,
            'post_id'   => (int) $post->ID,
        ];
        include CG_PLUGIN_DIR . 'templates/admin/gallery-edit.php';
    }

    public function save_gallery(int $post_id, WP_Post $post): void
    {
        if (! isset($_POST['cg_gallery_nonce']) || ! wp_verify_nonce(sanitize_text_field($_POST['cg_gallery_nonce']), 'cg_gallery_save')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (! current_user_can('edit_post', $post_id)) {
            return;
        }
    }
}

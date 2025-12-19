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
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        $post_id = isset($_GET['post']) ? absint($_GET['post']) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $post_type = $screen && $screen->post_type ? $screen->post_type : (isset($_GET['post_type']) ? sanitize_key($_GET['post_type']) : ''); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $is_gallery_screen = ('post.php' === $hook || 'post-new.php' === $hook) && ('client_gallery' === $post_type);
        $max_file_uploads = ini_get('max_file_uploads');

        if (! $is_gallery_screen) {
            return;
        }

        wp_enqueue_style('cg-admin');
        wp_enqueue_script('cg-admin');
        $maybe_post = isset($GLOBALS['post']) && $GLOBALS['post'] instanceof WP_Post ? $GLOBALS['post'] : null;
        if (! $post_id && $maybe_post && 'client_gallery' === $maybe_post->post_type) {
            $post_id = (int) $maybe_post->ID;
        }
        $localization = [
            'ajaxUrl'         => admin_url('admin-ajax.php'),
            'ajax_url'        => admin_url('admin-ajax.php'),
            'nonce'           => CG_Security::create_nonce('admin_upload'),
            'galleryId'       => (int) $post_id,
            'gallery_id'      => (int) $post_id,
            'maxFileUploads'  => $max_file_uploads ? (int) $max_file_uploads : null,
            'strings'         => [
                'pending'        => __('Pending', 'client-galleries'),
                'uploading'      => __('Uploading', 'client-galleries'),
                'completed'      => __('Completed', 'client-galleries'),
                'error'          => __('Error', 'client-galleries'),
                'networkError'   => __('Network error', 'client-galleries'),
                'serverError'    => __('Upload failed', 'client-galleries'),
                'start'          => __('Start upload', 'client-galleries'),
                'summary'        => __('Uploads', 'client-galleries'),
                'summaryFormat'  => __('%1$s/%2$s successful - %3$s errors', 'client-galleries'),
                'resuming'       => __('Resuming failed uploads...', 'client-galleries'),
                'limitHelp'      => __('Server limit reached: check upload_max_filesize, post_max_size, max_file_uploads', 'client-galleries'),
                'httpStatus'     => __('HTTP status', 'client-galleries'),
                'responseLabel'  => __('Response', 'client-galleries'),
                'fileLabel'      => __('File', 'client-galleries'),
                'maxFileUploads' => $max_file_uploads ? sprintf(__('max_file_uploads: %d', 'client-galleries'), (int) $max_file_uploads) : '',
            ],
        ];
        wp_localize_script('cg-admin', 'cgAdmin', $localization);
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

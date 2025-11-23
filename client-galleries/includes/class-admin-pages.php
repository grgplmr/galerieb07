<?php
/**
 * Admin pages.
 */

if (! defined('ABSPATH')) {
    exit;
}

class CG_Admin_Pages
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
        add_action('admin_menu', [$this, 'register_menu']);
    }

    public function register_menu(): void
    {
        add_submenu_page(
            'edit.php?post_type=client_gallery',
            __('Selections', 'client-galleries'),
            __('Selections', 'client-galleries'),
            'edit_posts',
            'cg-selections',
            [$this, 'render_selections_page']
        );
    }

    public function render_selections_page(): void
    {
        if (! current_user_can('edit_posts')) {
            wp_die(__('You do not have permission to access this page.', 'client-galleries'));
        }
        wp_enqueue_style('cg-admin');
        $gallery_id = isset($_GET['gallery_id']) ? absint($_GET['gallery_id']) : 0;
        $galleries = get_posts([
            'post_type'      => 'client_gallery',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ]);

        $rows = [];
        if ($gallery_id) {
            $rows = CG_DB::instance()->get_gallery_rows($gallery_id);
        }

        $data = [
            'galleries' => $galleries,
            'gallery_id'=> $gallery_id,
            'rows'      => $rows,
        ];
        include CG_PLUGIN_DIR . 'templates/admin/selections-table.php';
    }
}

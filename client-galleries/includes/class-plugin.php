<?php
/**
 * Main plugin loader.
 */

if (! defined('ABSPATH')) {
    exit;
}

class CG_Plugin
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
        add_action('init', [$this, 'load_textdomain']);
        add_action('init', [$this, 'register_assets']);
        add_action('template_redirect', [$this, 'maybe_direct_gallery']);

        CG_DB::instance();
        CG_CPT_Gallery::instance();
        CG_Shortcodes::instance();
        CG_Ajax::instance();
        CG_Admin_Pages::instance();
    }

    public function load_textdomain(): void
    {
        load_plugin_textdomain('client-galleries', false, dirname(plugin_basename(CG_PLUGIN_FILE)) . '/languages');
    }

    public function register_assets(): void
    {
        wp_register_style('cg-admin', CG_PLUGIN_URL . 'assets/css/admin.css', [], CG_VERSION);
        wp_register_style('cg-front', CG_PLUGIN_URL . 'assets/css/front.css', [], CG_VERSION);
        wp_register_script('cg-admin', CG_PLUGIN_URL . 'assets/js/admin.js', ['jquery'], CG_VERSION, true);
        wp_register_script('cg-front', CG_PLUGIN_URL . 'assets/js/front.js', ['jquery'], CG_VERSION, true);
    }

    public function maybe_direct_gallery(): void
    {
        if (is_admin()) {
            return;
        }

        if (isset($_GET['cg_gallery'], $_GET['cg_token'])) {
            $gallery_id = absint($_GET['cg_gallery']);
            $token = sanitize_text_field(wp_unslash($_GET['cg_token']));
            $stored_token = get_post_meta($gallery_id, '_cg_gallery_token', true);
            if ($gallery_id && $token && $token === $stored_token) {
                status_header(200);
                get_header();
                echo do_shortcode('[client_gallery id="' . $gallery_id . '"]');
                get_footer();
                exit;
            }
        }
    }
}

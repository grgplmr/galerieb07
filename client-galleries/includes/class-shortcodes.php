<?php
/**
 * Shortcode rendering.
 */

if (! defined('ABSPATH')) {
    exit;
}

class CG_Shortcodes
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
        add_shortcode('client_gallery', [$this, 'render_gallery']);
    }

    public function render_gallery($atts): string
    {
        $atts = shortcode_atts([
            'id' => 0,
        ], $atts, 'client_gallery');

        $gallery_id = absint($atts['id']);
        if (! $gallery_id && isset($_GET['cg_gallery'])) {
            $gallery_id = absint($_GET['cg_gallery']);
        }

        if (! $gallery_id) {
            return '';
        }

        $token = isset($_GET['cg_token']) ? sanitize_text_field(wp_unslash($_GET['cg_token'])) : '';
        $stored_token = get_post_meta($gallery_id, '_cg_gallery_token', true);
        if ($stored_token && $token && $token !== $stored_token) {
            return esc_html__('Invalid gallery link.', 'client-galleries');
        }

        $cookie_key = 'cg_email_' . $gallery_id;
        $email = isset($_COOKIE[$cookie_key]) ? sanitize_email(wp_unslash($_COOKIE[$cookie_key])) : '';

        ob_start();
        if (! $email) {
            $this->render_email_gate($gallery_id, $cookie_key);
        } else {
            $this->render_gallery_grid($gallery_id, $email);
        }
        return ob_get_clean();
    }

    private function render_email_gate(int $gallery_id, string $cookie_key): void
    {
        wp_enqueue_style('cg-front');
        wp_enqueue_script('cg-front');
        wp_localize_script('cg-front', 'cgFront', [
            'ajaxUrl'    => admin_url('admin-ajax.php'),
            'nonce'      => CG_Security::create_nonce('front_actions'),
            'starsMax'   => (int) cg_get_option('stars_max', 5),
            'galleryId'  => $gallery_id,
            'cookieKey'  => $cookie_key,
        ]);
        include CG_PLUGIN_DIR . 'templates/front/email-gate.php';
    }

    private function render_gallery_grid(int $gallery_id, string $email): void
    {
        wp_enqueue_style('cg-front');
        wp_enqueue_script('cg-front');

        $email = cg_normalize_email($email);
        $email_hash = cg_email_hash($email);
        $selection = CG_DB::instance()->get_selection($gallery_id, $email_hash);

        $images = get_children([
            'post_parent'    => $gallery_id,
            'post_type'      => 'attachment',
            'post_mime_type' => 'image',
            'orderby'        => 'menu_order ID',
            'order'          => 'ASC',
        ]);

        wp_localize_script('cg-front', 'cgFront', [
            'ajaxUrl'   => admin_url('admin-ajax.php'),
            'nonce'     => CG_Security::create_nonce('front_actions'),
            'starsMax'  => (int) cg_get_option('stars_max', 5),
            'galleryId' => $gallery_id,
            'email'     => $email,
            'selection' => $selection,
        ]);

        $data = [
            'images'    => $images,
            'selection' => $selection,
            'email'     => $email,
            'gallery'   => get_post($gallery_id),
        ];
        include CG_PLUGIN_DIR . 'templates/front/gallery-grid.php';
    }
}

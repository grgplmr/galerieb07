<?php
/**
 * AJAX endpoints.
 */

if (! defined('ABSPATH')) {
    exit;
}

class CG_Ajax
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
        add_action('wp_ajax_nopriv_cg_save_rating', [$this, 'save_rating']);
        add_action('wp_ajax_cg_save_rating', [$this, 'save_rating']);
        add_action('wp_ajax_nopriv_cg_submit_selection', [$this, 'submit_selection']);
        add_action('wp_ajax_cg_submit_selection', [$this, 'submit_selection']);
        add_action('wp_ajax_nopriv_cg_render_gallery', [$this, 'render_gallery']);
        add_action('wp_ajax_cg_render_gallery', [$this, 'render_gallery']);
        add_action('wp_ajax_cg_export_csv', [$this, 'export_csv']);
    }

    public function save_rating(): void
    {
        $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
        if (! CG_Security::verify_nonce($nonce, 'front_actions')) {
            wp_send_json_error(__('Invalid nonce', 'client-galleries'));
        }

        $gallery_id = isset($_POST['gallery_id']) ? absint($_POST['gallery_id']) : 0;
        $image_id = isset($_POST['image_id']) ? absint($_POST['image_id']) : 0;
        $rating = isset($_POST['rating']) ? absint($_POST['rating']) : 0;
        $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';

        if (! $gallery_id || ! $image_id || ! $email) {
            wp_send_json_error(__('Missing data', 'client-galleries'));
        }

        $email = cg_normalize_email($email);
        $email_hash = cg_email_hash($email);
        $rating = max(0, min($rating, (int) cg_get_option('stars_max', 5)));

        CG_DB::instance()->upsert_rating($gallery_id, $email_hash, $email, $image_id, $rating);

        wp_send_json_success(['rating' => $rating]);
    }

    public function submit_selection(): void
    {
        $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
        if (! CG_Security::verify_nonce($nonce, 'front_actions')) {
            wp_send_json_error(__('Invalid nonce', 'client-galleries'));
        }

        $gallery_id = isset($_POST['gallery_id']) ? absint($_POST['gallery_id']) : 0;
        $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';

        if (! $gallery_id || ! $email) {
            wp_send_json_error(__('Missing data', 'client-galleries'));
        }

        $email = cg_normalize_email($email);
        $email_hash = cg_email_hash($email);

        $rows = CG_DB::instance()->get_selection_rows($gallery_id, $email_hash);
        CG_Mailer::send_selection($gallery_id, $email, $rows);

        wp_send_json_success(['message' => __('Selection sent', 'client-galleries')]);
    }

    public function render_gallery(): void
    {
        $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
        if (! CG_Security::verify_nonce($nonce, 'front_actions')) {
            wp_send_json_error(__('Invalid nonce', 'client-galleries'));
        }

        $gallery_id = isset($_POST['gallery_id']) ? absint($_POST['gallery_id']) : 0;
        $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
        $cookie_key = isset($_POST['cookie_key']) ? sanitize_text_field(wp_unslash($_POST['cookie_key'])) : '';

        if (! $gallery_id || ! $email || ! $cookie_key) {
            wp_send_json_error(__('Missing data', 'client-galleries'));
        }

        $email = cg_normalize_email($email);
        $html = CG_Shortcodes::instance()->render_gallery_markup($gallery_id, $email, $cookie_key);
        $selection = CG_Shortcodes::instance()->get_gallery_data($gallery_id, $email)['selection'];

        wp_send_json_success([
            'html'      => $html,
            'email'     => $email,
            'selection' => $selection,
        ]);
    }

    public function export_csv(): void
    {
        if (! current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied', 'client-galleries'));
        }

        $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field($_GET['_wpnonce']) : '';
        if (! wp_verify_nonce($nonce, 'cg_export_csv')) {
            wp_die(__('Invalid nonce', 'client-galleries'));
        }

        $gallery_id = isset($_GET['gallery_id']) ? absint($_GET['gallery_id']) : 0;
        if (! $gallery_id) {
            wp_die(__('Missing gallery', 'client-galleries'));
        }

        $rows = CG_DB::instance()->get_gallery_rows($gallery_id);
        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="cg-selections-' . $gallery_id . '.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['email', 'image_id', 'filename', 'rating', 'updated_at']);
        foreach ($rows as $row) {
            $file = get_attached_file((int) $row['image_id']);
            fputcsv($output, [
                $row['email_raw'],
                $row['image_id'],
                $file ? basename($file) : '',
                $row['rating'],
                $row['updated_at'],
            ]);
        }
        fclose($output);
        exit;
    }
}

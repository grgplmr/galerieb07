<?php
/**
 * Media handling.
 */

if (! defined('ABSPATH')) {
    exit;
}

class CG_Media
{
    public static function hooks(): void
    {
        add_action('wp_ajax_cg_admin_upload_images', [__CLASS__, 'handle_upload_legacy']);
        add_action('wp_ajax_cg_admin_upload_single', [__CLASS__, 'handle_upload_single']);
    }

    /**
     * Legacy multi-file handler. Kept for backward compatibility.
     */
    public static function handle_upload_legacy(): void
    {
        if (! current_user_can('upload_files')) {
            wp_send_json_error(__('Permission denied', 'client-galleries'));
        }

        $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
        if (! CG_Security::verify_nonce($nonce, 'admin_upload')) {
            wp_send_json_error(__('Invalid nonce', 'client-galleries'));
        }

        $gallery_id = isset($_POST['gallery_id']) ? absint($_POST['gallery_id']) : 0;
        if (! $gallery_id) {
            wp_send_json_error(__('Missing gallery', 'client-galleries'));
        }

        $attachments = [];
        if (! empty($_FILES['cg_files'])) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
            $files = self::restructure_files_array($_FILES['cg_files']);
            foreach ($files as $file) {
                $attachment_id = media_handle_sideload($file, $gallery_id);
                if (is_wp_error($attachment_id)) {
                    continue;
                }

                $path = get_attached_file($attachment_id);
                self::resize_image($path);
                wp_update_attachment_metadata($attachment_id, wp_generate_attachment_metadata($attachment_id, $path));

                $attachments[] = [
                    'id'  => $attachment_id,
                    'url' => wp_get_attachment_url($attachment_id),
                ];
            }
        }

        wp_send_json_success(['attachments' => $attachments]);
    }

    public static function handle_upload_single(): void
    {
        if (! current_user_can('upload_files')) {
            wp_send_json_error(['message' => __('Permission denied', 'client-galleries')]);
        }

        $nonce = isset($_POST['_ajax_nonce']) ? sanitize_text_field(wp_unslash($_POST['_ajax_nonce'])) : '';
        if (! wp_verify_nonce($nonce, 'cg_admin_upload_single')) {
            wp_send_json_error(['message' => __('Invalid nonce', 'client-galleries')]);
        }

        $gallery_id = isset($_POST['gallery_id']) ? absint($_POST['gallery_id']) : 0;
        if (! $gallery_id) {
            wp_send_json_error(['message' => __('Missing gallery', 'client-galleries')]);
        }

        if (! current_user_can('edit_post', $gallery_id)) {
            wp_send_json_error(['message' => __('You cannot edit this gallery.', 'client-galleries')]);
        }

        if (empty($_FILES['file'])) {
            wp_send_json_error(['message' => __('No file received.', 'client-galleries')]);
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $attachment_id = media_handle_upload('file', $gallery_id);
        if (is_wp_error($attachment_id)) {
            wp_send_json_error(['message' => $attachment_id->get_error_message()]);
        }

        $path = get_attached_file($attachment_id);
        self::resize_image($path);
        wp_update_attachment_metadata($attachment_id, wp_generate_attachment_metadata($attachment_id, $path));

        $thumb = wp_get_attachment_image_src($attachment_id, 'thumbnail') ?: wp_get_attachment_image_src($attachment_id, 'medium');
        $full_url = wp_get_attachment_url($attachment_id);

        wp_send_json_success([
            'attachment_id' => $attachment_id,
            'thumb_url'     => $thumb ? $thumb[0] : $full_url,
            'full_url'      => $full_url,
            'filename'      => get_the_title($attachment_id),
        ]);
    }

    public static function resize_image(string $path, int $max = 1024): void
    {
        $editor = wp_get_image_editor($path);
        if (is_wp_error($editor)) {
            return;
        }
        $size = $editor->get_size();
        if (! $size || (int) $size['width'] <= $max && (int) $size['height'] <= $max) {
            return;
        }

        $editor->resize($max, $max, false);
        $editor->save($path);
    }

    private static function restructure_files_array(array $files): array
    {
        $output = [];
        $file_count = count($files['name']);
        $file_keys = array_keys($files);
        for ($i = 0; $i < $file_count; $i++) {
            foreach ($file_keys as $key) {
                $output[$i][$key] = $files[$key][$i];
            }
        }
        return $output;
    }
}
CG_Media::hooks();

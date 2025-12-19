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
        add_action('wp_ajax_cg_admin_upload_images', [__CLASS__, 'handle_upload']);
    }

    public static function handle_upload(): void
    {
        if (! current_user_can('upload_files')) {
            self::send_error_response('permission_denied', __('Permission denied', 'client-galleries'), 403);
        }

        $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
        if (! CG_Security::verify_nonce($nonce, 'admin_upload')) {
            self::send_error_response('invalid_nonce', __('Invalid nonce', 'client-galleries'), 403);
        }

        $gallery_id = isset($_POST['gallery_id']) ? absint($_POST['gallery_id']) : 0;
        if (! $gallery_id) {
            self::send_error_response('missing_gallery', __('Missing gallery', 'client-galleries'));
        }

        if (empty($_FILES['file'])) {
            self::send_error_response('missing_file', __('No file received', 'client-galleries'));
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $file = self::normalize_file($_FILES['file']);
        if (empty($file['tmp_name'])) {
            self::send_error_response('missing_tmp_name', __('Temporary file missing', 'client-galleries'));
        }
        if (UPLOAD_ERR_OK !== $file['error']) {
            self::send_error_response(
                'upload_error',
                sprintf(__('Upload error (code %d)', 'client-galleries'), (int) $file['error'])
            );
        }

        $attachment_id = media_handle_sideload($file, $gallery_id);
        if (is_wp_error($attachment_id)) {
            self::send_error_response(
                $attachment_id->get_error_code() ?: 'upload_error',
                $attachment_id->get_error_message(),
                400,
                [
                    'error_data' => $attachment_id->get_error_data(),
                ]
            );
        }

        $path = get_attached_file($attachment_id);
        self::resize_image($path);
        wp_update_attachment_metadata($attachment_id, wp_generate_attachment_metadata($attachment_id, $path));

        wp_send_json_success([
            'attachments' => [
                [
                    'id'  => $attachment_id,
                    'url' => wp_get_attachment_url($attachment_id),
                ],
            ],
            'debug'       => self::get_debug_info(),
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

    private static function normalize_file(array $file): array
    {
        return [
            'name'     => isset($file['name']) ? sanitize_file_name($file['name']) : '',
            'type'     => isset($file['type']) ? sanitize_mime_type($file['type']) : '',
            'tmp_name' => isset($file['tmp_name']) ? $file['tmp_name'] : '',
            'error'    => isset($file['error']) ? (int) $file['error'] : UPLOAD_ERR_NO_FILE,
            'size'     => isset($file['size']) ? (int) $file['size'] : 0,
        ];
    }

    private static function send_error_response(string $code, string $message, int $status = 400, array $data = []): void
    {
        $payload = array_merge(
            [
                'code'    => $code,
                'message' => $message,
                'debug'   => self::get_debug_info(),
            ],
            $data
        );

        wp_send_json_error($payload, $status);
    }

    private static function get_debug_info(): array
    {
        $max_file_uploads = ini_get('max_file_uploads');
        return [
            'max_file_uploads' => $max_file_uploads ? (int) $max_file_uploads : null,
        ];
    }
}
CG_Media::hooks();

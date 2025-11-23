<?php
/**
 * Mailer helpers.
 */

if (! defined('ABSPATH')) {
    exit;
}

class CG_Mailer
{
    public static function send_selection(int $gallery_id, string $email, array $rows): void
    {
        $admin_email = cg_get_option('admin_email_target', get_option('admin_email'));
        $gallery = get_post($gallery_id);
        if (! $gallery || ! $admin_email) {
            return;
        }

        $subject = sprintf(__('Selection for gallery %s', 'client-galleries'), $gallery->post_title);
        $body = '<h2>' . esc_html($gallery->post_title) . '</h2>';
        $body .= '<p>' . sprintf(__('Client email: %s', 'client-galleries'), esc_html($email)) . '</p>';
        if ($rows) {
            $body .= '<ul>';
            foreach ($rows as $row) {
                $file = get_attached_file((int) $row['image_id']);
                $name = $file ? basename($file) : '#';
                $body .= '<li>' . esc_html($name) . ' - ' . sprintf(__('Rating: %d', 'client-galleries'), (int) $row['rating']) . '</li>';
            }
            $body .= '</ul>';
        } else {
            $body .= '<p>' . esc_html__('No ratings provided.', 'client-galleries') . '</p>';
        }

        $headers = ['Content-Type: text/html; charset=UTF-8'];
        wp_mail($admin_email, $subject, $body, $headers);
    }
}

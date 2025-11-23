<?php
if (! defined('ABSPATH')) {
    exit;
}

function cg_get_option(string $key, $default = '')
{
    $options = get_option('cg_settings', []);
    return isset($options[$key]) ? $options[$key] : $default;
}

function cg_normalize_email(string $email): string
{
    return strtolower(trim($email));
}

function cg_email_hash(string $email): string
{
    return hash('sha256', cg_normalize_email($email));
}

function cg_get_gallery_token(int $post_id): string
{
    $token = get_post_meta($post_id, '_cg_gallery_token', true);
    if (! $token) {
        $token = wp_generate_uuid4();
        update_post_meta($post_id, '_cg_gallery_token', $token);
    }
    return $token;
}

function cg_get_gallery_link(int $post_id): string
{
    $token = cg_get_gallery_token($post_id);
    return add_query_arg([
        'cg_gallery' => $post_id,
        'cg_token'   => $token,
    ], home_url('/'));
}

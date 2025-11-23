<?php
/**
 * Security helpers.
 */

if (! defined('ABSPATH')) {
    exit;
}

class CG_Security
{
    public static function get_nonce_action(string $action): string
    {
        return 'cg_' . $action;
    }

    public static function create_nonce(string $action): string
    {
        return wp_create_nonce(self::get_nonce_action($action));
    }

    public static function verify_nonce(string $nonce, string $action): bool
    {
        return wp_verify_nonce($nonce, self::get_nonce_action($action));
    }
}

<?php
/**
 * Database layer.
 */

if (! defined('ABSPATH')) {
    exit;
}

class CG_DB
{
    private static $instance = null;
    private $table;

    private function __construct()
    {
        global $wpdb;
        $this->table = $wpdb->prefix . 'cg_selections';
    }

    public static function instance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function table(): string
    {
        return $this->table;
    }

    public function upsert_rating(int $gallery_id, string $email_hash, string $email_raw, int $image_id, int $rating): void
    {
        global $wpdb;
        $data = [
            'gallery_id' => $gallery_id,
            'email_hash' => $email_hash,
            'email_raw'  => $email_raw,
            'image_id'   => $image_id,
            'rating'     => $rating,
            'updated_at' => current_time('mysql'),
        ];

        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table} WHERE gallery_id = %d AND email_hash = %s AND image_id = %d LIMIT 1",
            $gallery_id,
            $email_hash,
            $image_id
        ));

        if ($existing) {
            $wpdb->update(
                $this->table,
                $data,
                ['id' => (int) $existing],
                ['%d', '%s', '%s', '%d', '%d', '%s'],
                ['%d']
            );
        } else {
            $wpdb->insert(
                $this->table,
                $data,
                ['%d', '%s', '%s', '%d', '%d', '%s']
            );
        }
    }

    public function get_selection(int $gallery_id, string $email_hash): array
    {
        global $wpdb;
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT image_id, rating FROM {$this->table} WHERE gallery_id = %d AND email_hash = %s",
            $gallery_id,
            $email_hash
        ), ARRAY_A);

        $selection = [];
        foreach ($results as $row) {
            $selection[(int) $row['image_id']] = (int) $row['rating'];
        }
        return $selection;
    }

    public function get_selection_rows(int $gallery_id, string $email_hash): array
    {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE gallery_id = %d AND email_hash = %s ORDER BY updated_at DESC",
            $gallery_id,
            $email_hash
        ), ARRAY_A);
    }

    public function get_gallery_emails(int $gallery_id): array
    {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT email_hash, email_raw FROM {$this->table} WHERE gallery_id = %d",
            $gallery_id
        ), ARRAY_A);
    }

    public function get_gallery_rows(int $gallery_id): array
    {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE gallery_id = %d ORDER BY email_raw ASC, updated_at DESC",
            $gallery_id
        ), ARRAY_A);
    }
}

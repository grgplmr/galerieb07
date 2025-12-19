<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CG_Deactivator {
    public static function deactivate() {
        flush_rewrite_rules();
    }
}

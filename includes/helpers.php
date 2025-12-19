<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function cg_array_get( $array, $key, $default = null ) {
    return isset( $array[ $key ] ) ? $array[ $key ] : $default;
}

function cg_sanitize_int( $value ) {
    return absint( $value );
}

function cg_sanitize_rating( $value ) {
    $value = intval( $value );
    if ( $value < 0 ) {
        $value = 0;
    }
    if ( $value > 5 ) {
        $value = 5;
    }
    return $value;
}

function cg_clean_text( $value ) {
    return sanitize_text_field( wp_unslash( $value ) );
}
